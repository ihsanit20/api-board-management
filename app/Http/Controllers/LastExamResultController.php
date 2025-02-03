<?php

namespace App\Http\Controllers;

use App\Models\Exam;
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
        $subjectId = $request->query('subject_id');

        $students = Student::query()
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
            ->when($subjectId, function ($query, $subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->get();

        $subjects = Subject::query()
            ->where('zamat_id', $zamatId)
            ->when($subjectId, function ($query, $subjectId) {
                $query
                    ->with('zamat:id,name')
                    ->where('id', $subjectId)
                ;
            })
            ->get([
                "id",
                "zamat_id",
                "name",
                "code",
            ]);

        return response()->json(compact("students", "results", "subjects"));
    }

    public function submitMarks(Request $request, Zamat $zamat, Subject $subject)
    {
        $lastExamId = Exam::max('id');
    
        $request->validate([
            'marks' => 'required|array'
        ]);
    
        $results = [];
    
        foreach ($request->marks as $student_id => $marks) {
            // শুধুমাত্র ফাঁকা না থাকা নম্বর সংগ্রহ করুন
            $validMarks = array_filter([
                isset($marks['examiner1']) && $marks['examiner1'] !== '' ? (float) $marks['examiner1'] : null,
                isset($marks['examiner2']) && $marks['examiner2'] !== '' ? (float) $marks['examiner2'] : null
            ], fn($mark) => $mark !== null);
    
            // গড় নির্ণয় (যদি কোনো নম্বর থাকে)
            $averageMark = count($validMarks) > 0 ? array_sum($validMarks) / count($validMarks) : 0;
    
            // ডাটাবেজ আপডেট বা তৈরি করুন
            $result = Result::updateOrCreate(
                [
                    'exam_id'    => $lastExamId,
                    'zamat_id'   => $zamat->id,
                    'subject_id' => $subject->id,
                    'student_id' => $student_id,
                ],
                [
                    'mark'  => $averageMark, // গড় নম্বর সংরক্ষণ করা হবে
                    'marks' => $marks, // মূল নম্বরগুলো JSON আকারে সংরক্ষণ করা হবে
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
            ->when($request->subject_id, function ($query, $subject_id) {
                $query->where('subject_id', $subject_id);
            })
            ->when($request->student_id, function ($query, $student_id) {
                $query->where('student_id', $student_id);
            })
            ->get();

        return response()->json($results);
    }
}
