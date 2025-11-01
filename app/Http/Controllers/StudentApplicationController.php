<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Institute;
use App\Models\ParaGroup;
use App\Models\Student;

class StudentApplicationController extends Controller
{
    /**
     * GET/POST /api/student-applications/registered-students
     *
     * Required: exam_id, zamat_id, institute_code
     * Optional: sort=registration|name (default: registration), dir=asc|desc
     *
     * Note: শুধু Paid অ্যাপ্লিকেশনগুলোই ধরা হবে এবং কোনো পেজিনেশন নেই।
     */
    public function registeredStudents(Request $request)
    {
        $validated = $request->validate([
            'exam_id'        => 'required|exists:exams,id',
            'zamat_id'       => 'required|exists:zamats,id',
            'institute_code' => 'required|exists:institutes,institute_code',
            'sort'           => 'nullable|string|in:registration,name',
            'dir'            => 'nullable|string|in:asc,desc',
        ]);

        // Paid applications
        $apps = Application::query()
            ->select(['id', 'exam_id', 'zamat_id', 'institute_id', 'students', 'application_date', 'created_at'])
            ->with(['institute:id,name,institute_code'])
            ->where('exam_id', $validated['exam_id'])
            ->where('zamat_id', $validated['zamat_id'])
            ->whereHas('institute', function ($q) use ($validated) {
                $q->where('institute_code', $validated['institute_code']);
            })
            ->where('payment_status', 'Paid')
            ->get();

        $paidApplications = $apps->count();

        // institute_id resolve (first app অথবা fallback by code)
        $instituteId = optional($apps->first())->institute_id
            ?? Institute::where('institute_code', $validated['institute_code'])->value('id');

        // সব রেজিস্ট্রেশন নাম্বার (int) সংগ্রহ
        $allRegs = $apps->flatMap(fn($app) => collect($app->students ?? [])
            ->pluck('registration'))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        // students টেবিলে আগেই আছে—এগুলো বাদ দেব
        $alreadyRegs = $allRegs->isNotEmpty()
            ? Student::query()
            ->where('exam_id', $validated['exam_id'])
            ->where('zamat_id', $validated['zamat_id'])
            ->where('institute_id', $instituteId)
            ->whereIn('registration_number', $allRegs->all())
            ->pluck('registration_number')
            ->map(fn($v) => (int) $v)
            ->all()
            : [];

        $alreadySet = array_flip($alreadyRegs); // O(1) lookup

        // para map (optional display)
        $paraIds = $apps->flatMap(fn($app) => collect($app->students ?? [])->pluck('para'))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => is_numeric($v) ? (int) $v : $v)
            ->unique()
            ->values();

        $paraMap = $paraIds->isNotEmpty()
            ? ParaGroup::query()->whereIn('id', $paraIds)->pluck('name', 'id')->toArray()
            : [];

        // ফ্ল্যাট রো — কিন্তু আগেই থাকা রেজিস্ট্রেশনগুলো বাদ
        $rows = $apps->flatMap(function ($app) use ($paraMap, $alreadySet) {
            $inst = optional($app->institute);
            return collect($app->students ?? [])->map(function ($s) use ($app, $inst, $paraMap, $alreadySet) {
                $reg = $s['registration'] ?? null;
                if ($reg !== null && isset($alreadySet[(int) $reg])) {
                    return null; // skip
                }

                $paraId = $s['para'] ?? null;
                $paraIdNorm = $paraId === '' ? null : (is_numeric($paraId) ? (int) $paraId : $paraId);

                return [
                    'application_id'   => (int) $app->id,
                    'exam_id'          => (int) $app->exam_id,
                    'zamat_id'         => (int) $app->zamat_id,
                    'registration'     => $reg,
                    'name'             => (string) ($s['name'] ?? ''),
                    'father_name'      => (string) ($s['father_name'] ?? ''),
                    'date_of_birth'    => $s['date_of_birth'] ?? null,
                    'para_id'          => $paraIdNorm,
                    'para_name'        => $paraIdNorm ? ($paraMap[$paraIdNorm] ?? null) : null,
                    'address'          => $s['address'] ?? null,
                    'institute_name'   => (string) ($inst->name ?? ''),
                    'institute_code'   => (string) ($inst->institute_code ?? ''),
                    'application_date' => (string) ($app->application_date ?? $app->created_at),
                ];
            })->filter(); // nullগুলো বাদ
        })->values();

        // sort (default: registration asc)
        $sort = $request->input('sort', 'registration');
        $dir  = strtolower($request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $rows = $rows->sortBy(function ($r) use ($sort) {
            $v = $r[$sort] ?? null;
            return $sort === 'registration' && is_numeric($v) ? (int) $v : (string) $v;
        }, SORT_REGULAR, $dir === 'desc')->values();

        return response()->json([
            'summary' => [
                'paid_applications' => $paidApplications,
                'total_students'    => $rows->count(), // ফিল্টার করার পরের সংখ্যা
            ],
            'data' => $rows->all(),
        ]);
    }
}
