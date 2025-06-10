@props(['content' => ''])

<div {{ $attributes->merge(['class' => 'markdown-content prose']) }}>
    @if(empty($content))
        <p class="text-gray-400">No description provided.</p>
    @else
        {!! \Illuminate\Support\Str::markdown($content) !!}
    @endif
</div>

<style>
    .markdown-content {
        max-width: 100%;
    }
    
    .markdown-content h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: hsl(var(--bc));
    }
    
    .markdown-content h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: hsl(var(--bc));
    }
    
    .markdown-content h3 {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: hsl(var(--bc));
    }
    
    .markdown-content p {
        margin-bottom: 1rem;
        color: hsl(var(--bc));
    }
    
    .markdown-content ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .markdown-content ol {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .markdown-content blockquote {
        border-left: 3px solid hsl(var(--p));
        padding-left: 1rem;
        margin-left: 0;
        margin-right: 0;
        margin-bottom: 1rem;
        color: hsl(var(--bc) / 0.8);
    }
    
    .markdown-content pre {
        background-color: hsl(var(--b2));
        padding: 0.75rem;
        border-radius: 0.25rem;
        overflow-x: auto;
        margin-bottom: 1rem;
    }
    
    .markdown-content code {
        font-family: monospace;
        background-color: hsl(var(--b2));
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
    }
    
    .markdown-content hr {
        border: none;
        border-top: 1px solid hsl(var(--bc) / 0.2);
        margin: 1rem 0;
    }
    
    .markdown-content a {
        color: hsl(var(--p));
        text-decoration: underline;
    }
    
    .markdown-content .task-list {
        list-style-type: none;
        padding-left: 0;
    }
    
    .markdown-content .task-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 0.25rem;
    }
    
    .markdown-content .task-item input[type="checkbox"] {
        margin-right: 0.5rem;
        margin-top: 0.25rem;
    }
</style>
