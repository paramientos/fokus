@props([
    'id' => 'markdown-editor-' . uniqid(),
    'label' => null,
    'value' => '',
])

<div class="form-control" x-data="{ editorInitialized: false }">
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
            if (!textarea) return;

            // Initialize EasyMDE
            const easyMDE = new EasyMDE({
                element: textarea,
                spellChecker: false,
                autofocus: false,
                initialValue: textarea.value || '',
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
                    textarea.value = easyMDE.value();
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                });

                // Listen for Livewire updates
                document.addEventListener('livewire:update', () => {
                    if (textarea.value !== easyMDE.value()) {
                        easyMDE.value(textarea.value);
                    }
                });
                
                // Livewire Alpine init hook for proper initialization
                document.addEventListener('livewire:initialized', () => {
                    if (textarea.value && textarea.value !== easyMDE.value()) {
                        easyMDE.value(textarea.value);
                    }
                });
            }
            
            // Livewire component hook for proper initialization
            if (window.Livewire) {
                window.Livewire.hook('component.initialized', (component) => {
                    // Check if this component contains our textarea
                    if (document.getElementById('{{ $id }}') && 
                        document.getElementById('{{ $id }}').value !== easyMDE.value()) {
                        easyMDE.value(document.getElementById('{{ $id }}').value);
                    }
                });
                
                // Handle Livewire model updates
                window.Livewire.hook('message.processed', (message, component) => {
                    if (document.getElementById('{{ $id }}')) {
                        const updatedValue = document.getElementById('{{ $id }}').value;
                        if (updatedValue !== easyMDE.value()) {
                            easyMDE.value(updatedValue);
                        }
                    }
                });
            }
        });
    </script>

    @error($attributes->wire('model')->value())
        <span class="text-error text-sm">{{ $message }}</span>
    @enderror
</div>
