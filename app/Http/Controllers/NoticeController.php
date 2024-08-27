<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index()
    {
        return Notice::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'publish_date' => 'required|date',
        ]);

        $notice = Notice::create($request->all());

        return response()->json($notice, 201);
    }

    public function show($id)
    {
        return Notice::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);
        $notice->update($request->all());

        return response()->json($notice, 200);
    }

    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json(null, 204);
    }
}

