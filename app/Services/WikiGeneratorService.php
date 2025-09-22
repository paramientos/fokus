<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WikiGeneratorService
{
    /**
     * Task açıklamaları ve yorumlarından wiki sayfası oluştur
     *
     * @param  Project  $project  Dokümantasyon oluşturulacak proje
     * @param  bool  $force  Tüm dokümantasyonu yeniden oluşturmaya zorla
     */
    public function generateWikiFromTasks(Project $project, bool $force = false): void
    {
        // Proje için tüm task'ları al
        $tasks = $project->tasks()
            ->with(['comments', 'status'])
            ->get();

        // Task'ları statülerine göre grupla
        $tasksByStatus = $tasks->groupBy('status.name');

        // Her statü için bir wiki kategorisi oluştur
        foreach ($tasksByStatus as $statusName => $statusTasks) {
            $this->processTasksForStatus($project, $statusName, $statusTasks, $force);
        }

        // Task tipine göre grupla
        $tasksByType = $tasks->groupBy('task_type');

        // Her task tipi için bir wiki kategorisi oluştur
        foreach ($tasksByType as $taskType => $typeTasks) {
            if (!$taskType) {
                continue;
            } // Tipi olmayan task'ları atla

            $this->processTasksForType($project, $taskType, $typeTasks, $force);
        }

        // Teknik dokümantasyon sayfası oluştur
        $this->generateTechnicalDocumentation($project, $tasks, $force);

        // Kullanıcı dokümantasyonu oluştur
        $this->generateUserDocumentation($project, $tasks, $force);
    }

    /**
     * Belirli bir statüdeki task'lardan wiki sayfası oluştur
     */
    private function processTasksForStatus(Project $project, string $statusName, Collection $tasks, bool $force = false): void
    {
        // Kategori oluştur veya mevcut olanı al
        $category = $this->findOrCreateCategory($project, $statusName, 'status');

        // Her task için bir wiki sayfası oluştur
        foreach ($tasks as $task) {
            $this->createOrUpdateWikiPage($project, $task, $category, $force);
        }

        // Statü özeti sayfası oluştur
        $this->createStatusSummaryPage($project, $statusName, $tasks, $category, $force);
    }

    /**
     * Belirli bir tipteki task'lardan wiki sayfası oluştur
     */
    private function processTasksForType(Project $project, string $taskType, Collection $tasks, bool $force = false): void
    {
        // Kategori oluştur veya mevcut olanı al
        $categoryName = ucfirst($taskType);
        $category = $this->findOrCreateCategory($project, $categoryName, 'type');

        // Her task için bir wiki sayfası oluştur
        foreach ($tasks as $task) {
            $this->createOrUpdateWikiPage($project, $task, $category, $force);
        }

        // Task tipi özeti sayfası oluştur
        $this->createTypeSummaryPage($project, $taskType, $tasks, $category, $force);
    }

    /**
     * Bir task için wiki sayfası oluştur veya güncelle
     */
    private function createOrUpdateWikiPage(Project $project, Task $task, WikiCategory $category, bool $force = false): void
    {
        $title = "{$project->key}-{$task->id}: {$task->title}";
        $slug = WikiPage::createSlug($title);

        // Mevcut wiki sayfasını kontrol et
        $existingPage = WikiPage::where('project_id', $project->id)
            ->where('slug', $slug)
            ->first();

        // Eğer sayfa varsa ve force değilse ve otomatik oluşturulmuşsa ve son 24 saat içinde güncellenmiş ise, güncelleme
        if ($existingPage && !$force && $existingPage->is_auto_generated &&
            $existingPage->last_updated_at &&
            $existingPage->last_updated_at->diffInHours(now()) < 24) {
            return;
        }

        // İçerik oluştur
        $content = $this->generateContentFromTask($task);

        // Kaynak referanslarını hazırla (artık morph ilişkisi kullanacağız)
        $sourceReferences = [
            'task_id' => $task->id,
            'comment_ids' => $task->comments->pluck('id')->toArray(),
        ];

        // Wiki sayfasını oluştur veya güncelle
        $wikiPage = WikiPage::updateOrCreate(
            ['project_id' => $project->id, 'slug' => $slug],
            [
                'title' => $title,
                'content' => $content,
                'source_references' => $sourceReferences, // Geriye dönük uyumluluk için tutuyoruz
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]
        );

        // Kategori ilişkisini kur
        if (!$wikiPage->categories->contains($category->id)) {
            $wikiPage->categories()->attach($category->id);
        }

        // Kaynak ilişkilerini kur (Task)
        $task->wikiPages()->syncWithoutDetaching([$wikiPage->id]);

        // Yorumlar için de ilişki kurulabilir (eğer Comment modeli için de ilişki tanımlanmışsa)
        foreach ($task->comments as $comment) {
            if (method_exists($comment, 'wikiPages')) {
                $comment->wikiPages()->syncWithoutDetaching([$wikiPage->id]);
            }
        }
    }

    /**
     * Kategori oluştur veya mevcut olanı bul
     *
     * @param  Project  $project  Proje
     * @param  string  $name  Kategori adı
     * @param  string  $type  Kategori tipi (status, type, technical, user)
     * @return WikiCategory Oluşturulan veya bulunan kategori
     */
    private function findOrCreateCategory(Project $project, string $name, string $type): WikiCategory
    {
        return WikiCategory::firstOrCreate(
            [
                'project_id' => $project->id,
                'name' => $name,
                'type' => $type,
            ],
            [
                'slug' => Str::slug($name),
                'description' => "$name kategorisindeki wiki sayfaları",
            ]
        );
    }

    /**
     * Bir task'tan içerik oluştur
     */
    private function generateContentFromTask(Task $task): string
    {
        $content = "# {$task->title}\n\n";

        if ($task->description) {
            $content .= "## Açıklama\n\n{$task->description}\n\n";
        }

        $content .= "## Detaylar\n\n";
        $content .= "- **Durum:** {$task->status->name}\n";
        $content .= '- **Tip:** '.($task->task_type ? ucfirst($task->task_type->label()) : 'Belirtilmemiş')."\n";
        $content .= '- **Öncelik:** '.($task->priority ? ucfirst($task->priority->label()) : 'Belirtilmemiş')."\n";

        if ($task->user) {
            $content .= "- **Atanan:** {$task->user->name}\n";
        }

        if ($task->reporter) {
            $content .= "- **Raporlayan:** {$task->reporter->name}\n";
        }

        if ($task->story_points) {
            $content .= "- **Story Points:** {$task->story_points}\n";
        }

        if ($task->due_date) {
            $content .= '- **Bitiş Tarihi:** '.$task->due_date->format('d.m.Y')."\n";
        }

        // Yorumları ekle
        if ($task->comments->isNotEmpty()) {
            $content .= "\n## Yorumlar\n\n";

            foreach ($task->comments as $comment) {
                $content .= "### {$comment->user->name} - ".$comment->created_at->format('d.m.Y H:i')."\n\n";
                $content .= "{$comment->content}\n\n";
            }
        }

        return $content;
    }

    /**
     * Statü özeti sayfası oluştur
     */
    private function createStatusSummaryPage(Project $project, string $statusName, Collection $tasks, WikiCategory $category, bool $force = false): void
    {
        $title = "{$statusName} Durumundaki Görevler";
        $slug = WikiPage::createSlug($title);

        $content = "# {$title}\n\n";
        $content .= "Bu sayfada {$project->name} projesindeki {$statusName} durumundaki tüm görevlerin listesi bulunmaktadır.\n\n";

        $content .= "## Görev Listesi\n\n";
        foreach ($tasks as $task) {
            $taskPageSlug = WikiPage::createSlug("{$project->key}-{$task->id}: {$task->title}");
            $content .= "- [{$project->key}-{$task->id}: {$task->title}](/projects/{$project->id}/wiki/{$taskPageSlug})\n";
        }

        // Wiki sayfasını oluştur veya güncelle
        $wikiPage = WikiPage::updateOrCreate(
            ['project_id' => $project->id, 'slug' => $slug],
            [
                'title' => $title,
                'content' => $content,
                'source_references' => ['status' => $statusName, 'task_ids' => $tasks->pluck('id')->toArray()],
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]
        );

        // Kategori ilişkisini kur
        if (!$wikiPage->categories->contains($category->id)) {
            $wikiPage->categories()->attach($category->id);
        }
    }

    /**
     * Task tipi özeti sayfası oluştur
     */
    private function createTypeSummaryPage(Project $project, string $taskType, Collection $tasks, WikiCategory $category, bool $force = false): void
    {
        $typeName = ucfirst($taskType);
        $title = "{$typeName} Tipindeki Görevler";
        $slug = WikiPage::createSlug($title);

        $content = "# {$title}\n\n";
        $content .= "Bu sayfada {$project->name} projesindeki {$typeName} tipindeki tüm görevlerin listesi bulunmaktadır.\n\n";

        $content .= "## Görev Listesi\n\n";
        foreach ($tasks as $task) {
            $taskPageSlug = WikiPage::createSlug("{$project->key}-{$task->id}: {$task->title}");
            $content .= "- [{$project->key}-{$task->id}: {$task->title}](/projects/{$project->id}/wiki/{$taskPageSlug})\n";
        }

        // Wiki sayfasını oluştur veya güncelle
        $wikiPage = WikiPage::updateOrCreate(
            ['project_id' => $project->id, 'slug' => $slug],
            [
                'title' => $title,
                'content' => $content,
                'source_references' => ['task_type' => $taskType, 'task_ids' => $tasks->pluck('id')->toArray()],
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]
        );

        // Kategori ilişkisini kur
        if (!$wikiPage->categories->contains($category->id)) {
            $wikiPage->categories()->attach($category->id);
        }
    }

    /**
     * Teknik dokümantasyon sayfası oluştur
     */
    private function generateTechnicalDocumentation(Project $project, Collection $tasks, bool $force = false): void
    {
        // Teknik kategori oluştur
        $category = WikiCategory::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'technical-documentation'],
            ['name' => 'Teknik Dokümantasyon']
        );

        // Teknik task'ları filtrele (bug, task, technical-debt gibi)
        $technicalTasks = $tasks->filter(function ($task) {
            return in_array($task->task_type, ['bug', 'task', 'technical-debt', 'improvement']);
        });

        $title = 'Teknik Dokümantasyon';
        $slug = WikiPage::createSlug($title);

        $content = "# {$project->name} - Teknik Dokümantasyon\n\n";
        $content .= "Bu dokümantasyon, {$project->name} projesinin teknik detaylarını içermektedir. Otomatik olarak oluşturulmuştur.\n\n";

        // Teknik task'ları tiplere göre grupla
        $tasksByType = $technicalTasks->groupBy('task_type');

        foreach ($tasksByType as $type => $typeTasks) {
            $typeName = ucfirst($type);
            $content .= "## {$typeName}\n\n";

            foreach ($typeTasks as $task) {
                $taskPageSlug = WikiPage::createSlug("{$project->key}-{$task->id}: {$task->title}");
                $content .= "### [{$project->key}-{$task->id}: {$task->title}](/projects/{$project->id}/wiki/{$taskPageSlug})\n\n";

                if ($task->description) {
                    // Açıklamadan ilk 100 karakteri al
                    $shortDesc = Str::limit(strip_tags($task->description), 100);
                    $content .= "{$shortDesc}\n\n";
                }
            }
        }

        // Wiki sayfasını oluştur veya güncelle
        $wikiPage = WikiPage::updateOrCreate(
            ['project_id' => $project->id, 'slug' => $slug],
            [
                'title' => $title,
                'content' => $content,
                'source_references' => ['task_ids' => $technicalTasks->pluck('id')->toArray()],
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]
        );

        // Kategori ilişkisini kur
        if (!$wikiPage->categories->contains($category->id)) {
            $wikiPage->categories()->attach($category->id);
        }
    }

    /**
     * Kullanıcı dokümantasyonu oluştur
     */
    private function generateUserDocumentation(Project $project, Collection $tasks, bool $force = false): void
    {
        // Kullanıcı kategori oluştur
        $category = WikiCategory::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'user-documentation'],
            ['name' => 'Kullanıcı Dokümantasyonu']
        );

        // Kullanıcı ile ilgili task'ları filtrele (story, feature gibi)
        $userTasks = $tasks->filter(function ($task) {
            return in_array($task->task_type, ['story', 'feature', 'enhancement']);
        });

        $title = 'Kullanıcı Dokümantasyonu';
        $slug = WikiPage::createSlug($title);

        $content = "# {$project->name} - Kullanıcı Dokümantasyonu\n\n";
        $content .= "Bu dokümantasyon, {$project->name} projesinin kullanıcı kılavuzunu içermektedir. Otomatik olarak oluşturulmuştur.\n\n";

        // Kullanıcı task'larını tiplere göre grupla
        $tasksByType = $userTasks->groupBy('task_type');

        foreach ($tasksByType as $type => $typeTasks) {
            $typeName = ucfirst($type);
            $content .= "## {$typeName}\n\n";

            foreach ($typeTasks as $task) {
                $taskPageSlug = WikiPage::createSlug("{$project->key}-{$task->id}: {$task->title}");
                $content .= "### [{$project->key}-{$task->id}: {$task->title}](/projects/{$project->id}/wiki/{$taskPageSlug})\n\n";

                if ($task->description) {
                    // Açıklamadan ilk 150 karakteri al
                    $shortDesc = Str::limit(strip_tags($task->description), 150);
                    $content .= "{$shortDesc}\n\n";
                }
            }
        }

        // Wiki sayfasını oluştur veya güncelle
        $wikiPage = WikiPage::updateOrCreate(
            ['project_id' => $project->id, 'slug' => $slug],
            [
                'title' => $title,
                'content' => $content,
                'source_references' => ['task_ids' => $userTasks->pluck('id')->toArray()],
                'is_auto_generated' => true,
                'last_updated_at' => now(),
            ]
        );

        // Kategori ilişkisini kur
        if (!$wikiPage->categories->contains($category->id)) {
            $wikiPage->categories()->attach($category->id);
        }
    }
}
