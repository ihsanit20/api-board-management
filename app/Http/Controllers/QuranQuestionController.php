<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\QuranQuestion;
use Illuminate\Http\Request;

class QuranQuestionController extends Controller
{
    public function index(Request $request)
    {
        $lastExamId = QuranQuestion::max('exam_id');
        $centerId = $request->query('center_id');
        $zamatId = $request->query('zamat_id');
        $paraGroupId = $request->query('para_group_id');

        // Include department relationship through zamat
        $query = QuranQuestion::with(['center.institute', 'zamat.department', 'paraGroup'])
            ->where('exam_id', $lastExamId);

        if ($centerId) {
            $query->where('center_id', $centerId);
        }
        if ($zamatId) {
            $query->where('zamat_id', $zamatId);
        }
        if ($paraGroupId) {
            $query->where('para_group_id', $paraGroupId);
        }

        $quranQuestions = $query->get();

        return response()->json($quranQuestions);
    }



    public function store(Request $request)
    {
        $lastExam = Exam::latest('id')->first();
        $lastExamId = $lastExam ? $lastExam->id : null;

        $validatedData = $request->validate([
            'center_id' => 'nullable|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'para_group_id' => 'nullable|exists:para_groups,id',
            'questions' => 'required|array',
            'questions.*.surah' => 'required|integer',
            'questions.*.verses' => 'required|string',
            'questions.*.text' => 'required|string',
            'questions.*.page' => 'nullable|integer',
        ]);

        QuranQuestion::create([
            'exam_id' => $lastExamId,
            'center_id' => $validatedData['center_id'],
            'zamat_id' => $validatedData['zamat_id'],
            'para_group_id' => $validatedData['para_group_id'],
            'questions' => $validatedData['questions'],
        ]);

        return response()->json(['message' => 'Questions saved successfully'], 201);
    }


    public function show($id)
    {
        $quranQuestion = QuranQuestion::with(['center.institute', 'zamat', 'paraGroup'])->find($id);

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
            'zamat_id' => 'sometimes|required|exists:zamats,id',
            'para_group_id' => 'nullable|exists:para_groups,id', // নতুন ফিল্ড
            'questions' => 'sometimes|required|array',
            'questions.*.surah' => 'sometimes|required|integer',
            'questions.*.verses' => 'sometimes|required|string',
            'questions.*.text' => 'sometimes|required|string',
            'questions.*.page' => 'nullable|integer',
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

        return response()->json(['message' => 'Quran question deleted successfully.']);
    }
}
