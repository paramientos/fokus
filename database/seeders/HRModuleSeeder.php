<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Training;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HRModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create HR Manager
        $hrManager = User::firstOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );


        // Get or create the workspace
        $workspace = Workspace::firstOrCreate(
            ['name' => 'Default Workspace'],
            [
                'description' => 'Default workspace for HR module',
                'owner_id' => $hrManager->id,
                'created_by' => $hrManager->id,
            ]
        );

        // Assign HR Manager to workspace if not already assigned
        if (!$hrManager->workspaceMembers()->where('workspace_id', $workspace->id)->exists()) {
            $hrManager->workspaceMembers()->attach($workspace->id, ['role' => 'hr_manager', 'id' => Str::uuid(),]);
            $hrManager->current_workspace_id = $workspace->id;
            $hrManager->save();
        }

        // Create sample employee
        $employeeUser = User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'phone' => '+1234567890',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'nationality' => 'US',
                'address' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States',
                'emergency_contact_relationship' => 'Spouse',
            ]
        );

        // Assign employee to workspace if not already assigned
        if (!$employeeUser->workspaceMembers()->where('workspace_id', $workspace->id)->exists()) {
            $employeeUser->workspaceMembers()->attach($workspace->id, ['role' => 'employee','id'=> Str::uuid(),]);
            $employeeUser->current_workspace_id = $workspace->id;
            $employeeUser->save();
        }

        // Create employee record
        $employee = Employee::firstOrCreate(
            ['user_id' => $employeeUser->id],
            [
                'workspace_id' => $workspace->id,
                'employee_id' => 'EMP' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'department' => 'Engineering',
                'position' => 'Senior Developer',
                'hire_date' => now()->subYear(),
                'salary' => 85000.00,
                'employment_type' => 'full_time',
                'work_location' => 'hybrid',
                'emergency_contact_name' => 'Jane Doe',
                'emergency_contact_phone' => '+1987654321',
                'bank_name' => 'Tech Bank',
                'bank_account' => '1234567890',
                'iban' => 'US1234567890',
                'skills' => ['PHP', 'Laravel', 'JavaScript', 'Vue.js'],
                'notes' => 'Top performer in the team',
            ]
        );

        // Create leave types
        $leaveTypes = [
            [
                'workspace_id' => $workspace->id,
                'name' => 'Annual Leave',
                'annual_quota' => 20,
                'carry_over' => true,
                'max_carried_over' => 10,
                'requires_approval' => true,
                'description' => 'Paid time off work granted by employers to employees to be used for whatever the employee wishes.',
                'is_active' => true,
            ],
            [
                'workspace_id' => $workspace->id,
                'name' => 'Sick Leave',
                'annual_quota' => 10,
                'carry_over' => false,
                'max_carried_over' => null,
                'requires_approval' => false,
                'description' => 'Paid time off work that workers can use to stay home to address their health needs without losing pay.',
                'is_active' => true,
            ],
            [
                'workspace_id' => $workspace->id,
                'name' => 'Unpaid Leave',
                'annual_quota' => 0,
                'carry_over' => false,
                'max_carried_over' => null,
                'requires_approval' => true,
                'description' => 'A leave of absence from work, for which the employee does not receive pay.',
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $leaveType['name']],
                $leaveType
            );
        }

        // Create sample training
        $training = Training::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'title' => 'Advanced Laravel Development'
            ],
            [
                'description' => 'In-depth training on advanced Laravel features and best practices.',
                'type' => 'workshop',
                'start_date' => now()->addWeek(),
                'end_date' => now()->addWeeks(2),
                'cost' => 999.99,
                'provider' => 'Laravel Academy',
                'location' => 'Online',
                'max_participants' => 20,
                'is_mandatory' => false,
                'prerequisites' => ['Basic PHP', 'Basic Laravel'],
            ]
        );

        // Enroll employee in training
        if (!$employee->trainings()->where('training_id', $training->id)->exists()) {
            $employee->trainings()->attach($training->id, [
                'id'=> Str::uuid(),
                'status' => 'registered',
                'score' => null,
                'feedback' => null,
                'certificate_path' => null,
            ]);
        }

        // Create sample performance review
        $employee->performanceReviews()->firstOrCreate(
            [
                'reviewer_id' => $hrManager->id,
                'review_date' => now()->subMonth(),
            ],
            [
                'next_review_date' => now()->addYear(),
                'goals' => ['Improve code quality', 'Learn new technologies', 'Enhance team collaboration'],
                'strengths' => ['Problem-solving skills', 'Team player', 'Quick learner'],
                'improvement_areas' => ['Documentation', 'Time management'],
                'overall_rating' => 4.5,
                'feedback' => 'John has been a valuable member of the team, consistently delivering high-quality work.',
                'status' => 'completed',
            ]
        );
    }
}
