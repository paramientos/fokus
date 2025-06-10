<?php

namespace Database\Seeders;

use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        Workspace::create([
            'id' => 1,
            'name' => 'Ana Çalışma Alanı',
            'description' => 'Varsayılan çalışma alanı',
            'created_by' => 1,
            'owner_id' => 1
        ]);
    }
}
