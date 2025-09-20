<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        Status::truncate();

        $statuses = [
            [
                "id" => 3,
                "name" => "To Do",
                "slug" => "to-do",
                "color" => "#3B82F6",
                "order" => 0,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:35:07",
                "updated_at" => "2025-09-20 15:35:56",
                "is_completed" => false,
            ],
            [
                "id" => 1,
                "name" => "In Progress",
                "slug" => "in-progress",
                "color" => "#3B82F6",
                "order" => 1,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:34:24",
                "updated_at" => "2025-09-20 15:35:56",
                "is_completed" => false,
            ],
            [
                "id" => 4,
                "name" => "Ready For Test",
                "slug" => "ready-for-test",
                "color" => "#174896",
                "order" => 2,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:35:31",
                "updated_at" => "2025-09-20 15:36:03",
                "is_completed" => false,
            ],
            [
                "id" => 5,
                "name" => "Ready For UAT",
                "slug" => "ready-for-uat",
                "color" => "#3B82F6",
                "order" => 3,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:35:39",
                "updated_at" => "2025-09-20 15:36:03",
                "is_completed" => false,
            ],
            [
                "id" => 6,
                "name" => "UAT",
                "slug" => "uat",
                "color" => "#3B82F6",
                "order" => 4,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:35:43",
                "updated_at" => "2025-09-20 15:36:03",
                "is_completed" => false,
            ],
            [
                "id" => 7,
                "name" => "Done",
                "slug" => "done",
                "color" => "#3B82F6",
                "order" => 5,
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "created_at" => "2025-09-20 15:35:48",
                "updated_at" => "2025-09-20 15:36:03",
                "is_completed" => false,
            ],
        ];

        Status::insert($statuses);
    }
}
