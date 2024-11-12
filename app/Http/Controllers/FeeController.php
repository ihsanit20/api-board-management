<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FeeController extends Controller
{
    public function index()
    {
        $fees = Fee::with(['exam:id,name'])->get();

        return response()->json($fees, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_amounts' => 'required|array',
            'zamat_amounts.*.zamat_id' => 'nullable|exists:zamats,id',
            'zamat_amounts.*.amount' => 'required|integer|min:0',
            'zamat_amounts.*.late_fee' => 'nullable|integer|min:0',
            'last_date' => 'nullable|date',
            'final_date' => 'nullable|date',
        ]);

        $fee = Fee::create([
            'exam_id' => $validatedData['exam_id'],
            'zamat_amounts' => $validatedData['zamat_amounts'],
            'last_date' => $validatedData['last_date'] ?? null,
            'final_date' => $validatedData['final_date'] ?? null,
        ]);

        return response()->json($fee, Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $fee = Fee::with(['exam'])->findOrFail($id);

        return response()->json($fee, Response::HTTP_OK);
    }


    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_amounts' => 'required|array',
            'zamat_amounts.*.zamat_id' => 'nullable|exists:zamats,id',
            'zamat_amounts.*.amount' => 'required|integer|min:0',
            'zamat_amounts.*.late_fee' => 'nullable|integer|min:0',
            'last_date' => 'nullable|date',
            'final_date' => 'nullable|date',
        ]);

        $fee = Fee::findOrFail($id);

        $fee->update([
            'exam_id' => $validatedData['exam_id'],
            'zamat_amounts' => $validatedData['zamat_amounts'],
            'last_date' => $validatedData['last_date'] ?? null,
            'final_date' => $validatedData['final_date'] ?? null,
        ]);

        return response()->json($fee, Response::HTTP_OK);
    }

    public function destroy(string $id)
    {
        $fee = Fee::findOrFail($id);
        $fee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
