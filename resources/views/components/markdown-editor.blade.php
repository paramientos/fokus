@props(['id', 'label' => null, 'placeholder' => 'Write your description here...', 'value' => ''])

<div class="form-control">
    @if ($label)
        <label for="{{ $id }}" class="label">
            <span class="label-text">{{ $label }}</span>
        </label>
    @endif

    <div class="markdown-editor">
        <div class="toolbar">
            <button type="button" data-action="heading" title="Heading">
                <x-icon name="fas.heading" class="w-4 h-4" />
            </button>
            <button type="button" data-action="bold" title="Bold">
                <x-icon name="fas.bold" class="w-4 h-4" />
            </button>
            <button type="button" data-action="italic" title="Italic">
                <x-icon name="fas.italic" class="w-4 h-4" />
            </button>
            
            <div class="separator"></div>
            
            <button type="button" data-action="bullet-list" title="Bullet List">
                <x-icon name="fas.list-ul" class="w-4 h-4" />
            </button>
            <button type="button" data-action="ordered-list" title="Numbered List">
                <x-icon name="fas.list-ol" class="w-4 h-4" />
            </button>
            <button type="button" data-action="task-list" title="Task List">
                <x-icon name="fas.list-check" class="w-4 h-4" />
            </button>
            
            <div class="separator"></div>
            
            <button type="button" data-action="code-block" title="Code Block">
                <x-icon name="fas.code" class="w-4 h-4" />
            </button>
            <button type="button" data-action="blockquote" title="Quote">
                <x-icon name="fas.quote-left" class="w-4 h-4" />
            </button>
            <button type="button" data-action="horizontal-rule" title="Horizontal Line">
                <x-icon name="fas.minus" class="w-4 h-4" />
            </button>
            <button type="button" data-action="link" title="Link">
                <x-icon name="fas.link" class="w-4 h-4" />
            </button>
        </div>
        
        <div class="content border-2 border-dashed border-gray-200 hover:border-primary focus:border-primary transition-colors"></div>
        
        <input type="hidden" id="{{ $id }}" name="{{ $id }}" class="input" {{ $attributes->wire('model') }} value="{{ $value }}">
    </div>
    
    @error($attributes->wire('model')->value())
        <span class="text-error text-sm">{{ $message }}</span>
    @enderror
</div>
