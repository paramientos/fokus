<?php

namespace Database\Seeders;

use App\Models\StatusTransition;
use Illuminate\Database\Seeder;

class StatusTransitionSeeder extends Seeder
{
    public function run(): void
    {
        StatusTransition::truncate();

        $data = [
            [
                "id" => "019967c4-f6d0-731f-b711-262c0875c480",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 3,
                "to_status_id" => 1,
                "created_at" => "2025-09-20 15:36:25",
                "updated_at" => "2025-09-20 15:36:25",
            ],
            [
                "id" => "019967c5-02ce-72bd-b322-843356d60dd6",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 1,
                "to_status_id" => 3,
                "created_at" => "2025-09-20 15:36:28",
                "updated_at" => "2025-09-20 15:36:28",
            ],
            [
                "id" => "019967c5-06a0-722f-88f6-862c16957c92",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 1,
                "to_status_id" => 4,
                "created_at" => "2025-09-20 15:36:29",
                "updated_at" => "2025-09-20 15:36:29",
            ],
            [
                "id" => "019967c5-1139-73a6-a0be-88e8a4f479ce",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 4,
                "to_status_id" => 1,
                "created_at" => "2025-09-20 15:36:32",
                "updated_at" => "2025-09-20 15:36:32",
            ],
            [
                "id" => "019967c5-13af-71c9-bd89-21565f73e799",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 4,
                "to_status_id" => 5,
                "created_at" => "2025-09-20 15:36:32",
                "updated_at" => "2025-09-20 15:36:32",
            ],
            [
                "id" => "019967c5-1de9-7397-bcc4-9a887babc77e",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 5,
                "to_status_id" => 4,
                "created_at" => "2025-09-20 15:36:35",
                "updated_at" => "2025-09-20 15:36:35",
            ],
            [
                "id" => "019967c5-206b-7270-9f24-e0cdf3568a95",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 5,
                "to_status_id" => 6,
                "created_at" => "2025-09-20 15:36:36",
                "updated_at" => "2025-09-20 15:36:36",
            ],
            [
                "id" => "019967c5-3582-7058-adf7-df5107ca796e",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 5,
                "to_status_id" => 1,
                "created_at" => "2025-09-20 15:36:41",
                "updated_at" => "2025-09-20 15:36:41",
            ],
            [
                "id" => "019967c5-4f3e-7161-a34c-b2dd7a1cc7d2",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 6,
                "to_status_id" => 5,
                "created_at" => "2025-09-20 15:36:48",
                "updated_at" => "2025-09-20 15:36:48",
            ],
            [
                "id" => "019967c5-5cea-7391-bfb3-8666e7294294",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 6,
                "to_status_id" => 4,
                "created_at" => "2025-09-20 15:36:51",
                "updated_at" => "2025-09-20 15:36:51",
            ],
            [
                "id" => "019967c5-5ff2-71fe-8f2d-4edb347a008c",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 6,
                "to_status_id" => 7,
                "created_at" => "2025-09-20 15:36:52",
                "updated_at" => "2025-09-20 15:36:52",
            ],
            [
                "id" => "019967c5-6297-707c-917d-2fb1e8585c7a",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 6,
                "to_status_id" => 1,
                "created_at" => "2025-09-20 15:36:53",
                "updated_at" => "2025-09-20 15:36:53",
            ],
            [
                "id" => "019967c5-77ec-73f1-aff3-56f50ed3eba9",
                "project_id" => "019967c2-21b8-71eb-8c39-97d796503d50",
                "from_status_id" => 7,
                "to_status_id" => 6,
                "created_at" => "2025-09-20 15:36:58",
                "updated_at" => "2025-09-20 15:36:58",
            ],
        ];

        StatusTransition::insert($data);
    }
}
