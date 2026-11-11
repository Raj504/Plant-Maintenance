<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return $this->page(new Team());
    }

    public function create()
    {
        return redirect()->route('admin.teams.index');
    }

    public function store(Request $request)
    {
        $team = Team::create($this->validated($request));
        $team->members()->sync($request->input('members', []));
        flash_toast('success', 'Team added.');
        return back();
    }

    public function edit(Team $team)
    {
        return $this->page($team->load('members'));
    }

    public function update(Request $request, Team $team)
    {
        $team->update($this->validated($request, $team));
        $team->members()->sync($request->input('members', []));
        flash_toast('success', 'Team updated.');
        return redirect()->route('admin.teams.index');
    }

    public function destroy(Team $team)
    {
        $team->update(['is_active' => false]);
        flash_toast('success', 'Team deactivated.');
        return back();
    }

    protected function page(Team $team)
    {
        return view('admin.masters.teams', ['teams' => Team::with(['lead', 'members'])->orderBy('name')->get(), 'team' => $team, 'users' => User::where('is_active', true)->orderBy('name')->get()]);
    }

    protected function validated(Request $request, ?Team $team = null): array
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:pm_teams,name,' . ($team?->id ?? 'NULL'), 'lead_user_id' => 'nullable|exists:users,id', 'description' => 'nullable|string|max:255', 'members' => 'nullable|array', 'members.*' => 'exists:users,id']);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['members']);
        return $data;
    }
}
