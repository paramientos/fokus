<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    /**
     * Proje üyelerini görüntüle
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return view('projects.members.index', [
            'project' => $project->load('teamMembers'),
        ]);
    }

    /**
     * Yeni üye davet etme formunu göster
     */
    public function create(Project $project)
    {
        $this->authorize('update', $project);

        $users = User::whereNotIn('id', $project->teamMembers->pluck('id'))
            ->get();

        return view('projects.members.create', [
            'project' => $project,
            'users' => $users,
        ]);
    }

    /**
     * Projeye yeni üye ekle
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member,viewer',
        ]);

        // Kullanıcı zaten projede mi kontrol et
        if ($project->teamMembers()->where('user_id', $validated['user_id'])->exists()) {
            return redirect()
                ->route('projects.members.index', $project)
                ->with('error', 'Bu kullanıcı zaten projenin bir üyesi.');
        }

        // Kullanıcıyı projeye ekle
        $project->teamMembers()->attach($validated['user_id'], [
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', 'Kullanıcı projeye başarıyla eklendi.');
    }

    /**
     * Üye rolünü düzenleme formunu göster
     */
    public function edit(Project $project, User $member)
    {
        $this->authorize('update', $project);

        // Kullanıcının projede olduğunu kontrol et
        $membership = $project->teamMembers()
            ->where('user_id', $member->id)
            ->first();

        if (!$membership) {
            return redirect()
                ->route('projects.members.index', $project)
                ->with('error', 'Bu kullanıcı projenin bir üyesi değil.');
        }

        return view('projects.members.edit', [
            'project' => $project,
            'member' => $member,
            'role' => $membership->pivot->role,
        ]);
    }

    /**
     * Üye rolünü güncelle
     */
    public function update(Request $request, Project $project, User $member)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'role' => 'required|in:admin,member,viewer',
        ]);

        // Kullanıcının projede olduğunu kontrol et
        if (!$project->teamMembers()->where('user_id', $member->id)->exists()) {
            return redirect()
                ->route('projects.members.index', $project)
                ->with('error', 'Bu kullanıcı projenin bir üyesi değil.');
        }

        // Kullanıcı rolünü güncelle
        $project->teamMembers()->updateExistingPivot($member->id, [
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', 'Üye rolü başarıyla güncellendi.');
    }

    /**
     * Üyeyi projeden çıkar
     */
    public function destroy(Project $project, User $member)
    {
        $this->authorize('update', $project);

        // Proje sahibini çıkaramayız
        if ($project->user_id === $member->id) {
            return redirect()
                ->route('projects.members.index', $project)
                ->with('error', 'Proje sahibi projeden çıkarılamaz.');
        }

        // Kullanıcıyı projeden çıkar
        $project->teamMembers()->detach($member->id);

        return redirect()
            ->route('projects.members.index', $project)
            ->with('success', 'Üye projeden başarıyla çıkarıldı.');
    }
}
