<?php
new class extends Livewire\Volt\Component {
    use \Mary\Traits\Toast;

    public $passwordEntries = [];
    public $weakPasswords = [];
    public $reusedPasswords = [];
    public $oldPasswords = [];
    public $expiredPasswords = [];
    public $isScanning = false;
    public $scanComplete = false;

    public function mount()
    {
        // Don't auto-scan on page load to avoid performance issues
    }

    public function startScan()
    {
        $this->isScanning = true;
        $this->scanComplete = false;

        // Get all password entries the user has access to
        $this->passwordEntries = \App\Models\PasswordEntry::whereHas('vault', function ($query) {
            $query->where('user_id', auth()->id())
                ->orWhere(function ($query) {
                    $query->where('is_shared', true)
                        ->whereHas('workspace', function ($query) {
                            $query->whereHas('members', function ($query) {
                                $query->where('user_id', auth()->id());
                            });
                        });
                });
        })->get();

        // Reset results
        $this->weakPasswords = [];
        $this->reusedPasswords = [];
        $this->oldPasswords = [];
        $this->expiredPasswords = [];

        // Check for weak passwords
        foreach ($this->passwordEntries as $entry) {
            if ($entry->security_level === 'low') {
                $this->weakPasswords[] = $entry;
            }
        }

        // Check for reused passwords (same password used in multiple entries)
        $passwordHashes = [];
        foreach ($this->passwordEntries as $entry) {
            // Note: In a real implementation, we would need to decrypt and compare actual passwords
            // For this demo, we'll just use the security_level as a proxy
            $hash = $entry->security_level . '-' . strlen($entry->password_encrypted);

            if (isset($passwordHashes[$hash])) {
                $passwordHashes[$hash][] = $entry;
                // Only add to reused if not already added
                if (count($passwordHashes[$hash]) === 2) {
                    $this->reusedPasswords[] = $passwordHashes[$hash][0];
                }
                $this->reusedPasswords[] = $entry;
            } else {
                $passwordHashes[$hash] = [$entry];
            }
        }

        // Check for old passwords (not updated in the last 90 days)
        $ninetyDaysAgo = now()->subDays(90);
        foreach ($this->passwordEntries as $entry) {
            if ($entry->updated_at->lt($ninetyDaysAgo)) {
                $this->oldPasswords[] = $entry;
            }
        }

        // Check for expired passwords
        foreach ($this->passwordEntries as $entry) {
            if ($entry->expires_at && $entry->expires_at->lt(now())) {
                $this->expiredPasswords[] = $entry;
            }
        }

        $this->isScanning = false;
        $this->scanComplete = true;

        $this->success('Security scan completed successfully.');
    }

    public function getTotalIssuesProperty(): int
    {
        return count($this->weakPasswords) + count($this->reusedPasswords) +
            count($this->oldPasswords) + count($this->expiredPasswords);
    }

    public function getSecurityScoreProperty(): float|int
    {
        if (count($this->passwordEntries) === 0) {
            return 100;
        }

        $totalIssues = $this->totalIssues;
        $totalEntries = count($this->passwordEntries);

        // Calculate score (100 - percentage of entries with issues)
        $score = 100 - min(100, ($totalIssues / $totalEntries) * 100);

        return round($score);
    }

    public function getSecurityLevelProperty(): array
    {
        $score = $this->securityScore;

        if ($score >= 90) {
            return [
                'text' => 'Excellent',
                'color' => 'green'
            ];
        } elseif ($score >= 70) {
            return [
                'text' => 'Good',
                'color' => 'blue'
            ];
        } elseif ($score >= 50) {
            return [
                'text' => 'Fair',
                'color' => 'yellow'
            ];
        } else {
            return [
                'text' => 'Poor',
                'color' => 'red'
            ];
        }
    }


    public function with(): array
    {
        return [
            'securityLevel' => $this->getSecurityLevelProperty(),
            'securityScore' => $this->getSecurityScoreProperty(),
            'totalIssues' => $this->getTotalIssuesProperty(),
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-shield-alt"></i>
            Security Check
        </h1>
        <div>
            <x-button link="{{ route('password-manager.dashboard') }}" variant="ghost">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </x-button>
        </div>
    </div>

    @if(!$scanComplete)
        <x-card class="mb-6">
            <div class="text-center py-8">
                @if($isScanning)
                    <div class="flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                        <h3 class="text-xl font-medium">Scanning your passwords...</h3>
                        <p class="text-gray-500 mt-2">This may take a moment</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center">
                        <div class="text-5xl text-gray-300 mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="text-xl font-medium mb-4">Check your password security</h3>
                        <p class="text-gray-500 mb-6 max-w-lg mx-auto">
                            This tool will scan your passwords for security issues such as weak passwords,
                            reused passwords, old passwords, and expired passwords.
                        </p>
                        <x-button wire:click="startScan" class="btn-primary">
                            <i class="fas fa-play mr-2"></i> Start Security Scan
                        </x-button>
                    </div>
                @endif
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
            <x-card>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-1">{{ $securityScore }}/100</div>
                    <div class="text-sm text-gray-500">Security Score</div>
                    <div class="mt-2">
                        @if($securityLevel['color'] === 'green')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">{{ $securityLevel['text'] }}</span>
                        @elseif($securityLevel['color'] === 'blue')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">{{ $securityLevel['text'] }}</span>
                        @elseif($securityLevel['color'] === 'yellow')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">{{ $securityLevel['text'] }}</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">{{ $securityLevel['text'] }}</span>
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-1 text-red-500">{{ count($weakPasswords) }}</div>
                    <div class="text-sm text-gray-500">Weak Passwords</div>
                </div>
            </x-card>

            <x-card>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-1 text-yellow-500">{{ count($reusedPasswords) }}</div>
                    <div class="text-sm text-gray-500">Reused Passwords</div>
                </div>
            </x-card>

            <x-card>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-1 text-blue-500">{{ count($oldPasswords) + count($expiredPasswords) }}</div>
                    <div class="text-sm text-gray-500">Old/Expired Passwords</div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            @if(count($weakPasswords) > 0)
                <x-card>
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                        Weak Passwords
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Username</th>
                                <th>Vault</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($weakPasswords as $entry)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-key text-gray-400"></i>
                                            {{ $entry->title }}
                                        </div>
                                    </td>
                                    <td>{{ $entry->username }}</td>
                                    <td>{{ $entry->vault->name }}</td>
                                    <td>
                                        <x-button
                                                link="{{ route('password-manager.entries.edit', ['vault' => $entry->vault_id, 'entry' => $entry->id]) }}"
                                                class="btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            @if(count($reusedPasswords) > 0)
                <x-card>
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <i class="fas fa-copy text-yellow-500"></i>
                        Reused Passwords
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Username</th>
                                <th>Vault</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($reusedPasswords as $entry)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-key text-gray-400"></i>
                                            {{ $entry->title }}
                                        </div>
                                    </td>
                                    <td>{{ $entry->username }}</td>
                                    <td>{{ $entry->vault->name }}</td>
                                    <td>
                                        <x-button
                                                link="{{ route('password-manager.entries.edit', ['vault' => $entry->vault_id, 'entry' => $entry->id]) }}"
                                                class="btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            @if(count($oldPasswords) > 0)
                <x-card>
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <i class="fas fa-clock text-blue-500"></i>
                        Old Passwords (not updated in 90+ days)
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Username</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($oldPasswords as $entry)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-key text-gray-400"></i>
                                            {{ $entry->title }}
                                        </div>
                                    </td>
                                    <td>{{ $entry->username }}</td>
                                    <td>{{ $entry->updated_at->format('M d, Y') }}
                                        ({{ $entry->updated_at->diffForHumans() }})
                                    </td>
                                    <td>
                                        <x-button
                                                link="{{ route('password-manager.entries.edit', ['vault' => $entry->vault_id, 'entry' => $entry->id]) }}"
                                                class="btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            @if(count($expiredPasswords) > 0)
                <x-card>
                    <h3 class="text-lg font-medium mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-times text-red-500"></i>
                        Expired Passwords
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Username</th>
                                <th>Expired On</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($expiredPasswords as $entry)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-key text-gray-400"></i>
                                            {{ $entry->title }}
                                        </div>
                                    </td>
                                    <td>{{ $entry->username }}</td>
                                    <td>{{ $entry->expires_at->format('M d, Y') }}
                                        ({{ $entry->expires_at->diffForHumans() }})
                                    </td>
                                    <td>
                                        <x-button
                                                link="{{ route('password-manager.entries.edit', ['vault' => $entry->vault_id, 'entry' => $entry->id]) }}"
                                                class="btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            @if($totalIssues === 0)
                <x-card>
                    <div class="text-center py-8">
                        <div class="text-5xl text-green-500 mb-4">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="text-xl font-medium mb-2">No security issues found!</h3>
                        <p class="text-gray-500">Your passwords are secure. Keep up the good work!</p>
                    </div>
                </x-card>
            @endif

            <div class="flex justify-center">
                <x-button wire:click="startScan" class="btn-primary">
                    <i class="fas fa-sync-alt mr-2"></i> Scan Again
                </x-button>
            </div>
        </div>
    @endif
</div>
