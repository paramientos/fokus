<?php

use App\Models\Project;
use App\Models\WikiPage;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public Project $project;
    public string $slug;

    public WikiPage $page;
    public $isEditing = false;
    public $editContent;
    public $relatedPages = [];

    public function mount(): void
    {
        $this->page = WikiPage::where('project_id', $this->project->id)
            ->where('slug', $this->slug)
            ->firstOrFail();

        $this->loadRelatedPages();
    }

    public function loadRelatedPages()
    {
        // Aynı kategorideki diğer sayfaları yükle
        $categoryIds = $this->page->categories->pluck('id');

        if ($categoryIds->isNotEmpty()) {
            $this->relatedPages = WikiPage::where('project_id', $this->project->id)
                ->where('id', '!=', $this->page->id)
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('wiki_categories.id', $categoryIds);
                })
                ->orderBy('title')
                ->limit(5)
                ->get();
        }
    }

    public function startEditing()
    {
        $this->isEditing = true;
        $this->editContent = $this->page->content;
    }

    public function cancelEditing()
    {
        $this->isEditing = false;
        $this->editContent = null;
    }

    public function saveEdits()
    {
        $this->page->update([
            'content' => $this->editContent,
            'is_auto_generated' => false,
            'last_updated_at' => now(),
        ]);

        $this->isEditing = false;
        $this->success('Sayfa başarıyla güncellendi!');
    }

    public bool $showForceConfirm = false;

    public function confirmRegenerate()
    {
        $this->showForceConfirm = true;
    }

    public function cancelRegenerate()
    {
        $this->showForceConfirm = false;
    }
    
    /**
     * Markdown içeriğini HTML'e dönüştür
     * 
     * @param string $markdown Markdown içeriği
     * @return string HTML içeriği
     */
    public function renderMarkdown(string $markdown): string
    {
        return Str::markdown($markdown);
    }
    
    public function insertMarkdown(string $markdown): void
    {
        $this->editContent .= "\n" . $markdown;
    }
    
    /**
     * Seçili metni formatla
     * 
     * @param string $prefix Başlangıç formatı
     * @param string $suffix Bitiş formatı
     * @return void
     */
    public function formatMarkdown(string $prefix, string $suffix): void
    {
        // Tarayıcıdan seçim bilgisini al
        $selection = $this->js("window.getSelection ? window.getSelection().toString() : document.selection.createRange().text");
        
        // Seçili metin varsa formatla, yoksa ekleme yap
        if ($selection) {
            // Textarea içeriğini al
            $content = $this->editContent;
            
            // Textarea'da seçili metni bul
            $pos = strpos($content, $selection);
            
            if ($pos !== false) {
                // Seçili metni formatla
                $beforeText = substr($content, 0, $pos);
                $afterText = substr($content, $pos + strlen($selection));
                $this->editContent = $beforeText . $prefix . $selection . $suffix . $afterText;
                
                // İmleç pozisyonunu güncelle
                $newPosition = $pos + strlen($prefix) + strlen($selection) + strlen($suffix);
                $this->dispatch('editorSelectionUpdated', ['newPosition' => $newPosition]);
            } else {
                // Seçili metin bulunamadıysa sona ekle
                $this->insertMarkdown($prefix . $selection . $suffix);
            }
        } else {
            // Seçili metin yoksa normal ekleme yap
            $this->insertMarkdown($prefix . $suffix);
        }
    }

    public function regenerate()
    {
        try {
            // İlişkili task'ları kontrol et (morph ilişkisini kullanarak)
            $tasks = \App\Models\Task::whereHas('wikiPages', function ($query) {
                $query->where('wiki_pages.id', $this->page->id);
            })->get();

            // Geriye dönük uyumluluk için source_references alanını da kontrol et
            if ($tasks->isEmpty()) {
                $sourceRefs = $this->page->source_references;

                if (!empty($sourceRefs) && isset($sourceRefs['task_id'])) {
                    $task = \App\Models\Task::find($sourceRefs['task_id']);
                    if ($task) {
                        $tasks = collect([$task]);
                    }
                }
            }

            if ($tasks->isEmpty()) {
                $this->error('Bu sayfa otomatik olarak yeniden oluşturulamaz!');
                return;
            }

            // Ana task'tan içeriği yeniden oluştur
            $task = $tasks->first();
            $service = app(\App\Services\WikiGeneratorService::class);
            $content = $service->generateContentFromTask($task);

            // Sayfayı güncelle
            $this->page->update([
                'content' => $content,
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]);

            // İlişkileri güncelle
            if (!$task->wikiPages()->where('wiki_pages.id', $this->page->id)->exists()) {
                $task->wikiPages()->attach($this->page->id);
            }

            $this->showForceConfirm = false;
            $this->success('Sayfa başarıyla yeniden oluşturuldu!');
        } catch (\Exception $e) {
            $this->error('Sayfa yeniden oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }
}

?>

<div>
    <x-slot:title>{{ $page->title }} - Wiki</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/wiki" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">{{ $page->title }}</h1>
            </div>

            <div class="flex gap-2">
                @if($page->is_auto_generated)
                    <x-button
                        wire:click="confirmRegenerate"
                        label="Yeniden Oluştur"
                        icon="o-arrow-path"
                        class="btn-outline"
                    />

                    <!-- Onay İletişim Kutusu -->
                    @if($showForceConfirm)
                        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                            <div class="bg-base-100 p-6 rounded-lg shadow-lg max-w-md w-full">
                                <h3 class="text-lg font-bold mb-4">Sayfayı Yeniden Oluştur</h3>
                                <p class="mb-4">Bu sayfa otomatik olarak oluşturulmuştur. Yeniden oluşturmak, manuel
                                    yaptığınız tüm değişiklikleri kaybetmenize neden olabilir.</p>
                                <p class="text-sm text-gray-500 mb-6">Sayfa, ilgili task'tan yeniden
                                    oluşturulacaktır.</p>

                                <div class="flex justify-end gap-2">
                                    <x-button
                                        wire:click="cancelRegenerate"
                                        label="İptal"
                                        class="btn-ghost"
                                    />
                                    <x-button
                                        wire:click="regenerate"
                                        label="Yeniden Oluştur"
                                        class="btn-primary"
                                    />
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if($isEditing)
                    <x-button
                        wire:click="saveEdits"
                        label="Kaydet"
                        icon="o-check"
                        class="btn-primary"
                    />
                    <x-button
                        wire:click="cancelEditing"
                        label="İptal"
                        icon="o-x-mark"
                        class="btn-outline"
                    />
                @else
                    <x-button
                        wire:click="startEditing"
                        label="Düzenle"
                        icon="o-pencil"
                        class="btn-outline"
                    />
                @endif
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- Ana İçerik -->
            <div class="col-span-9">
                <x-card>
                    @if($isEditing)
                        <div class="space-y-4">
                            <div class="mb-3 flex justify-between items-center">
                                <div class="text-sm font-medium">Düzenle</div>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('**', '**')" title="Kalın">
                                        <x-fas-bold class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('*', '*')" title="İtalik">
                                        <x-fas-italic class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('# ', '')" title="Başlık">
                                        <x-fas-heading class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('[', '](https://ornek.com)')" title="Bağlantı">
                                        <x-fas-link class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('- ', '')" title="Liste">
                                        <x-fas-list-ul class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('1. ', '')" title="Sıralı Liste">
                                        <x-fas-list-ol class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('> ', '')" title="Alıntı">
                                        <x-fas-quote-right class="w-4 h-4" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" wire:click="formatMarkdown('```\n', '\n```')" title="Kod Bloğu">
                                        <x-fas-code class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            
                            <div class="border rounded-lg p-4">
                                <x-textarea
                                    id="markdown-editor"
                                    wire:model.live="editContent"
                                    x-data="{}"
                                    x-init="
                                        $el.addEventListener('selectionchange', function() {
                                            window.currentEditor = $el;
                                        });
                                        $el.addEventListener('focus', function() {
                                            window.currentEditor = $el;
                                        });
                                    "
                                    rows="20"
                                    class="w-full font-mono text-sm"
                                    placeholder="İçerik yazabilirsiniz..."
                                />
                            </div>
                        </div>
                        
                        <script>
                            window.currentEditor = null;
                            document.addEventListener('livewire:initialized', () => {
                                @this.on('editorSelectionUpdated', (data) => {
                                    if (window.currentEditor) {
                                        window.currentEditor.focus();
                                        window.currentEditor.setSelectionRange(data.newPosition, data.newPosition);
                                    }
                                });
                            });
                        </script>
                        
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <div>Markdown formatında düzenleyebilirsiniz.</div>
                            <div>
                                <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" class="link link-primary">Markdown Rehberi</a>
                            </div>
                        </div>
                        
                        <div class="mt-4 border rounded-lg p-4">
                            <div class="text-sm font-medium mb-2">Önizleme</div>
                            <div class="prose max-w-none">
                                {!! Str::markdown($editContent) !!}
                            </div>
                        </div>
                        </div>
                    @else
                        <div class="prose max-w-none">
                            {!! Str::markdown($page->content) !!}
                        </div>
                    @endif
                </x-card>

                <div class="mt-4 text-sm text-gray-500 flex justify-between">
                    <div>
                        <span>{{ $page->is_auto_generated ? 'Otomatik oluşturuldu' : 'Manuel düzenlendi' }}</span>
                        @if($page->last_updated_at)
                            <span> • Son güncelleme: {{ $page->last_updated_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>

                    <div>
                        @foreach($page->categories as $category)
                            <span class="badge badge-sm">{{ $category->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sağ Kenar Çubuğu -->
            <div class="col-span-3">
                <x-card>
                    <div class="font-bold mb-3">İlgili Sayfalar</div>

                    @if($relatedPages->isEmpty())
                        <div class="text-sm text-gray-500">
                            İlgili sayfa bulunamadı.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($relatedPages as $relatedPage)
                                <a
                                    href="/projects/{{ $project->id }}/wiki/{{ $relatedPage->slug }}"
                                    class="block p-2 rounded hover:bg-base-200 transition-colors"
                                >
                                    <div class="font-medium">{{ $relatedPage->title }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $relatedPage->updated_at->format('d.m.Y') }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                @if($page->is_auto_generated && !empty($page->source_references))
                    <x-card class="mt-4">
                        <div class="font-bold mb-3">Kaynak Bilgileri</div>

                        <div class="text-sm">
                            @if(isset($page->source_references['task_id']))
                                <div class="mb-2">
                                    <div class="font-medium">Görev:</div>
                                    <a
                                        href="/projects/{{ $project->id }}/tasks/{{ $page->source_references['task_id'] }}"
                                        class="link link-primary"
                                    >
                                        {{ $project->key }}-{{ $page->source_references['task_id'] }}
                                    </a>
                                </div>
                            @endif

                            @if(isset($page->source_references['status']))
                                <div class="mb-2">
                                    <div class="font-medium">Durum:</div>
                                    <span>{{ $page->source_references['status'] }}</span>
                                </div>
                            @endif

                            @if(isset($page->source_references['task_type']))
                                <div class="mb-2">
                                    <div class="font-medium">Görev Tipi:</div>
                                    <span>{{ ucfirst($page->source_references['task_type']) }}</span>
                                </div>
                            @endif
                        </div>
                    </x-card>
                @endif
            </div>
        </div>
    </div>
</div>
