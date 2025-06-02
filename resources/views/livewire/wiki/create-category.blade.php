<?php

use App\Models\Project;
use App\Models\WikiCategory;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;

    public Project $project;
    public string $name = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function createCategory(): void
    {
        $this->validate([
            'name' => 'required|min:2',
        ], [
            'name.required' => 'Kategori adı zorunludur.',
            'name.min' => 'Kategori adı en az 2 karakter olmalıdır.',
        ]);

        $slug = str()->slug($this->name);

        // Slug'ın benzersiz olup olmadığını kontrol et
        $existingCategory = WikiCategory::where('project_id', $this->project->id)
            ->where('slug', $slug)
            ->first();

        if ($existingCategory) {
            $this->error('Bu isimle bir kategori zaten mevcut!');
            return;
        }

        // Kategoriyi oluştur
        WikiCategory::create([
            'project_id' => $this->project->id,
            'name' => $this->name,
            'slug' => $slug,
        ]);

        $this->success('Kategori başarıyla oluşturuldu!');
        $this->dispatch('close-modal', 'create-wiki-category');
        $this->dispatch('category-created');
        $this->name = '';

        // Ana bileşene kategorileri yeniden yüklemesi için bildirim gönder
        $this->dispatch('reload-categories');
    }
}

?>

<div>
    <form wire:submit="createCategory" class="space-y-4">
        <x-input
            wire:model="name"
            label="Kategori Adı"
            placeholder="Örn: Teknik Dokümantasyon"
            required
        />

        <div class="flex justify-end gap-2">
            <x-button
                x-on:click="$dispatch('close-modal', 'create-wiki-category')"
                label="İptal"
                class="btn-outline"
            />
            <x-button
                type="submit"
                label="Oluştur"
                class="btn-primary"
            />
        </div>
    </form>
</div>
