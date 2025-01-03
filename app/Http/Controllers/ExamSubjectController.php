<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSubject;
use Illuminate\Http\Request;

class ExamSubjectController extends Controller
{
    public function index()
    {
        $latestExam = Exam::latest('id')->first();

        if (!$latestExam) {
            return response()->json(['message' => 'No exams found'], 404);
        }

        return ExamSubject::with(['exam', 'subject'])
            ->where('exam_id', $latestExam->id)
            ->get();
    }

    public function store(Request $request)
    {
        $latestExam = Exam::latest('id')->first();

        if (!$latestExam) {
            return response()->json(['message' => 'No exams found to associate'], 404);
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id|unique:exam_subjects,subject_id,NULL,id,exam_id,' . $latestExam->id,
            'full_marks' => 'required|integer',
            'pass_marks' => 'required|integer',
        ]);

        $examSubject = ExamSubject::create([
            'exam_id' => $latestExam->id,
            'subject_id' => $request->subject_id,
            'full_marks' => $request->full_marks,
            'pass_marks' => $request->pass_marks,
        ]);

        return $examSubject;
    }


    public function show($id)
    {
        return ExamSubject::with(['exam', 'subject'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $examSubject = ExamSubject::findOrFail($id);

        $request->validate([
            'exam_id' => 'exists:exams,id',
            'subject_id' => 'exists:subjects,id',
            'full_marks' => 'integer',
            'pass_marks' => 'integer',
        ]);

        $examSubject->update($request->all());
        return $examSubject;
    }

    public function destroy($id)
    {
        $examSubject = ExamSubject::findOrFail($id);
        $examSubject->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
