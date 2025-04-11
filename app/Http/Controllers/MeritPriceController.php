<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\MeritPrice;
use Illuminate\Http\Request;

class MeritPriceController extends Controller
{
    // 📥 Store default ৪টি merit price entry
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_id' => 'required|exists:zamats,id',
            'merit_names' => 'required|array|size:4',
            'price_amounts' => 'required|array|size:4',
        ]);

        foreach ($request->merit_names as $index => $merit_name) {
            MeritPrice::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'zamat_id' => $request->zamat_id,
                    'merit_name' => $merit_name,
                ],
                [
                    'price_amount' => $request->price_amounts[$index],
                ]
            );
        }

        return response()->json(['message' => 'Merit prices saved successfully.']);
    }

    // 🔁 Update individual merit price
    public function update(Request $request, MeritPrice $meritPrice)
    {
        $request->validate([
            'price_amount' => 'required|integer|min:0',
        ]);

        $meritPrice->update([
            'price_amount' => $request->price_amount,
        ]);

        return response()->json(['message' => 'Merit price updated successfully.']);
    }

    // 📋 Index: merit prices list by exam_id (optional)
    public function index(Request $request)
    {
        $examId = $request->input('exam_id');

        if ($examId) {
            $request->validate([
                'exam_id' => 'exists:exams,id',
            ]);
        } else {
            $lastExam = Exam::latest()->first();
            $examId = $lastExam?->id;
        }

        $meritPrices = MeritPrice::where('exam_id', $examId)
            ->with('zamat') // optional if you want zamat details
            ->get();

        return response()->json([
            'exam_id' => $examId,
            'merit_prices' => $meritPrices,
        ]);
    }

    public function StudentMeritPrice(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'area_id' => 'required|exists:areas,id',
        ]);

        $exam_id = $request->exam_id;
        $area_id = $request->area_id;

        $students = \App\Models\Student::where('exam_id', $exam_id)
            ->where('area_id', $area_id)
            ->whereNotNull('merit')
            ->with(['institute:id,name', 'zamat:id,name', 'zamat.department'])
            ->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students with merit found'], 404);
        }

        // Group by institute
        $institutes = $students->groupBy('institute_id')->map(function ($instituteStudents, $institute_id) use ($exam_id) {
            $institute = optional($instituteStudents->first()->institute);

            // Group by zamat inside each institute
            $zamats = $instituteStudents->groupBy('zamat_id')->map(function ($zamatStudents, $zamat_id) use ($exam_id) {
                $zamat = optional($zamatStudents->first()->zamat);

                $studentsList = $zamatStudents->map(function ($student) use ($exam_id) {
                    // Merit → numeric only (remove suffix)
                    $numericMerit = (int) filter_var($student->merit, FILTER_SANITIZE_NUMBER_INT);
                    $merit_name = match ($numericMerit) {
                        1 => '১ম',
                        2 => '২য়',
                        3 => '৩য়',
                        default => 'অন্যান্য',
                    };

                    $meritPrice = MeritPrice::where('exam_id', $exam_id)
                        ->where('zamat_id', $student->zamat_id)
                        ->where('merit_name', $merit_name)
                        ->first();

                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'roll_number' => $student->roll_number,
                        'merit' => $student->merit,
                        'merit_name' => $merit_name,
                        'price_amount' => $meritPrice?->price_amount ?? 0,
                    ];
                })->values();

                return [
                    'id' => $zamat->id,
                    'name' => $zamat->name,
                    'department' => optional($zamat->department)->name,
                    'students' => $studentsList,
                ];
            })->values();

            return [
                'id' => $institute->id,
                'name' => $institute->name,
                'zamats' => $zamats,
            ];
        })->values();

        return response()->json([
            'exam' => [
                'id' => $exam_id,
                'name' => optional(Exam::find($exam_id))->name,
            ],
            'area' => [
                'id' => $area_id,
                'name' => optional(\App\Models\Area::find($area_id))->name,
            ],
            'institutes' => $institutes,
        ]);
    }
}
