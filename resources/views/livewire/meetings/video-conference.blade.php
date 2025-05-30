<?php
new class extends Livewire\Volt\Component {
    public \App\Models\Meeting $meeting;
    public $meetingRoom;
    public $userName;
    public $isHost = false;
    public $jwtToken = null;

    public function mount()
    {
        $this->meeting = \App\Models\Meeting::with(['project', 'users', 'creator'])->findOrFail($this->meeting->id);
        $this->meetingRoom = 'projecta-meeting-' . $this->meeting->id;
        $this->userName = auth()->user()->name;
        $this->isHost = auth()->id() === $this->meeting->creator_id;

        // Update meeting status if host is joining
        if ($this->isHost && $this->meeting->status === 'scheduled') {
            $this->meeting->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    public function endMeeting()
    {
        if ($this->isHost) {
            $this->meeting->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);

            return redirect()->route('meetings.show', $this->meeting->id);
        }
    }

    public function leaveMeeting()
    {
        return redirect()->route('meetings.show', $this->meeting->id);
    }
}
?>

<div class="h-screen flex flex-col">
    <!-- Meeting Header -->
    <div class="bg-base-200 p-4 shadow-md">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">{{ $meeting->title }}</h1>
                <p class="text-sm text-gray-500">{{ $meeting->project->name }} | {{ ucfirst($meeting->meeting_type) }}
                    Meeting</p>
            </div>
            <div class="flex gap-2">
                @if($isHost)
                    <x-button wire:click="endMeeting" icon="fas.stop-circle" color="error">End Meeting</x-button>
                @else
                    <x-button wire:click="leaveMeeting" icon="fas.sign-out-alt" color="neutral">Leave</x-button>
                @endif
            </div>
        </div>
    </div>

    <!-- Meeting Video Container -->
    <div class="flex-1 bg-gray-900" id="meet"></div>

    <!-- Jitsi Meet Script -->
    <script src='https://meet.jit.si/external_api.js'></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const domain = '23.88.112.66:8443';
            const options = {
                roomName: '{{ $meetingRoom }}',
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#meet'),
                userInfo: {
                    displayName: '{{ $userName }}',
                    email: '{{ auth()->user()->email }}'
                },
                configOverwrite: {
                    // Temel ayarlar
                    prejoinPageEnabled: false,
                    disableDeepLinking: true,

                    // Ses ve video ayarları
                    startWithAudioMuted: false,
                    startWithVideoMuted: false,

                    // Moderatör ayarları
                    enableUserRolesBasedOnToken: false,

                    // Lobi ayarları
                    enableLobbyChat: false,
                    requireDisplayName: false,
                    enableWelcomePage: false,
                    enableClosePage: false,

                    // P2P ayarları
                    p2p: {
                        enabled: true
                    },

                    // Moderatör bekleme sorununu çözmek için
                    testing: {
                        enableFirefoxSimulcast: false,
                        p2pTestMode: false,
                        disableE2EE: true,
                        // Bu ayar önemli
                        noAutoPlayVideo: false
                    },

                    // Oda ayarları
                    disableInitialGUM: false,
                    resolution: 720,
                    constraints: {
                        video: {
                            height: {
                                ideal: 720,
                                max: 720,
                                min: 180
                            }
                        }
                    },
                    // Ekran paylaşımı ayarları
                    DISABLE_VIDEO_BACKGROUND: false,
                    INITIAL_TOOLBAR_TIMEOUT: 20000,
                    TOOLBAR_TIMEOUT: 4000,
                    SHOW_CHROME_EXTENSION_BANNER: false,
                },
                interfaceConfigOverwrite: {
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera', 'desktop', 'fullscreen',
                        'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
                        'settings', 'raisehand', 'videoquality', 'filmstrip',
                        'tileview', 'download', 'help', 'mute-everyone',
                        'screensharing', 'sharedvideo'
                    ],
                    SETTINGS_SECTIONS: ['devices', 'language', 'moderator', 'profile', 'calendar'],
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false,
                    DEFAULT_BACKGROUND: '#3D4451',
                    DEFAULT_REMOTE_DISPLAY_NAME: 'Team Member',
                    TOOLBAR_ALWAYS_VISIBLE: true,
                    DISABLE_JOIN_LEAVE_NOTIFICATIONS: true,
                    DISABLE_FOCUS_INDICATOR: true,
                    // Moderatör beklemeden başlama
                    DISABLE_DOMINANT_SPEAKER_INDICATOR: false,
                    RECENT_LIST_ENABLED: false,
                    GENERATE_ROOMNAMES_ON_WELCOME_PAGE: false,
                    DISPLAY_WELCOME_FOOTER: false,
                    DISPLAY_WELCOME_PAGE_CONTENT: false,
                    DISPLAY_WELCOME_PAGE_TOOLBAR_ADDITIONAL_CONTENT: false
                }
            };

            // Toplantı sahibi için ek ayarlar
            if ({{ $isHost ? 'true' : 'false' }}) {
                options.userInfo.role = 'moderator';
            }

            // API'yi başlat
            const api = new JitsiMeetExternalAPI(domain, options);

            // Ekran paylaşımı butonu ekle
            const screenSharingButton = document.createElement('div');
            screenSharingButton.className = 'fixed bottom-4 right-4 z-50 bg-primary text-white rounded-full p-3 shadow-lg cursor-pointer hover:bg-primary-focus';
            screenSharingButton.innerHTML = '<i class="fas fa-desktop text-xl"></i>';
            screenSharingButton.title = 'Ekranı Paylaş';
            document.body.appendChild(screenSharingButton);
            
            // Ekran paylaşımı butonuna tıklama olayı ekle
            screenSharingButton.addEventListener('click', () => {
                api.executeCommand('toggleShareScreen');
            });

            // Ekran paylaşımı durumunu izle
            api.addEventListener('screenSharingStatusChanged', (event) => {
                if (event.on) {
                    screenSharingButton.classList.add('bg-success');
                    screenSharingButton.classList.remove('bg-primary');
                } else {
                    screenSharingButton.classList.add('bg-primary');
                    screenSharingButton.classList.remove('bg-success');
                }
            });

            // Toplantı başladığında
            api.addEventListener('videoConferenceJoined', (event) => {
                console.log('Toplantıya katıldınız', event);

                // Toplantı sahibi ise
                if ({{ $isHost ? 'true' : 'false' }}) {
                    // Başlık ayarla
                    api.executeCommand('subject', '{{ $meeting->title }}');

                    // Lobi modunu kapat
                    setTimeout(() => {
                        api.executeCommand('toggleLobby', false);
                    }, 1000);
                }
            });

            // Toplantı sonlandığında
            api.addEventListener('readyToClose', () => {
                @this.leaveMeeting();
            });

            // Hata durumunda
            api.addEventListener('errorOccurred', (error) => {
                console.error('Jitsi hata:', error);
            });

            // Bağlantı durumu değiştiğinde
            api.addEventListener('connectionEstablished', () => {
                console.log('Bağlantı kuruldu');
            });

            // Katılımcı rolü değiştiğinde
            api.addEventListener('participantRoleChanged', (event) => {
                console.log('Rol değişti:', event);
            });
        });
    </script>
</div>
