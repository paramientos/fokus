<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tüm projeleri al
        $projects = Project::all();

        // Tüm kullanıcıları al
        $users = User::all();

        foreach ($projects as $project) {
            // Proje sahibini admin olarak ekle
            $project->teamMembers()->attach($project->user_id, [
                'role' => 'admin',
                'id' => Str::uuid(),
            ]);

            // Rastgele 2-5 kullanıcıyı takım üyesi olarak ekle
            $randomUsers = $users->where('id', '!=', $project->user_id)
                ->random(min(rand(2, 5), $users->where('id', '!=', $project->user_id)->count()));

            foreach ($randomUsers as $user) {
                $project->teamMembers()->attach($user->id, [
                    'role' => 'member',
                    'id' => Str::uuid(),
                ]);
            }
        }
    }
}
