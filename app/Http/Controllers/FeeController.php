<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all fees with related exam and zamat data
        $fees = Fee::with(['exam', 'zamat'])->get();

        return response()->json($fees, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_id' => 'nullable|exists:zamats,id',
            'amount' => 'required|integer|min:0',
            'ext_amount' => 'required|integer|min:0',
        ]);

        // Create a new fee entry
        $fee = Fee::create($validatedData);

        return response()->json($fee, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Find the fee by ID and include related exam and zamat data
        $fee = Fee::with(['exam', 'zamat'])->findOrFail($id);

        return response()->json($fee, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_id' => 'nullable|exists:zamats,id',
            'amount' => 'required|integer|min:0',
            'ext_amount' => 'required|integer|min:0',
        ]);

        // Find the fee by ID and update it
        $fee = Fee::findOrFail($id);
        $fee->update($validatedData);

        return response()->json($fee, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the fee by ID and delete it
        $fee = Fee::findOrFail($id);
        $fee->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
