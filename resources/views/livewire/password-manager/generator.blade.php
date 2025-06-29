<?php
new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;
    
    public $length = 16;
    public $includeUppercase = true;
    public $includeLowercase = true;
    public $includeNumbers = true;
    public $includeSymbols = true;
    public $excludeSimilar = false;
    public $generatedPassword = '';
    
    public function mount()
    {
        $this->generatePassword();
    }
    
    public function generatePassword()
    {
        // Validate that at least one character type is selected
        if (!$this->includeUppercase && !$this->includeLowercase && !$this->includeNumbers && !$this->includeSymbols) {
            $this->error('Please select at least one character type.');
            return;
        }
        
        // Define character sets
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{};:,.<>?';
        
        // Remove similar characters if option is selected
        if ($this->excludeSimilar) {
            $uppercase = str_replace(['O', 'I'], '', $uppercase);
            $lowercase = str_replace(['o', 'i', 'l'], '', $lowercase);
            $numbers = str_replace(['0', '1'], '', $numbers);
        }
        
        // Build character pool based on selected options
        $pool = '';
        if ($this->includeUppercase) $pool .= $uppercase;
        if ($this->includeLowercase) $pool .= $lowercase;
        if ($this->includeNumbers) $pool .= $numbers;
        if ($this->includeSymbols) $pool .= $symbols;
        
        // Generate password
        $password = '';
        $poolLength = strlen($pool);
        
        for ($i = 0; $i < $this->length; $i++) {
            $password .= $pool[random_int(0, $poolLength - 1)];
        }
        
        // Ensure password contains at least one character from each selected type
        $hasRequiredChars = true;
        
        if ($this->includeUppercase && !preg_match('/[A-Z]/', $password)) $hasRequiredChars = false;
        if ($this->includeLowercase && !preg_match('/[a-z]/', $password)) $hasRequiredChars = false;
        if ($this->includeNumbers && !preg_match('/[0-9]/', $password)) $hasRequiredChars = false;
        if ($this->includeSymbols && !preg_match('/[^A-Za-z0-9]/', $password)) $hasRequiredChars = false;
        
        // If password doesn't meet requirements, generate again
        if (!$hasRequiredChars) {
            return $this->generatePassword();
        }
        
        $this->generatedPassword = $password;
    }
    
    public function copyToClipboard()
    {
        $this->dispatch('clipboard', text: $this->generatedPassword);
        $this->success('Password copied to clipboard!');
    }
    
    public function getPasswordStrengthProperty()
    {
        $length = strlen($this->generatedPassword);
        $charTypes = 0;
        
        if (preg_match('/[A-Z]/', $this->generatedPassword)) $charTypes++;
        if (preg_match('/[a-z]/', $this->generatedPassword)) $charTypes++;
        if (preg_match('/[0-9]/', $this->generatedPassword)) $charTypes++;
        if (preg_match('/[^A-Za-z0-9]/', $this->generatedPassword)) $charTypes++;
        
        if ($length >= 16 && $charTypes >= 3) {
            return [
                'level' => 'high',
                'text' => 'Strong',
                'color' => 'green'
            ];
        } elseif ($length >= 10 && $charTypes >= 2) {
            return [
                'level' => 'medium',
                'text' => 'Medium',
                'color' => 'yellow'
            ];
        } else {
            return [
                'level' => 'low',
                'text' => 'Weak',
                'color' => 'red'
            ];
        }
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-key"></i>
            Password Generator
        </h1>
        <div>
            <x-button link="{{ route('password-manager.dashboard') }}" variant="ghost">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </x-button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <div class="mb-6">
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <x-input wire:model.live="generatedPassword" readonly />
                        </div>
                        <x-button wire:click="copyToClipboard" class="btn-primary">
                            <i class="fas fa-copy mr-2"></i> Copy
                        </x-button>
                        <x-button wire:click="generatePassword" class="btn-secondary">
                            <i class="fas fa-sync-alt mr-2"></i> Generate
                        </x-button>
                    </div>
                    <div class="mt-2 flex items-center">
                        <span class="text-sm mr-2">Strength:</span>
                        @if($this->passwordStrength['level'] === 'high')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Strong</span>
                        @elseif($this->passwordStrength['level'] === 'medium')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Medium</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Weak</span>
                        @endif
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Password Length: {{ $length }}</label>
                        <input type="range" wire:model.live="length" min="8" max="32" class="w-full" />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-checkbox wire:model.live="includeUppercase" label="Include Uppercase Letters (A-Z)" />
                        </div>
                        <div>
                            <x-checkbox wire:model.live="includeLowercase" label="Include Lowercase Letters (a-z)" />
                        </div>
                        <div>
                            <x-checkbox wire:model.live="includeNumbers" label="Include Numbers (0-9)" />
                        </div>
                        <div>
                            <x-checkbox wire:model.live="includeSymbols" label="Include Symbols (!@#$%^&*)" />
                        </div>
                        <div>
                            <x-checkbox wire:model.live="excludeSimilar" label="Exclude Similar Characters (0,O,1,l,I)" />
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
        
        <div>
            <x-card>
                <h3 class="font-medium text-lg mb-4">Password Tips</h3>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <h4 class="font-medium">Strong Password Guidelines</h4>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Use at least 16 characters</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Include numbers and symbols</li>
                            <li>Avoid common words or phrases</li>
                            <li>Don't use personal information</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium">Password Security</h4>
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            <li>Use a different password for each account</li>
                            <li>Change passwords regularly</li>
                            <li>Use a password manager to store passwords</li>
                            <li>Enable two-factor authentication when available</li>
                        </ul>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>

<script>
    document.addEventListener('clipboard', event => {
        navigator.clipboard.writeText(event.detail.text);
    });
</script>
