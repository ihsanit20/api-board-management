<?php

namespace App\Http\Controllers;

use App\Models\ExamSubject;
use Illuminate\Http\Request;

class ExamSubjectController extends Controller
{
    public function index()
    {
        return ExamSubject::with(['exam', 'subject'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'total_marks' => 'required|integer',
            'pass_marks' => 'required|integer',
        ]);

        return ExamSubject::create($request->all());
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
            'total_marks' => 'integer',
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
