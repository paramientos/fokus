<?php

use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.empty')]
class extends Livewire\Volt\Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        if (auth()->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return $this->redirect('/', navigate: true);
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }
}

?>

<div>
    <x-slot:title>Login</x-slot:title>

    <div class="min-h-screen flex items-center justify-center">
        <div class="card bg-base-100 shadow-xl w-full max-w-md">

            <div class="card-body">

                <div class="flex justify-center mb-6">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-rocket-launch" class="text-primary w-10 h-10"/>
                        <div>
                            <h1 class="text-2xl font-bold text-primary">ProjectFlow</h1>
                            <p class="text-sm">Project Management</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-center text-2xl font-bold mb-6">Login to your account</h2>

                <livewire:components.all-info-component />

                <form wire:submit="login">
                    <div class="space-y-4">
                        <div class="form-control">
                            <x-input label="Email" wire:model="email" type="email" placeholder="your@email.com"
                                     required/>
                            @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <x-input label="Password" wire:model="password" type="password" placeholder="••••••••"
                                     required/>
                            @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer label justify-start gap-2">
                                <x-checkbox wire:model="remember"/>
                                <span class="label-text">Remember me</span>
                            </label>

                            <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                        </div>

                        <div class="form-control mt-6">
                            <x-button type="submit" label="Login" class="btn-primary w-full"/>
                        </div>
                    </div>
                </form>

                <div class="divider">OR</div>

                <p class="text-center">
                    Don't have an account?
                    <a href="/register" class="text-primary hover:underline">Register</a>
                </p>
            </div>
        </div>
    </div>
</div>
