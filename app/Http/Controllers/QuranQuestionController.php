<?php

namespace App\Http\Controllers;

use App\Models\QuranQuestion;
use Illuminate\Http\Request;

class QuranQuestionController extends Controller
{
    public function index()
    {
        $lastExamId = QuranQuestion::max('exam_id');
        $quranQuestions = QuranQuestion::where('exam_id', $lastExamId)->get();

        return response()->json($quranQuestions);
    }

    public function store(Request $request)
    {
        $lastExamId = QuranQuestion::max('exam_id');

        $validatedData = $request->validate([
            'center_id' => 'required|integer',
            'zamat_id' => 'required|integer',
            'questions' => 'required|array',
            'questions.*.surah' => 'required|integer',
            'questions.*.verses' => 'required|string',
            'questions.*.text' => 'required|string',
            'questions.*.page' => 'required|integer',
        ]);

        $validatedData['exam_id'] = $lastExamId;

        $quranQuestion = QuranQuestion::create($validatedData);

        return response()->json($quranQuestion, 201);
    }

    public function show($id)
    {
        $quranQuestion = QuranQuestion::find($id);

        if (!$quranQuestion) {
            return response()->json(['error' => 'Quran question not found'], 404);
        }

        return response()->json($quranQuestion);
    }

    public function update(Request $request, $id)
    {
        $quranQuestion = QuranQuestion::find($id);

        if (!$quranQuestion) {
            return response()->json(['error' => 'Quran question not found'], 404);
        }

        $validatedData = $request->validate([
            'center_id' => 'sometimes|required|integer',
            'zamat_id' => 'sometimes|required|integer',
            'questions' => 'sometimes|required|array',
            'questions.*.surah' => 'sometimes|required|integer',
            'questions.*.verses' => 'sometimes|required|string',
            'questions.*.text' => 'sometimes|required|string',
            'questions.*.page' => 'sometimes|required|integer',
        ]);

        $quranQuestion->update($validatedData);

        return response()->json($quranQuestion);
    }

    public function destroy($id)
    {
        $quranQuestion = QuranQuestion::find($id);

        if (!$quranQuestion) {
            return response()->json(['error' => 'Quran question not found'], 404);
        }

        $quranQuestion->delete();

        return response()->json(['message' => 'Quran question deleted successfully']);
    }
}
