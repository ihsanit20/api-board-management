<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\MeritPrice;
use Illuminate\Http\Request;

class MeritPriceController extends Controller
{
    // 📥 Store default ৪টি merit price entry
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'zamat_id' => 'required|exists:zamats,id',
            'merit_names' => 'required|array|size:4',
            'price_amounts' => 'required|array|size:4',
        ]);

        foreach ($request->merit_names as $index => $merit_name) {
            MeritPrice::updateOrCreate(
                [
                    'exam_id' => $request->exam_id,
                    'zamat_id' => $request->zamat_id,
                    'merit_name' => $merit_name,
                ],
                [
                    'price_amount' => $request->price_amounts[$index],
                ]
            );
        }

        return response()->json(['message' => 'Merit prices saved successfully.']);
    }

    // 🔁 Update individual merit price
    public function update(Request $request, MeritPrice $meritPrice)
    {
        $request->validate([
            'price_amount' => 'required|integer|min:0',
        ]);

        $meritPrice->update([
            'price_amount' => $request->price_amount,
        ]);

        return response()->json(['message' => 'Merit price updated successfully.']);
    }

    // 📋 Index: merit prices list by exam_id (optional)
    public function index(Request $request)
    {
        $examId = $request->input('exam_id');

        if ($examId) {
            $request->validate([
                'exam_id' => 'exists:exams,id',
            ]);
        } else {
            $lastExam = Exam::latest()->first();
            $examId = $lastExam?->id;
        }

        $meritPrices = MeritPrice::where('exam_id', $examId)
            ->with('zamat') // optional if you want zamat details
            ->get();

        return response()->json([
            'exam_id' => $examId,
            'merit_prices' => $meritPrices,
        ]);
    }
}
