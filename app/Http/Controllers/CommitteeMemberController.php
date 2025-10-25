<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteeMember;
use Illuminate\Http\Request;

class CommitteeMemberController extends Controller
{
    // GET committees/{committee}/members
    public function index(Committee $committee)
    {
        return $committee->members()->get(); // ordered by 'order'
    }

    public function publicAllByCommittee()
    {
        $committees = Committee::with([
            'members' => function ($q) {
                $q->orderBy('order');
                $q->where('is_active', true);
            }
        ])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return response()->json($committees);
    }

    // POST committees/{committee}/members
    public function store(Request $request, Committee $committee)
    {
        $data = $request->validate([
            'user_id'     => 'nullable|integer',
            'name'        => 'required|string|max:255',
            'designation' => 'required|string|max:120',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:120',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['committee_id'] = $committee->id;

        if (!array_key_exists('order', $data) || is_null($data['order'])) {
            $max = CommitteeMember::where('committee_id', $committee->id)->max('order');
            $data['order'] = is_null($max) ? 1 : $max + 1;
        }

        $member = CommitteeMember::create($data);
        return response()->json($member, 201);
    }


    // GET members/{member}
    public function show(CommitteeMember $member)
    {
        return $member;
    }

    public function update(Request $request, CommitteeMember $member)
    {
        $data = $request->validate([
            'user_id'     => 'nullable|integer',
            'name'        => 'sometimes|required|string|max:255',
            'designation' => 'sometimes|required|string|max:120',
            'phone'       => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:120',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        if (array_key_exists('order', $data) && is_null($data['order'])) {
            $data['order'] = $member->order ?? 0;
        }

        $member->update($data);
        return $member;
    }

    public function destroy(CommitteeMember $member)
    {
        $member->delete();
        return response()->noContent();
    }

    // POST committees/{committee}/members/reorder
    public function reorder(Request $request, Committee $committee)
    {
        $payload = $request->validate([
            'orders' => 'required|array',
            'orders.*.id'    => 'required|integer|exists:committee_members,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($payload['orders'] as $item) {
            CommitteeMember::where('id', $item['id'])
                ->where('committee_id', $committee->id)
                ->update(['order' => $item['order']]);
        }

        return $committee->members()->get();
    }
}
