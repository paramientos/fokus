<?php

new class extends Livewire\Volt\Component {
    public $user;
    public $name;
    public $email;
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';
    public bool $showDeleteModal = false;

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'email' => 'required|email',
    ];

    protected $validationAttributes = [
        'current_password' => 'current password',
        'password' => 'new password',
    ];

    public function mount()
    {
        $this->user = auth()->user();

        $this->name = $this->user?->name;
        $this->email = $this->user?->email;
    }

    public function updateProfile()
    {
        $this->validate();

        // Check if email is changed and if it's already taken
        if ($this->email !== $this->user->email) {
            $this->validate([
                'email' => 'unique:users,email,' . $this->user->id,
            ]);
        }

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        session()->flash('message', 'Profile updated successfully!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        $this->user->update([
            'password' => bcrypt($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('message', 'Password updated successfully!');
    }
}

?>

<div>
    <x-slot:title>Profile Settings</x-slot:title>

    <div class="p-6">
        <h1 class="text-2xl font-bold text-primary mb-6">Profile Settings</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Profile Information</h2>
                        <p class="text-gray-500">Update your account's profile information and email address.</p>

                        <form wire:submit="updateProfile" class="mt-4">
                            <div class="space-y-4">
                                <div class="form-control">
                                    <x-input label="Name" wire:model="name" placeholder="Your name" required />
                                    @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="form-control">
                                    <x-input label="Email" wire:model="email" type="email" placeholder="your@email.com" required />
                                    @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-button type="submit" label="Save" class="btn-primary" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Avatar -->
            <div class="lg:col-span-1">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Your Avatar</h2>

                        <div class="flex flex-col items-center justify-center py-4">
                            <div class="avatar placeholder">
                                <div class="bg-neutral text-neutral-content rounded-full w-24">
                                    <span class="text-3xl">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                            </div>

                            <p class="mt-4 text-gray-500">Avatar functionality coming soon</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Password -->
            <div class="lg:col-span-3">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">Update Password</h2>
                        <p class="text-gray-500">Ensure your account is using a long, random password to stay secure.</p>

                        <form wire:submit="updatePassword" class="mt-4">
                            <div class="space-y-4">
                                <div class="form-control">
                                    <x-input label="Current Password" wire:model="current_password" type="password" placeholder="••••••••" required />
                                    @error('current_password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="form-control">
                                    <x-input label="New Password" wire:model="password" type="password" placeholder="••••••••" required />
                                    @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="form-control">
                                    <x-input label="Confirm Password" wire:model="password_confirmation" type="password" placeholder="••••••••" required />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-button type="submit" label="Update Password" class="btn-primary" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="lg:col-span-3">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title text-error">Danger Zone</h2>
                        <p class="text-gray-500">Once your account is deleted, all of its resources and data will be permanently deleted.</p>

                        <div class="mt-4">
                            <x-button
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'delete-account-modal')"
                                label="Delete Account"
                                icon="o-trash"
                                class="btn-error"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <x-modal wire:model="showDeleteModal" name="delete-account-modal" title="Delete Account">
            <div class="p-4">
                <h3 class="text-lg font-bold">Are you sure you want to delete your account?</h3>
                <p class="py-4">Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.</p>

                <div class="flex justify-end gap-2">
                    <x-button
                        x-on:click="$dispatch('close-modal', 'delete-account-modal')"
                        label="Cancel"
                        class="btn-ghost"
                    />
                    <x-button
                        wire:click="deleteAccount"
                        x-on:click="$dispatch('close-modal', 'delete-account-modal')"
                        label="Delete Account"
                        icon="o-trash"
                        class="btn-error"
                    />
                </div>
            </div>
        </x-modal>
    </div>
</div>
