<?php

use App\Models\Project;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new class extends Livewire\Volt\Component {
    use Toast;

    public Project $project;

    public string $title = '';
    public string $content = '';
    public array $selectedCategories = [];
    public $categories = [];
    public bool $openCategoryModal = false;

    public function mount(): void
    {
        $this->loadCategories();
    }

    #[On('reload-categories')]
    public function reloadCategories(): void
    {
        $this->loadCategories();
    }

    public function loadCategories(): void
    {
        $this->categories = WikiCategory::where('project_id', $this->project->id)
            ->orderBy('name')
            ->get();
    }

    public function createPage(): void
    {
        $this->validate([
            'title' => 'required|min:3',
            'content' => 'required',
        ], [
            'title.required' => 'Başlık alanı zorunludur.',
            'title.min' => 'Başlık en az 3 karakter olmalıdır.',
            'content.required' => 'İçerik alanı zorunludur.',
        ]);

        $slug = WikiPage::createSlug($this->title);

        // Slug'ın benzersiz olup olmadığını kontrol et
        $existingPage = WikiPage::where('project_id', $this->project->id)
            ->where('slug', $slug)
            ->first();

        if ($existingPage) {
            $this->error('Bu başlıkla bir sayfa zaten mevcut!');
            return;
        }

        // Wiki sayfasını oluştur
        $page = WikiPage::create([
            'project_id' => $this->project->id,
            'title' => $this->title,
            'slug' => $slug,
            'content' => $this->content,
            'is_auto_generated' => false,
            'last_updated_at' => now(),
        ]);

        // Kategorileri ekle
        if (!empty($this->selectedCategories)) {
            $page->categories()->attach($this->selectedCategories);
        }

        $this->success('Wiki sayfası başarıyla oluşturuldu!');
        $this->redirect("/projects/{$this->project->id}/wiki/{$slug}");
    }

    public function createCategory(): void
    {
        $this->openCategoryModal = true;
        $this->dispatch('open-modal', 'create-wiki-category');
    }

    #[On('close-modal')]
    public function closeCategoryModal(): void
    {
        $this->openCategoryModal = false;
        $this->dispatch('close-modal', 'create-wiki-category');
    }
}

?>

<div>
    <x-slot:title>Yeni Wiki Sayfası</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-2">
                <x-button link="/projects/{{ $project->id }}/wiki" icon="o-arrow-left" class="btn-ghost btn-sm"/>
                <h1 class="text-2xl font-bold text-primary">Yeni Wiki Sayfası</h1>
            </div>
        </div>

        <x-card>
            <form wire:submit="createPage">
                <div class="space-y-6">
                    <div>
                        <x-input
                            wire:model="title"
                            label="Başlık"
                            placeholder="Sayfa başlığı"
                            required
                        />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <div for="categories">Kategoriler</div>
                            <x-button
                                wire:click="createCategory"
                                type="button"
                                icon="o-plus"
                                class="btn-sm btn-ghost"
                                label="Yeni Kategori"
                            />
                        </div>

                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach($categories as $category)
                                <label
                                    class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-base-200">
                                    <x-checkbox wire:model="selectedCategories" value="{{ $category->id }}"/>
                                    <span>{{ $category->name }}</span>
                                </label>
                            @endforeach

                            @if($categories->isEmpty())
                                <div class="text-sm text-gray-500">
                                    Henüz kategori bulunmuyor. Yeni bir kategori ekleyin.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div for="content">İçerik</div>
                        <div class="mb-2 text-sm text-gray-500">
                            Markdown formatında yazabilirsiniz.
                        </div>
                        <x-textarea
                            wire:model="content"
                            id="content"
                            rows="15"
                            class="font-mono text-sm"
                            placeholder="# Başlık

## Alt Başlık

Bu bir örnek içeriktir. **Kalın**, *italik* veya `kod` yazabilirsiniz.

- Liste öğesi 1
- Liste öğesi 2

[Bağlantı](https://example.com)"
                        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-button
                            link="/projects/{{ $project->id }}/wiki"
                            label="İptal"
                            class="btn-outline"
                        />
                        <x-button
                            type="submit"
                            label="Kaydet"
                            class="btn-primary"
                        />
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    <x-modal wire:model="openCategoryModal" name="create-wiki-category" title="Yeni Kategori">
        <livewire:wiki.create-category :project="$project"/>
    </x-modal>
</div>
