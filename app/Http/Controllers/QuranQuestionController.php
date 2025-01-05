<?php

namespace App\Http\Controllers;

use App\Models\QuranQuestion;
use Illuminate\Http\Request;

class QuranQuestionController extends Controller
{
    public function index(Request $request)
    {
        $lastExamId = QuranQuestion::max('exam_id');
        $centerId = $request->query('center_id');
        $zamatId = $request->query('zamat_id');

        if (!$centerId || !$zamatId) {
            return response()->json(['error' => 'center_id and zamat_id are required'], 400);
        }

        $quranQuestions = QuranQuestion::where('exam_id', $lastExamId)
            ->where('center_id', $centerId)
            ->where('zamat_id', $zamatId)
            ->get();

        return response()->json($quranQuestions);
    }

    public function store(Request $request)
    {
        $lastExamId = QuranQuestion::max('exam_id') ?? 22;

        $validatedData = $request->validate([
            'center_id' => 'nullable|exists:institutes,id',
            'zamat_id' => 'required|integer',
            'questions' => 'required|array',
            'questions.*.surah' => 'required|integer',
            'questions.*.verses' => 'required|string',
            'questions.*.text' => 'required|string',
            'questions.*.page' => 'required|integer',
        ]);

        // Save the data as JSON in the `questions` field
        QuranQuestion::create([
            'exam_id' => $lastExamId,
            'center_id' => $validatedData['center_id'],
            'zamat_id' => $validatedData['zamat_id'],
            'questions' => $validatedData['questions'], // JSON data
        ]);

        return response()->json(['message' => 'Questions saved successfully'], 201);
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
            'center_id' => 'nullable|exists:institutes,id',
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