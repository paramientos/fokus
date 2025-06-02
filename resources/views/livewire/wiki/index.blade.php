<?php

use App\Models\Project;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public Project $project;
    public $categories = [];
    public $pages = [];
    public $selectedCategory = null;

    public function mount(): void
    {
        $this->loadWikiData();
    }

    public function loadWikiData()
    {
        $this->categories = WikiCategory::where('project_id', $this->project->id)
            ->withCount('pages')
            ->orderBy('name')
            ->get();

        $query = WikiPage::where('project_id', $this->project->id);

        if ($this->selectedCategory) {
            $category = WikiCategory::where('project_id', $this->project->id)
                ->where('id', $this->selectedCategory)
                ->firstOrFail();

            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('wiki_categories.id', $category->id);
            });
        }

        $this->pages = $query->orderBy('title')->get();
    }

    public function selectCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
        $this->loadWikiData();
    }

    public bool $showForceOption = false;
    public bool $forceGeneration = false;

    public function toggleForceOption()
    {
        $this->showForceOption = !$this->showForceOption;
    }

    public function generateDocumentation()
    {
        try {
            app(\App\Services\WikiGeneratorService::class)->generateWikiFromTasks($this->project, $this->forceGeneration);
            $this->loadWikiData();
            $this->success($this->forceGeneration
                ? 'Tüm dokümantasyon yeniden oluşturuldu!'
                : 'Dokümantasyon başarıyla oluşturuldu!');

            // İşlem tamamlandıktan sonra seçenekleri sıfırla
            $this->showForceOption = false;
            $this->forceGeneration = false;
        } catch (\Exception $e) {
            $this->error('Dokümantasyon oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }
}

?>

<div>
    <x-slot:title>{{ $project->name }} - Wiki</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">{{ $project->name }} Wiki</h1>
            </div>

            <div class="flex gap-2">
                <div class="dropdown dropdown-end">
                    <x-button
                        wire:click="toggleForceOption"
                        label="Otomatik Dokümantasyon Oluştur"
                        icon="o-document-text"
                        class="btn-primary"
                    />
                    @if($showForceOption)
                        <div class="dropdown-content z-[1] menu p-4 shadow bg-base-100 rounded-box w-72 mt-2">
                            <div class="space-y-4">
                                <div class="form-control">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <input type="checkbox" wire:model.live="forceGeneration"
                                               class="checkbox checkbox-primary"/>
                                        <span class="label-text">Tüm dokümantasyonu yeniden oluştur</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Bu seçenek tüm sayfaları yeniden oluşturur,
                                        manuel düzenlemeler kaybolabilir.</p>
                                </div>
                                <div class="flex justify-end">
                                    <x-button
                                        wire:click="generateDocumentation"
                                        label="Oluştur"
                                        class="btn-sm btn-primary"
                                    />
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <x-button
                    link="/projects/{{ $project->id }}/wiki/create"
                    label="Yeni Sayfa"
                    icon="o-plus"
                    class="btn-outline"
                />
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">
            <!-- Sol Menü - Kategoriler -->
            <div class="col-span-3">
                <x-card>
                    <div class="font-bold mb-3">Kategoriler</div>

                    <div class="space-y-1">
                        <x-button
                            wire:click="selectCategory(null)"
                            label="Tüm Sayfalar"
                            class="{{ $selectedCategory === null ? 'btn-primary' : 'btn-ghost' }} w-full justify-start"
                        />

                        @foreach($categories as $category)
                            <x-button
                                wire:click="selectCategory({{ $category->id }})"
                                label="{{ $category->name }} ({{ $category->pages_count }})"
                                class="{{ $selectedCategory === $category->id ? 'btn-primary' : 'btn-ghost' }} w-full justify-start"
                            />
                        @endforeach
                    </div>
                </x-card>
            </div>

            <!-- Sağ İçerik - Wiki Sayfaları -->
            <div class="col-span-9">
                <x-card>
                    <div class="font-bold mb-3">
                        {{ $selectedCategory ? $categories->firstWhere('id', $selectedCategory)->name : 'Tüm Sayfalar' }}
                    </div>

                    @if($pages->isEmpty())
                        <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                            <x-icon name="o-document-text" class="w-16 h-16 mb-4"/>
                            <p class="text-lg">Henüz wiki sayfası bulunmuyor</p>
                            <p class="text-sm mt-2">Yeni bir sayfa ekleyin veya otomatik dokümantasyon oluşturun</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($pages as $page)
                                <a
                                    href="/projects/{{ $project->id }}/wiki/{{ $page->slug }}"
                                    class="card bg-base-200 hover:bg-base-300 transition-colors"
                                >
                                    <div class="card-body p-4">
                                        <h3 class="card-title text-base">{{ $page->title }}</h3>

                                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                                            <span>{{ $page->is_auto_generated ? 'Otomatik' : 'Manuel' }}</span>
                                            <span>{{ $page->updated_at->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</div>
