<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tüm kullanıcıları al
        $users = User::all();

        // Örnek projeler oluştur
        $projects = [
            [
                'name' => 'Web Uygulaması',
                'key' => 'WEB',
                'description' => 'Şirket web uygulaması geliştirme projesi',
                'is_active' => true,
            ],
            [
                'name' => 'Mobil Uygulama',
                'key' => 'MOB',
                'description' => 'Mobil uygulama geliştirme projesi',
                'is_active' => true,
            ],
            [
                'name' => 'API Entegrasyonu',
                'key' => 'API',
                'description' => 'Üçüncü parti API entegrasyonları',
                'is_active' => true,
            ],
        ];

        foreach ($projects as $projectData) {
            // Rastgele bir kullanıcıyı proje sahibi olarak ata
            $user = $users->random();

            Project::create([
                'name' => $projectData['name'],
                'key' => $projectData['key'],
                'description' => $projectData['description'],
                'user_id' => $user->id,
                'is_active' => $projectData['is_active'],
            ]);
        }
    }
}
