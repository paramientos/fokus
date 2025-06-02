<?php

use App\Models\Project;
use App\Models\WikiPage;
use Mary\Traits\Toast;

new class extends Livewire\Volt\Component {
    use Toast;
    
    public $projects = [];
    public $recentPages = [];
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        // Kullanıcının erişimi olan projeleri yükle
        $this->projects = Project::with(['wikiCategories' => function($query) {
                $query->withCount('pages');
            }])
            ->withCount('wikiPages')
            ->orderBy('name')
            ->get();
            
        // Son eklenen wiki sayfalarını yükle
        $this->recentPages = WikiPage::with('project')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
}

?>

<div>
    <x-slot:title>Wiki - Fokus</x-slot:title>

    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Proje Dokümantasyonu</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Sol Taraf - Projeler -->
            <div class="md:col-span-8">
                <x-card>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Projeler</h2>
                    </div>

                    @if($projects->isEmpty())
                        <div class="p-6 text-center">
                            <x-icon name="o-document-text" class="w-12 h-12 mx-auto text-gray-400"/>
                            <p class="mt-2 text-gray-500">Henüz hiçbir projenin wiki sayfası bulunmuyor.</p>
                            <p class="text-sm text-gray-400 mt-1">Bir projeye gidip wiki sayfası oluşturabilirsiniz.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($projects as $project)
                                <div class="border rounded-lg p-4 hover:bg-base-200 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="/projects/{{ $project->id }}/wiki" class="text-lg font-medium text-primary hover:underline">
                                                {{ $project->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">
                                                {{ $project->wiki_pages_count }} sayfa, {{ $project->wikiCategories->count() }} kategori
                                            </p>
                                        </div>
                                        <x-button 
                                            link="/projects/{{ $project->id }}/wiki" 
                                            icon="o-arrow-right" 
                                            class="btn-sm btn-ghost"
                                        />
                                    </div>
                                    
                                    @if($project->wikiCategories->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($project->wikiCategories->take(5) as $category)
                                                <span class="badge badge-sm">
                                                    {{ $category->name }} ({{ $category->pages_count }})
                                                </span>
                                            @endforeach
                                            
                                            @if($project->wikiCategories->count() > 5)
                                                <span class="badge badge-sm badge-ghost">
                                                    +{{ $project->wikiCategories->count() - 5 }} daha
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
            
            <!-- Sağ Taraf - Son Eklenenler -->
            <div class="md:col-span-4">
                <x-card>
                    <h2 class="text-xl font-bold mb-4">Son Eklenen Sayfalar</h2>
                    
                    @if($recentPages->isEmpty())
                        <div class="p-4 text-center">
                            <p class="text-gray-500">Henüz hiçbir wiki sayfası oluşturulmadı.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recentPages as $page)
                                <div class="border-b last:border-b-0 pb-2 last:pb-0">
                                    <a href="/projects/{{ $page->project_id }}/wiki/{{ $page->slug }}" class="font-medium hover:text-primary">
                                        {{ $page->title }}
                                    </a>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-500">
                                            {{ $page->project->name }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $page->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
                
                <div class="mt-6">
                    <x-card>
                        <h2 class="text-xl font-bold mb-4">Hakkında</h2>
                        <p class="text-sm">Wiki sistemi, projelerinizin dokümantasyonunu otomatik olarak oluşturur ve yönetir. Task açıklamaları ve yorumlardan oluşturulan sayfalar, projelerinizin bilgi tabanını oluşturur.</p>
                        
                        <div class="mt-4">
                            <h3 class="font-bold text-sm">Özellikler:</h3>
                            <ul class="list-disc list-inside text-sm mt-2 space-y-1">
                                <li>Otomatik dokümantasyon oluşturma</li>
                                <li>Kategorilere göre düzenleme</li>
                                <li>Task ve yorumlardan içerik oluşturma</li>
                                <li>Manuel düzenleme imkanı</li>
                            </ul>
                        </div>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</div>
