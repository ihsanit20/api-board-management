<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\Exam;
use App\Models\Examiner;
use App\Models\ExamSubject;
use App\Models\Student;
use App\Models\Zamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintController extends Controller
{

    public function PrintEnvelopFinal(Request $request)
    {
        $areaName = $request->input('area_name');
        $instituteCode = $request->input('institute_code');

        $query = DB::table('students')
            ->join('institutes', 'students.institute_id', '=', 'institutes.id')
            ->join('areas', 'institutes.area_id', '=', 'areas.id')
            ->join('zamats', 'students.zamat_id', '=', 'zamats.id')
            ->select(
                'areas.name as area_name',
                'institutes.name as institute_name',
                'institutes.institute_code',
                'institutes.phone',
                'zamats.name as zamat_name',
                DB::raw('COUNT(students.id) as student_count')
            )
            ->whereNotNull('students.roll_number')
            ->groupBy('areas.name', 'institutes.name', 'institutes.institute_code', 'institutes.phone', 'zamats.name');

        if ($areaName) {
            $query->where('areas.name', $areaName);
        }

        if ($instituteCode) {
            $query->where('institutes.institute_code', $instituteCode);
        }

        $data = $query->get()
            ->groupBy('area_name')
            ->map(function ($area) {
                return $area->groupBy('institute_name')->map(function ($institutes) {
                    $institute = $institutes->first();
                    return [
                        'institute_name' => $institute->institute_name,
                        'institute_code' => $institute->institute_code,
                        'phone' => $institute->phone,
                        'zamat_counts' => $institutes->map(function ($item) {
                            return [
                                'zamat_name' => $item->zamat_name,
                                'student_count' => $item->student_count,
                            ];
                        })->values()
                    ];
                })->values();
            });

        return response()->json($data);
    }

    public function generateMarkSheet(Request $request)
    {
        $validated = $request->validate([
            'center_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
        ]);

        $center = Center::findOrFail($validated['center_id']);
        $zamat = Zamat::findOrFail($validated['zamat_id']);

        $lastExamId = ExamSubject::whereHas('subject', function ($query) use ($validated) {
            $query->where('zamat_id', $validated['zamat_id']);
        })->latest()->pluck('exam_id')->first();

        if (!$lastExamId) {
            return response()->json(['message' => 'No exams found for this Zamat.'], 404);
        }

        $students = Student::where('center_id', $center->institute_id)
            ->where('zamat_id', $validated['zamat_id'])
            ->whereNotNull('roll_number')
            ->orderBy('roll_number', 'asc')
            ->get();

        $examSubjects = ExamSubject::whereHas('subject', function ($query) use ($validated) {
            $query->where('zamat_id', $validated['zamat_id']);
        })
            ->where('exam_id', $lastExamId)
            ->with(['subject', 'exam'])
            ->get();

        $examiner = Examiner::where('center_id', $validated['center_id'])->first();

        $examName = $examSubjects->first()?->exam?->name;

        $groupName = $students->first()?->group?->name;

        return response()->json([
            'center' => [
                'id' => $center->id,
                'institute_name' => $center->institute->name,
            ],
            'zamat' => $zamat,
            'group_name' => $groupName, // গ্রুপের নাম রেসপন্সে যোগ করা হলো
            'students' => $students,
            'subjects' => $examSubjects->map(function ($examSubject) {
                return [
                    'name' => $examSubject->subject->name,
                    'full_marks' => $examSubject->full_marks,
                ];
            }),
            'exam_id' => $lastExamId,
            'exam_name' => $examName,
            'examiner' => $examiner ? [
                'name' => $examiner->name,
                'phone' => $examiner->phone,
            ] : null,
        ]);
    }

    public function centerEnvelop()
    {
        $lastExam = Exam::latest()->first();

        $data = Student::query()
            ->with(['center', 'zamat', 'exam'])
            ->where('exam_id', $lastExam->id)
            ->whereNotNull('roll_number')
            ->select('center_id', 'zamat_id', 'exam_id', DB::raw('COUNT(id) as student_count'))
            ->groupBy('center_id', 'zamat_id', 'exam_id')
            ->get()
            ->groupBy('center_id')
            ->map(function ($items, $centerId) {
                $centerName = optional($items->first()->center)->name;
                $examName = optional($items->first()->exam)->name;
                $zamatData = $items->map(function ($item) {
                    return [
                        'zamat_name' => optional($item->zamat)->name,
                        'student_count' => $item->student_count,
                    ];
                });

                return [
                    'center_name' => $centerName,
                    'exam_name' => $examName,
                    'zamat_data' => $zamatData, 
                ];
            });

        return response()->json($data->values());
    }

}
