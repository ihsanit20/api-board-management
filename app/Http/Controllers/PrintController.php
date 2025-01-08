<?php

namespace App\Http\Controllers;

use App\Models\Center;
use App\Models\ExamSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Zamat;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function generateMarkSheet(Request $request)
    {
        $validated = $request->validate([
            'center_id' => 'required|exists:centers,id',
            'zamat_id' => 'required|exists:zamats,id',
        ]);

        // Fetch Centers and Zamats
        $center = Center::findOrFail($validated['center_id']);
        $zamat = Zamat::findOrFail($validated['zamat_id']);

        // Fetch last exam ID for the given Zamat
        $lastExamId = ExamSubject::whereHas('subject', function ($query) use ($validated) {
            $query->where('zamat_id', $validated['zamat_id']);
        })->latest()->pluck('exam_id')->first();

        if (!$lastExamId) {
            return response()->json(['message' => 'No exams found for this Zamat.'], 404);
        }

        // Fetch students with roll numbers and associated subjects
        $students = Student::where('center_id', $validated['center_id'])
            ->where('zamat_id', $validated['zamat_id'])
            ->whereNotNull('roll_number')
            ->get();

        $subjects = Subject::where('zamat_id', $validated['zamat_id'])->get();

        $examSubjects = ExamSubject::where('exam_id', $lastExamId)
            ->with('subject')
            ->get();

        return response()->json([
            'center' => $center,
            'zamat' => $zamat,
            'students' => $students,
            'subjects' => $examSubjects->map(function ($examSubject) {
                return [
                    'name' => $examSubject->subject->name,
                    'full_marks' => $examSubject->full_marks,
                ];
            }),
            'exam_id' => $lastExamId,
        ]);
    }

}
