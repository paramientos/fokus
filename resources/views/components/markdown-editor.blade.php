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

    <!-- Trix CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.css">

    <!-- Trix JS -->
    <script src="https://cdn.jsdelivr.net/npm/trix@2.0.8/dist/trix.umd.min.js"></script>

    <!-- Hidden input for Livewire model binding -->
    <input id="{{ $id }}_input" type="hidden" name="{{ $id }}" {{ $attributes->wire('model') }} value="{{ $value }}">

    <!-- Trix Editor -->
    <trix-editor input="{{ $id }}_input" class="trix-content border-2 border-gray-200 rounded-lg min-h-[200px]"></trix-editor>

    <style>
        .trix-button-group {
            border-color: #e5e7eb !important;
        }

        .trix-button {
            border-bottom: none !important;
        }

        .trix-content {
            min-height: 200px;
            padding: 0.75rem;
        }

        .trix-content h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .trix-content table {
            border-collapse: collapse;
            width: 100%;
            margin: 1rem 0;
        }

        .trix-content table td, .trix-content table th {
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
        }

        .trix-content table th {
            background-color: #f9fafb;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputElement = document.getElementById('{{ $id }}_input');
            const trixEditor = document.querySelector('trix-editor[input="{{ $id }}_input"]');

            if (!inputElement || !trixEditor) return;

            // Initialize editor with value
            if (inputElement.value) {
                setTimeout(() => {
                    trixEditor.editor.loadHTML(inputElement.value);
                }, 50);
            }

            // Handle Livewire updates
            if (inputElement.hasAttribute('wire:model') || inputElement.hasAttribute('wire:model.defer')) {
                // Listen for Trix editor changes
                trixEditor.addEventListener('trix-change', function(e) {
                    inputElement.value = trixEditor.innerHTML;
                    inputElement.dispatchEvent(new Event('input', { bubbles: true }));
                });

                // Listen for Livewire updates
                document.addEventListener('livewire:update', function() {
                    if (trixEditor.innerHTML !== inputElement.value) {
                        trixEditor.editor.loadHTML(inputElement.value);
                    }
                });

                // Livewire component hook for proper initialization
                if (window.Livewire) {
                    window.Livewire.hook('component.initialized', (component) => {
                        if (document.querySelector('trix-editor[input="{{ $id }}_input"]')) {
                            setTimeout(() => {
                                const editor = document.querySelector('trix-editor[input="{{ $id }}_input"]');
                                if (editor && inputElement.value) {
                                    editor.editor.loadHTML(inputElement.value);
                                }
                            }, 100);
                        }
                    });

                    // Handle Livewire model updates
                    window.Livewire.hook('message.processed', (message, component) => {
                        setTimeout(() => {
                            const editor = document.querySelector('trix-editor[input="{{ $id }}_input"]');
                            if (editor && inputElement.value && editor.innerHTML !== inputElement.value) {
                                editor.editor.loadHTML(inputElement.value);
                            }
                        }, 100);
                    });
                }
            }

            // Add support for pasting tables from Excel
            trixEditor.addEventListener('trix-paste', function(event) {
                const clipboardData = event.clipboardData || window.clipboardData;
                const pastedText = clipboardData.getData('text/plain');

                // Check if it looks like a table (contains tabs)
                if (pastedText && pastedText.includes('\t')) {
                    event.preventDefault();

                    // Convert TSV to HTML table
                    const rows = pastedText.trim().split(/[\r\n]+/);
                    if (rows.length > 0) {
                        let htmlTable = '<table border="1" style="width:100%; border-collapse: collapse;">';

                        // Header row
                        htmlTable += '<thead><tr>';
                        rows[0].split('\t').forEach(cell => {
                            htmlTable += `<th style="padding: 8px; border: 1px solid #ddd;">${cell}</th>`;
                        });
                        htmlTable += '</tr></thead>';

                        // Data rows
                        htmlTable += '<tbody>';
                        for (let i = 1; i < rows.length; i++) {
                            htmlTable += '<tr>';
                            rows[i].split('\t').forEach(cell => {
                                htmlTable += `<td style="padding: 8px; border: 1px solid #ddd;">${cell}</td>`;
                            });
                            htmlTable += '</tr>';
                        }
                        htmlTable += '</tbody></table>';

                        // Insert at cursor position
                        trixEditor.editor.insertHTML(htmlTable);
                    }
                }
            });
        });
    </script>

    @error($attributes->wire('model')->value())
        <span class="text-error text-sm">{{ $message }}</span>
    @enderror
</div>
