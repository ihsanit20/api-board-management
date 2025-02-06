<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSubject;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Zamat;
use Illuminate\Http\Request;

class LastExamResultController extends Controller
{
    public function data(Request $request)
    {
        $lastExamId = Exam::max('id');

        $centerId = $request->query('center_id');
        $zamatId = $request->query('zamat_id');
        $examSubjectId = $request->query('exam_subject_id');

        $students = Student::query()
            ->where('exam_id', $lastExamId)
            ->where('center_id', $centerId)
            ->where('zamat_id', $zamatId)
            ->whereNotNull('roll_number')
            ->orderBy('roll_number')
            ->get([
                'id',
                'name',
                'roll_number',
                'exam_id',
                'zamat_id',
                'center_id',
                'institute_id',
            ]);

        $results = Result::query()
            ->where('exam_id', $lastExamId)
            ->where('zamat_id', $zamatId)
            ->whereIn('student_id', $students->pluck('id'))
            ->when($examSubjectId, function ($query, $examSubjectId) {
                $query->where('exam_subject_id', $examSubjectId);
            })
            ->get();

        $exam_subjects = ExamSubject::query()
            ->with([
                'subject:id,name,code,zamat_id',
                'subject.zamat:id,name',
            ])
            ->whereHas('subject', function ($query) use ($zamatId, $examSubjectId) {
                $query
                    ->where('zamat_id', $zamatId)
                    ->when($examSubjectId, function ($query, $examSubjectId) {
                        $query->where('exam_subjects.id', $examSubjectId);
                    });
            })
            ->get([
                "id",
                "exam_id",
                "subject_id",
                "full_marks",
                "pass_marks",
            ]);

        return response()->json(compact("students", "results", "exam_subjects"));
    }

    public function submitMarks(Request $request, Zamat $zamat, ExamSubject $examSubject)
    {
        $lastExamId = Exam::max('id');

        $request->validate([
            'marks' => 'required|array'
        ]);

        $results = [];

        foreach ($request->marks as $student_id => $marks) {
            // return
            $validMarks = [
                isset($marks[0]) && $marks[0] >= 0 ? (float) $marks[0] : '',
                isset($marks[1]) && $marks[1] >= 0 ? (float) $marks[1] : '',
            ];

            $filteredMarks = array_filter($marks, fn($mark) => $mark !== null);

            $averageMark = count($filteredMarks) > 0 ? array_sum($filteredMarks) / count($filteredMarks) : 0;

            $result = Result::updateOrCreate(
                [
                    'exam_id'    => $lastExamId,
                    'zamat_id'   => $zamat->id,
                    'exam_subject_id' => $examSubject->id,
                    'student_id' => $student_id,
                ],
                [
                    'mark'  => $averageMark,
                    'marks' => $validMarks,
                ]
            );

            $results[] = $result;
        }

        return response()->json([
            'message' => 'Marks submitted successfully!',
            'results' => $results
        ], 200);
    }


    public function index(Request $request)
    {
        $lastExamId = Exam::max('id');

        $results = Result::query()
            ->where('exam_id', $lastExamId)
            ->when($request->zamat_id, function ($query, $zamat_id) {
                $query->where('zamat_id', $zamat_id);
            })
            ->when($request->exam_subject_id, function ($query, $exam_subject_id) {
                $query->where('exam_subject_id', $exam_subject_id);
            })
            ->when($request->student_id, function ($query, $student_id) {
                $query->where('student_id', $student_id);
            })
            ->get();

        return response()->json($results);
    }
}
