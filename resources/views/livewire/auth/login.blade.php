<?php

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.empty')]
class extends Livewire\Volt\Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $turnstileToken = '';

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectRoute('dashboard');
        }
    }

    public function login()
    {
        $this->validate();

        if (app()->environment(['staging', 'production'])) {
            if ($this->turnstileToken === '') {
                $this->addError('turnstileToken', 'Security verification failed. Please try again.');
                $this->dispatch('reset-turnstile');
                return;
            }

            // Verify Turnstile token
            $response = Http::post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $this->turnstileToken,
                'remoteip' => get_real_ip(),
            ]);

            if (!$response->json('success')) {
                $this->addError('turnstileToken', 'Security verification failed. Please try again.');
                $this->dispatch('reset-turnstile');
                $this->turnstileToken = '';
                return;
            }
        }

        if (auth()->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $this->redirectRoute('dashboard');
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }
}

?>

<div>
    <x-slot:title>Login to Fokus</x-slot:title>

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left side - Brand showcase -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-primary to-primary-focus p-8 text-white justify-center items-center">
            <div class="max-w-md">
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-cube text-4xl"></i>
                    <h1 class="text-4xl font-bold">Fokus</h1>
                </div>

                <h2 class="text-2xl font-bold mb-4">Modern Project Management</h2>
                <p class="text-lg mb-8 opacity-90">Streamline your workflow, collaborate with your team, and deliver projects on time.</p>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i class="fas fa-tasks text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Task Management</h3>
                            <p class="opacity-80 text-sm">Create, assign, and track tasks with customizable workflows</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i class="fas fa-columns text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Kanban Board</h3>
                            <p class="opacity-80 text-sm">Visualize your workflow with drag-and-drop task management</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="bg-white/20 p-2 rounded-full">
                            <i class="fas fa-users text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Team Collaboration</h3>
                            <p class="opacity-80 text-sm">Work together seamlessly with integrated tools</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Login form -->
        <div class="w-full md:w-1/2 flex items-center justify-center p-6 bg-base-100">
            <div class="card bg-base-100 shadow-xl w-full max-w-md border border-base-300">
                <div class="card-body p-8">
                    <div class="flex justify-center mb-6 md:hidden">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-cube text-primary text-3xl"></i>
                            <div>
                                <h1 class="text-2xl font-bold text-primary">Fokus</h1>
                                <p class="text-sm text-base-content/70">Project Management</p>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-center text-2xl font-bold mb-2">Welcome Back</h2>
                    <p class="text-center text-base-content/70 mb-6">Sign in to continue to your workspace</p>

                    <livewire:components.all-info-component/>

                    <form wire:submit="login" class="space-y-6">
                        <div class="space-y-4">
                            <div class="form-control">
                                <x-input
                                    label="Email"
                                    wire:model="email"
                                    type="email"
                                    placeholder="your@email.com"
                                    icon="fas.envelope"
                                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                    required
                                />
                                @error('email') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-control">
                                <x-input
                                    label="Password"
                                    wire:model="password"
                                    type="password"
                                    placeholder="••••••••"
                                    icon="fas.lock"
                                    class="transition-all duration-300 focus:ring-2 focus:ring-primary/30"
                                    required
                                />
                                @error('password') <span class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            @env(['staging', 'production'])
                                <div class="form-control">
                                    <div class="turnstile"
                                         data-sitekey="{{ config('services.turnstile.site_key') }}"
                                         data-callback="turnstileCallback"
                                         data-theme="light"></div>
                                    @error('turnstileToken') <span
                                        class="text-error text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @endenv

                            <div class="flex items-center justify-between">
                                <label class="cursor-pointer label justify-start gap-2">
                                    <x-checkbox wire:model="remember"/>
                                    <span class="label-text">Remember me</span>
                                </label>

                                <a href="#" class="text-sm text-primary hover:underline transition-colors duration-200">Forgot password?</a>
                            </div>

                            <div wire:ignore class="cf-turnstile" data-callback="turnstileCallback"
                                 data-sitekey="{{ config('services.turnstile.site_key') }}"></div>

                            <div class="form-control mt-6">
                                <x-button
                                    type="submit"
                                    label="Sign In"
                                    icon="fas.sign-in-alt"
                                    class="btn-primary w-full hover:shadow-lg transition-all duration-300"
                                />
                            </div>
                        </div>
                    </form>

                    <div class="divider my-6">OR</div>

                    <div class="text-center">
                        <p class="mb-4 text-base-content/70">
                            Don't have an account?
                        </p>
                        <a href="/register" class="btn btn-outline btn-primary w-full hover:shadow-md transition-all duration-300">
                            <i class="fas fa-user-plus mr-2"></i> Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @env(['staging', 'production'])
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Turnstile callback function
                    window.turnstileCallback = function (token) {
                        @this.
                        set('turnstileToken', token);
                    };
                });

                document.addEventListener('livewire:init', () => {
                    Livewire.on('reset-turnstile', () => {
                        if (window.turnstile) {
                            window.turnstile.reset();
                        }
                    });
                });
            </script>
        @endpush
    @endenv
</div>
