<?php

new class extends Livewire\Component {
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    
    protected $rules = [
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
    ];
    
    public function register()
    {
        $this->validate();
        
        $user = \App\Models\User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);
        
        auth()->login($user);
        
        session()->flash('message', 'Account created successfully!');
        
        return $this->redirect('/', navigate: true);
    }
    
    public function render()
    {
        return view('livewire.auth.register');
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
                        <x-icon name="o-rocket-launch" class="text-primary w-10 h-10" />
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
                            <x-input label="Name" wire:model="name" placeholder="Your name" required />
                            @error('name') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="form-control">
                            <x-input label="Email" wire:model="email" type="email" placeholder="your@email.com" required />
                            @error('email') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="form-control">
                            <x-input label="Password" wire:model="password" type="password" placeholder="••••••••" required />
                            @error('password') <span class="text-error text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="form-control">
                            <x-input label="Confirm Password" wire:model="password_confirmation" type="password" placeholder="••••••••" required />
                        </div>
                        
                        <div class="form-control mt-6">
                            <x-button type="submit" label="Register" class="btn-primary w-full" />
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
</div>
