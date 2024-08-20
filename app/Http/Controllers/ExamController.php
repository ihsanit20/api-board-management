<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $exams = Exam::all();
        return response()->json($exams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:exams,name',
            'reg_last_date' => 'required|date',
            'reg_final_date' => 'required|date|after_or_equal:reg_last_date',
        ]);

        $exam = Exam::create($request->all());
        return response()->json($exam, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exam = Exam::findOrFail($id);
        return response()->json($exam);
    }

    /**
     * Display the specified resource.
     */
    public function showLast()
    {
        $exam = Exam::latest('id')->first();
        return response()->json($exam);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $exam = Exam::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|unique:exams,name,' . $exam->id,
            'reg_last_date' => 'sometimes|required|date',
            'reg_final_date' => 'sometimes|required|date|after_or_equal:reg_last_date',
        ]);

        $exam->update($request->all());
        return response()->json($exam);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();

        return response()->json(null, 204);
    }
}
