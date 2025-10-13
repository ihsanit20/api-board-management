<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Area;
use App\Models\Exam;
use App\Models\Institute;
use Illuminate\Http\Request;

class InstituteController extends Controller
{

    public function index(Request $request)
    {
        $query = Institute::query()
            ->with('area')
            ->oldest('institute_code');

        if ($request->has('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->has('is_center')) {
            $query->where('is_center', $request->input('is_center'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        $perPage = $request->input('per_page', 15); // Default per page is 15

        // If 'all' is requested, return the full list
        return $perPage === 'all'
            ? response()->json([
                'data' => $query->get(),
                'total' => $query->count(),
                'per_page' => $query->count(),
                'current_page' => 1,
                'last_page' => 1,
            ])
            : response()->json($query->paginate($perPage));
    }

    public function getInstitutesByArea(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
        ]);

        $institutes = Institute::query()
            ->where('area_id', $request->input('area_id'))
            ->when($request->zamat_id, function ($query, $zamat_id) {
                return $query->whereHas('students', function ($query) use ($zamat_id) {
                    $query->where('zamat_id', $zamat_id);
                });
            })
            ->when($request->group_id, function ($query, $group_id) {
                return $query->whereHas('students', function ($query) use ($group_id) {
                    $query->where('group_id', $group_id);
                });
            })
            ->select('id', 'name', 'institute_code', 'phone')
            ->oldest('institute_code')
            ->get();

        return response()->json($institutes);
    }


    public function instituteCounts()
    {
        $totalInstitutesCount = Institute::count();
        $activeInstitutesCount = Institute::where('is_active', true)->count();
        $centerInstitutesCount = Institute::where('is_center', true)->count();

        return response()->json([
            'totalInstitutes' => $totalInstitutesCount,
            'activeInstitutes' => $activeInstitutesCount,
            'centerInstitutes' => $centerInstitutesCount,
        ]);
    }

    public function institutesWithApplications()
    {
        // applications টেবিলের মধ্য থেকে সর্বশেষ exam_id
        $latestExamId = Application::max('exam_id');

        if (!$latestExamId) {
            return response()->json([]); // কোন application নেই
        }

        // 1) সর্বশেষ পরীক্ষার জন্য institute+zamat লেভেলে স্টুডেন্ট কাউন্ট
        $aggByInstitute = Application::query()
            ->join('zamats', 'zamats.id', '=', 'applications.zamat_id')
            ->where('applications.exam_id', $latestExamId)
            ->selectRaw("
            applications.institute_id,
            applications.zamat_id,
            zamats.name as zamat_name,
            SUM(COALESCE(JSON_LENGTH(applications.students), 0)) as students_count
        ")
            ->groupBy('applications.institute_id', 'applications.zamat_id', 'zamats.name')
            ->get()
            ->groupBy('institute_id'); // → [institute_id => [rows...]]

        // 2) ফোন-ভ্যালিডেশনসহ ইনস্টিটিউট লিস্ট (আপনার আগের ফিল্টারগুলো অপরিবর্তিত)
        $institutes = Institute::query()
            ->whereHas('applications', function ($q) use ($latestExamId) {
                $q->where('exam_id', $latestExamId);
            })
            ->whereNotNull('phone')
            // ->whereRaw('LENGTH(phone) = 11')
            ->select('id', 'name', 'phone', 'institute_code')
            ->oldest('institute_code')
            ->get();

        // 3) ম্যাপ করে কাঙ্ক্ষিত JSON স্ট্রাকচার বানানো
        $rows = $institutes->map(function ($inst) use ($aggByInstitute) {
            $zRows = collect($aggByInstitute->get($inst->id, collect()))
                ->map(function ($r) {
                    return [
                        'zamat_id'   => (int) $r->zamat_id,
                        'zamat_name' => (string) $r->zamat_name,
                        'students'   => (int) $r->students_count,
                    ];
                })
                // ✅ জামাতের আইডি ছোট→বড় (বড়গুলো শেষে)
                ->sortBy('zamat_id', SORT_NUMERIC, false)
                ->values();

            return [
                'id'              => (int) $inst->id,
                'name'            => (string) $inst->name,
                'phone'           => (string) $inst->phone,
                'institute_code'  => (string) $inst->institute_code,
                'total_students'  => (int) $zRows->sum('students'),
                'zamats'          => $zRows, // [{ zamat_id, zamat_name, students }]
            ];
        });

        return response()->json($rows);
    }


    public function institutesWithoutApplications()
    {
        // applications টেবিলের মধ্য থেকে সর্বশেষ exam_id
        $latestExamId = Application::max('exam_id');

        if (!$latestExamId) {
            return response()->json([]); // কোন application নেই
        }

        $rows = Institute::query()
            ->whereDoesntHave('applications', function ($q) use ($latestExamId) {
                $q->where('exam_id', $latestExamId);
            })
            ->whereNotNull('phone')
            ->whereRaw('LENGTH(phone) = 11')
            ->select('id', 'name', 'phone', 'institute_code')
            ->oldest('institute_code')
            ->get();

        return response()->json($rows);
    }

    public function institutesWithValidPhone()
    {
        $institutes = Institute::whereNotNull('phone')
            // ->whereRaw('LENGTH(phone) = 11')
            ->select('id', 'name', 'phone', 'institute_code')
            ->oldest('institute_code')
            ->get();
        return response()->json($institutes);
    }

    public function institutesWithValidPhoneAndCenter()
    {
        $institutes = Institute::whereNotNull('phone')
            ->whereRaw('LENGTH(phone) = 11')
            ->where('is_center', true)
            ->select('id', 'name', 'phone', 'institute_code')
            ->oldest('institute_code')
            ->get();

        return response()->json($institutes);
    }

    public function institutesApplicationStatusCounts(Request $request)
    {
        $examId = $request->input('exam_id') ?? Exam::latest('id')->value('id');

        $institutesWithApplicationsCount = Institute::whereHas('applications', function ($q) use ($examId) {
            $q->where('exam_id', $examId);
        })->count();

        $institutesWithoutApplicationsCount = Institute::whereDoesntHave('applications', function ($q) use ($examId) {
            $q->where('exam_id', $examId);
        })->count();

        return response()->json([
            'exam_id' => $examId,
            'withApplications' => $institutesWithApplicationsCount,
            'withoutApplications' => $institutesWithoutApplicationsCount,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        $area = Area::findOrFail($request->area_id);

        $maxInstituteCode = Institute::where('area_id', $area->id)->max('institute_code');

        $newInstituteSerial = $maxInstituteCode ? (int)substr($maxInstituteCode, -3) + 1 : 1;

        $newInstituteCode = $area->area_code . str_pad($newInstituteSerial, 3, '0', STR_PAD_LEFT);

        $institute = Institute::create([
            'name' => $validatedData['name'],
            'phone' => $validatedData['phone'],
            'area_id' => $validatedData['area_id'],
            'institute_code' => $newInstituteCode,
            'is_active' => $validatedData['is_active'] ?? 0,
            'is_center' => $validatedData['is_center'] ?? 0,
        ]);

        return response()->json($institute, 201);
    }


    public function show(string $id)
    {
        $institute = Institute::findOrFail($id);

        return response()->json($institute);
    }

    public function instituteByCode(string $institute_code)
    {
        $institute = Institute::query()
            ->with([
                'area:id,name,area_code',
            ])
            ->where('institute_code', $institute_code)
            ->firstOrFail();

        return response()->json($institute);
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'phone' => 'max:255',
            'area_id' => 'exists:areas,id',
            'is_active' => 'boolean',
            'is_center' => 'boolean',
        ]);

        $institute = Institute::findOrFail($id);
        $institute->update($validatedData);

        return response()->json($institute);
    }

    public function destroy($id)
    {
        $institute = Institute::findOrFail($id);
        $institute->delete();

        return response()->json(null, 204);
    }
}
