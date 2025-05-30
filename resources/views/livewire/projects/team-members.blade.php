<div>
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Team Members</h2>
            <p class="text-gray-500">Manage project team members and their roles</p>

            <!-- Invite New Member Form -->
            <div class="mt-6 p-4 bg-base-200 rounded-lg">
                <h3 class="font-medium mb-4">Invite New Member</h3>

                <form wire:submit="inviteMember" class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <x-input
                                wire:model="newMemberEmail"
                                placeholder="Email address"
                                label="Email Address"
                                error="{{ $errors->first('newMemberEmail') }}"
                            />
                        </div>
                        <div>
                            <x-select
                                wire:model="newMemberRole"
                                label="Role"
                                error="{{ $errors->first('newMemberRole') }}"
                                :options="collect($roles)->map(function($label, $value) {
                                return ['name' => $label, 'id' => $value];
                            })->values()"
                            />
                        </div>
                    </div>
                    
                    <!-- İsim alanı - Yeni kullanıcı için -->
                    <div class="mt-4">
                        <x-input
                            wire:model="newMemberName"
                            placeholder="Full name (for new users)"
                            label="Full Name"
                            error="{{ $errors->first('newMemberName') }}"
                            helper="If the email is not registered, a new user will be created with this name"
                        />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-button type="submit" icon="fas.user-plus" color="primary">
                            Invite Member
                        </x-button>
                    </div>
                </form>
            </div>

            <!-- Team Members List -->
            <div class="overflow-x-auto mt-6">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->teamMembers as $member)
                            <tr>
                                <td class="flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-8">
                                            <span>{{ substr($member->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    {{ $member->name }}
                                    @if($project->user_id === $member->id)
                                        <x-badge color="primary" size="sm">Owner</x-badge>
                                    @endif
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    @if($project->user_id === $member->id)
                                        <x-badge color="primary">Administrator</x-badge>
                                    @else
                                        <x-dropdown>
                                            <x-slot:trigger>
                                                <x-button class="btn-sm">
                                                    {{ $roles[$member->pivot->role] }}
                                                    <i class="fas fa-chevron-down ml-2"></i>
                                                </x-button>
                                            </x-slot:trigger>

                                            <x-menu>
                                                @foreach($roles as $value => $label)
                                                    <x-menu-item
                                                        wire:click="updateRole({{ $member->id }}, '{{ $value }}')"
                                                        class="{{ $member->pivot->role === $value ? 'bg-primary/10' : '' }}"
                                                    >
                                                        {{ $label }}
                                                    </x-menu-item>
                                                @endforeach
                                            </x-menu>
                                        </x-dropdown>
                                    @endif
                                </td>
                                <td>
                                    @if($project->user_id !== $member->id)
                                        <x-button
                                            wire:click="removeMember({{ $member->id }})"
                                            wire:confirm="Are you sure you want to remove this member from the project?"
                                            color="error"
                                            class="btn-sm"
                                            icon="fas.user-minus"
                                        >
                                            Remove
                                        </x-button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @if($project->teamMembers->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center py-4">No team members found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
