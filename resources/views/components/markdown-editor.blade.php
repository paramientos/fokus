@props([
    'id' => 'markdown-editor-' . uniqid(),
    'label' => null,
    'value' => '',
])

<div class="form-control">
    @if ($label)
        <label for="{{ $id }}" class="label">
            <span class="label-text">{{ $label }}</span>
        </label>
    @endif

    <div class="markdown-editor-container">
        <textarea id="{{ $id }}" name="{{ $id }}" {{ $attributes->wire('model') }}>{{ $value }}</textarea>
    </div>

    <!-- EasyMDE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    
    <!-- EasyMDE JS -->
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('{{ $id }}');
            
            // Initialize EasyMDE
            const easyMDE = new EasyMDE({
                element: textarea,
                spellChecker: false,
                autofocus: false,
                toolbar: [
                    'heading', 'bold', 'italic', '|',
                    'unordered-list', 'ordered-list', 'task', '|',
                    'code', 'quote', 'table', '|',
                    'link', 'image', '|',
                    'preview', 'side-by-side', 'fullscreen'
                ],
                status: false,
                tabSize: 4,
                renderingConfig: {
                    singleLineBreaks: false,
                    codeSyntaxHighlighting: true,
                },
            });

            // Handle Livewire updates
            if (textarea.hasAttribute('wire:model') || textarea.hasAttribute('wire:model.defer')) {
                // Update Livewire model when editor changes
                easyMDE.codemirror.on('change', () => {
                    const value = easyMDE.value();
                    textarea.value = value;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                });

                // Listen for Livewire updates
                document.addEventListener('livewire:update', () => {
                    if (textarea.value !== easyMDE.value()) {
                        easyMDE.value(textarea.value);
                    }
                });
            }
        });
    </script>

    @error($attributes->wire('model')->value())
        <span class="text-error text-sm">{{ $message }}</span>
    @enderror
</div>
