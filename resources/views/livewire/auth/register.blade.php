<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.empty')]
class extends Livewire\Volt\Component {
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public string $turnstileToken = '';

    protected array $rules = [
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
    ];

    public function mount(): void
    {
        $this->redirectRoute('landing');
        return;

        /*if (auth()->check()) {
            $this->redirectRoute('dashboard');
        }*/
    }

    public function register()
    {
        $this->validate();

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

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        auth()->login($user);

        session()->flash('message', 'Account created successfully!');

        return $this->redirect('/', navigate: true);
    }
}

?>

<div>
    <x-slot:title>Register</x-slot:title>

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

                <h2 class="text-center text-2xl font-bold mb-6">Create your account</h2>

                <form wire:submit="register">
                    <div class="space-y-4">
                        <div class="form-control">
                            <x-input label="Name" wire:model="name" placeholder="Your name" required/>
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

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

                        <div class="form-control">
                            <x-input label="Confirm Password" wire:model="password_confirmation" type="password"
                                     placeholder="••••••••" required/>
                        </div>

                        <div class="form-control">
                            <div class="cf-turnstile" wire:ignore
                                 data-sitekey="{{ config('services.turnstile.site_key') }}"
                                 data-callback="turnstileCallback"></div>
                            @error('turnstileToken') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control mt-6">
                            <x-button type="submit" label="Register" class="btn-primary w-full"/>
                        </div>
                    </div>
                </form>

                <div class="divider">OR</div>

                <p class="text-center">
                    Already have an account?
                    <a href="/login" class="text-primary hover:underline">Login</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.turnstileCallback = function (token) {
                    @this.
                    set('turnstileToken', token);
                };

                document.addEventListener('livewire:init', () => {
                    Livewire.on('reset-turnstile', () => {
                        if (window.turnstile) {
                            window.turnstile.reset();
                        }
                    });
                });
            });
        </script>
    @endpush
</div>
