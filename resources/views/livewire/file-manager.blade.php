<?php

use App\Models\File;
use App\Models\FileComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $fileable_type;
    public $fileable_id;
    public $files = [];
    public $upload;
    public $uploading = false;
    public $error = null;

    public $showCommentsFor = null;
    public bool $showCommentsModal = false;
    public $comments = [];
    public $newComment = '';
    public $commentError = null;
    public $commentLoading = false;

    public function mount($fileable_type, $fileable_id)
    {
        $this->fileable_type = $fileable_type;
        $this->fileable_id = $fileable_id;
        $this->loadFiles();
    }

    public function loadFiles()
    {
        $this->files = File::where('fileable_type', $this->fileable_type)
            ->where('fileable_id', $this->fileable_id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();
    }

    public function deleteFile($fileId)
    {
        $file = File::find($fileId);
        if (!$file) return;
        // Sadece yükleyen veya admin silebilir
        if (auth()->id() === $file->uploaded_by || auth()->user()?->hasRole('admin')) {
            $file->delete();
            $this->loadFiles();
        }
    }

    public function showComments($fileId): void
    {
        $this->showCommentsFor = $fileId;
        $this->loadComments();
        $this->showCommentsModal = true;
    }

    public function closeComments()
    {
        $this->showCommentsFor = null;
        $this->comments = [];
        $this->newComment = '';
        $this->commentError = null;
    }

    public function loadComments()
    {
        $this->comments = FileComment::where('file_id', $this->showCommentsFor)
            ->oldest()
            ->get();
    }

    public function addComment()
    {
        $this->commentError = null;
        $this->commentLoading = true;
        try {
            $this->validate([
                'newComment' => 'required|string|min:1|max:1000',
            ]);
            FileComment::create([
                'file_id' => $this->showCommentsFor,
                'user_id' => auth()->id(),
                'comment' => $this->newComment,
            ]);
            $this->newComment = '';
            $this->loadComments();
        } catch (\Exception $e) {
            $this->commentError = $e->getMessage();
        }
        $this->commentLoading = false;
    }

    public function updatedUpload()
    {
        $this->uploading = true;
        $this->error = null;
        try {
            $this->validate([
                'upload' => 'required|file|max:51200', // 50MB
            ]);
            $file = $this->upload;
            $path = $file->store('uploads/files', 'public');
            // Versiyon kontrolü: aynı isimde aktif dosya var mı?
            $existing = File::where('fileable_type', $this->fileable_type)
                ->where('fileable_id', $this->fileable_id)
                ->where('file_name', $file->getClientOriginalName())
                ->where('is_active', true)
                ->first();
            $version = 1;
            $parent_id = null;
            if ($existing) {
                $existing->is_active = false;
                $existing->save();
                $version = $existing->version + 1;
                $parent_id = $existing->id;
            }
            $newFile = File::create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
                'fileable_type' => $this->fileable_type,
                'fileable_id' => $this->fileable_id,
                'version' => $version,
                'parent_id' => $parent_id,
                'is_active' => true,
            ]);
            $this->loadFiles();
            $this->upload = null;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        $this->uploading = false;
    }
}; ?>

<div>
    <div class="mb-4">
        <x-input type="file" wire:model.live="upload" label="Upload File"/>
        @if($error)
            <x-alert color="error" class="mt-2">{{ $error }}</x-alert>
        @endif
        @if($uploading)
            <x-progress indeterminate label="Uploading..." class="mt-2"/>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
            <tr>
                <th>File Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Uploader</th>
                <th>Date</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($files as $file)
                <tr>
                    <td>{{ $file->file_name }}</td>
                    <td>{{ $file->mime_type }}</td>
                    <td>{{ number_format($file->size / 1024, 2) }} KB</td>
                    <td>{{ $file->uploader->name ?? '-' }}</td>
                    <td>{{ $file->created_at->format('Y-m-d H:i') }}</td>
                    <td class="flex gap-2">
                        <a href="{{ Storage::disk('public')->url($file->file_path) }}" target="_blank">
                            <x-icon name="fas.download" class="w-5 h-5 text-primary"/>
                        </a>
                        @if(auth()->id() === $file->uploaded_by)
                            <button wire:click.live="deleteFile({{ $file->id }})" class="text-error" title="Delete">
                                <x-icon name="fas.trash" class="w-5 h-5"/>
                            </button>
                        @endif
                        <x-button wire:click="showComments({{ $file->id }})" icon="fas.comments" class="text-info"
                                  title="Comments"/>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No files found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($showCommentsModal)
        <x-modal wire:model="showCommentsModal" max-width="lg" wire:close.live="closeComments">

            File Comments
            <div id="file-comments-list" class="space-y-4 max-h-72 overflow-y-auto">
                @forelse($comments as $comment)
                    <div class="flex items-start gap-2">
                        <x-icon name="fas.user" class="w-4 h-4 text-primary mt-1"/>
                        <div>
                            <div
                                class="font-semibold text-sm">{{ $comment->user->name ?? 'User#'.$comment->user_id }}</div>
                            <div
                                class="text-xs text-gray-400 mb-1">{{ $comment->created_at->diffForHumans() }}</div>
                            <div class="text-sm">{{ $comment->comment }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400">No comments yet.</div>
                @endforelse
            </div>
            <div class="mt-4">
                <x-input type="text" wire:model.live="newComment" label="Add Comment"
                         placeholder="Type your comment..."/>
                @if($commentError)
                    <x-alert color="error" class="mt-2">{{ $commentError }}</x-alert>
                @endif
                <div class="mt-2 flex justify-end">
                    <x-button color="primary"
                              wire:click="addComment; $nextTick(() => { document.getElementById('file-comments-list').scrollTop = document.getElementById('file-comments-list').scrollHeight })"
                              :disabled="$commentLoading || !$newComment">
                        <x-icon name="fas.paper-plane" class="w-4 h-4 mr-1"/>
                        Send
                    </x-button>
                </div>
            </div>
        </x-modal>
    @endif
</div>

