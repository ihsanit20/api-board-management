<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;

class LastExamResultController extends Controller
{
    public function data(Request $request)
    {
        $lastExamId = Exam::max('id');

        $centerId = $request->query('center_id');
        $zamatId = $request->query('zamat_id');

        $students = Student::query()
            ->with([
                'zamat:id,name'
            ])
            ->where('exam_id', $lastExamId)
            ->where('center_id', $centerId)
            ->where('zamat_id', $zamatId)
            ->get([
                'id',
                'name',
                'exam_id',
                'zamat_id',
                'center_id',
                'institute_id',
            ]);
        
        $results = Result::query()
            ->where('exam_id', $lastExamId)
            ->where('zamat_id', $zamatId)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        return response()->json(compact("students", "results"));
    }

    public function index(Request $request)
    {
        $lastExamId = Exam::max('id');

        $centerId = $request->query('center_id');
        $zamatId = $request->query('zamat_id');
        $paraGroupId = $request->query('para_group_id');

        $results = Result::query()
            ->where('exam_id', $lastExamId)
            ->where('center_id', $centerId)
            ->where('zamat_id', $zamatId)
            ->get();

        return response()->json($results);
    }
}
