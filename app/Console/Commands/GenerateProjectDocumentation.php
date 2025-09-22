<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\WikiGeneratorService;
use Illuminate\Console\Command;

class GenerateProjectDocumentation extends Command
{
    /**
     * Komut adı ve açıklaması
     *
     * @var string
     */
    protected $signature = 'wiki:generate {project? : Proje ID veya tüm projeler için "all"} {--force : Tüm dokümantasyonu yeniden oluştur}';

    /**
     * Komut açıklaması
     *
     * @var string
     */
    protected $description = 'Task açıklamaları ve yorumlarından otomatik wiki dokümantasyonu oluşturur';

    /**
     * Wiki generator servisi
     */
    protected WikiGeneratorService $wikiGenerator;

    /**
     * Komut yapıcısı
     */
    public function __construct(WikiGeneratorService $wikiGenerator)
    {
        parent::__construct();
        $this->wikiGenerator = $wikiGenerator;
    }

    /**
     * Komutu çalıştır
     */
    public function handle()
    {
        $projectId = $this->argument('project');
        $force = $this->option('force');

        if ($projectId === 'all') {
            $this->generateForAllProjects($force);
        } elseif ($projectId) {
            $this->generateForProject($projectId, $force);
        } else {
            // Proje seçimi için interaktif menü
            $projects = Project::orderBy('name')->get(['id', 'name', 'key']);

            if ($projects->isEmpty()) {
                $this->error('Hiç proje bulunamadı!');

                return 1;
            }

            $choices = $projects->mapWithKeys(function ($project) {
                return [$project->id => "{$project->key} - {$project->name}"];
            })->toArray();

            $choices['all'] = 'Tüm projeler';

            $selectedProject = $this->choice(
                'Hangi proje için dokümantasyon oluşturulacak?',
                $choices,
                'all'
            );

            if ($selectedProject === 'Tüm projeler') {
                $this->generateForAllProjects($force);
            } else {
                $selectedProjectId = array_search($selectedProject, $choices);
                $this->generateForProject($selectedProjectId, $force);
            }
        }

        return 0;
    }

    /**
     * Tüm projeler için dokümantasyon oluştur
     */
    protected function generateForAllProjects(bool $force): void
    {
        $projects = Project::all();
        $count = $projects->count();

        $this->info("Toplam {$count} proje için dokümantasyon oluşturuluyor...");
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach ($projects as $project) {
            $this->generateDocumentation($project, $force);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Tüm projeler için dokümantasyon oluşturuldu!');
    }

    /**
     * Belirli bir proje için dokümantasyon oluştur
     */
    protected function generateForProject(int $projectId, bool $force): void
    {
        $project = Project::find($projectId);

        if (!$project) {
            $this->error("ID: {$projectId} olan proje bulunamadı!");

            return;
        }

        $this->info("'{$project->name}' projesi için dokümantasyon oluşturuluyor...");
        $this->generateDocumentation($project, $force);
        $this->info('Dokümantasyon başarıyla oluşturuldu!');
    }

    /**
     * Dokümantasyon oluştur
     */
    protected function generateDocumentation(Project $project, bool $force): void
    {
        try {
            $this->wikiGenerator->generateWikiFromTasks($project, $force);
        } catch (\Exception $e) {
            $this->error("'{$project->name}' projesi için dokümantasyon oluşturulurken hata: {$e->getMessage()}");
        }
    }
}
