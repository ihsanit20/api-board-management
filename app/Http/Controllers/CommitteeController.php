<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use Illuminate\Http\Request;

class CommitteeController extends Controller
{
    // GET committees
    public function index(Request $request)
    {
        $q = Committee::query()
            ->withCount('members');

        if (!is_null($request->get('active'))) {
            $q->where('is_active', $request->boolean('active'));
        }

        $q->orderBy('id');

        return $q->get([
            'id',
            'name',
            'session',
            'start_date',
            'end_date',
            'is_active',
            'notes',
            'created_at',
            'updated_at',
        ]);
    }


    // POST committees
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'session'    => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'boolean',
            'notes'      => 'nullable|string',
        ]);

        $committee = Committee::create($data);
        return response()->json($committee->load('members'), 201);
    }

    // GET committees/{committee}
    public function show(Committee $committee)
    {
        return $committee->load('members');
    }

    // PUT/PATCH committees/{committee}
    public function update(Request $request, Committee $committee)
    {
        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'session'    => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'is_active'  => 'boolean',
            'notes'      => 'nullable|string',
        ]);

        $committee->update($data);
        return $committee->load('members');
    }

    // DELETE committees/{committee}
    public function destroy(Committee $committee)
    {
        $committee->delete();
        return response()->noContent();
    }
}