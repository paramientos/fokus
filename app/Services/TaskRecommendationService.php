<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class TaskRecommendationService
{
    /**
     * Bir kullanıcı için en uygun görevleri öner
     *
     * @param User $user
     * @param Project|null $project
     * @param int $limit
     * @return Collection
     */
    public function recommendTasksForUser(User $user, ?Project $project = null, int $limit = 5): Collection
    {
        // Kullanıcının tamamladığı görevleri analiz et
        $completedTasks = $this->getUserCompletedTasks($user, $project);
        
        // Kullanıcının beceri setini belirle
        $userSkills = $this->determineUserSkills($completedTasks);
        
        // Kullanıcının performans verilerini topla
        $performanceMetrics = $this->collectUserPerformanceMetrics($user, $project);
        
        // Uygun görevleri bul
        $query = Task::query()
            ->with(['project', 'status', 'sprint'])
            ->whereNull('user_id'); // Henüz atanmamış görevler
            
        if ($project) {
            $query->where('project_id', $project->id);
        }
        
        // Kullanıcının becerilerine göre görevleri filtrele
        $query->when(!empty($userSkills), function ($q) use ($userSkills) {
            return $q->where(function ($subQuery) use ($userSkills) {
                foreach ($userSkills as $skill => $weight) {
                    // Görev başlığı veya açıklamasında beceri anahtar kelimelerini ara
                    $subQuery->orWhere('title', 'like', "%{$skill}%")
                             ->orWhere('description', 'like', "%{$skill}%");
                }
            });
        });
        
        // Kullanıcının performans metriklerine göre sırala
        // Örneğin, kullanıcı belirli görev türlerinde daha hızlıysa, bu tür görevleri önceliklendir
        if (isset($performanceMetrics['task_type_efficiency'])) {
            $order = array_keys($performanceMetrics['task_type_efficiency']);
            if (count($order)) {
                $orderBy = 'CASE';
                foreach ($order as $index => $type) {
                    $orderBy .= " WHEN task_type = '" . addslashes($type) . "' THEN $index";
                }
                $orderBy .= ' ELSE ' . count($order) . ' END';
                $query->orderByRaw($orderBy . ' DESC');
            }
        }
        
        // Önceliğe göre sırala
        $query->orderBy('priority', 'desc');
        
        return $query->limit($limit)->get();
    }
    
    /**
     * Bir görev için en uygun kullanıcıyı öner
     *
     * @param Task $task
     * @param int $limit
     * @return Collection
     */
    public function recommendUsersForTask(Task $task, int $limit = 3): Collection
    {
        // Görev türüne benzer görevleri tamamlamış kullanıcıları bul
        $taskType = $task->task_type;
        
        $users = User::query()
            ->whereHas('assignedTasks', function ($query) use ($taskType) {
                $query->where('task_type', $taskType)
                      ->whereHas('status', function ($q) {
                          $q->where('is_completed', true);
                      });
            })
            ->withCount(['assignedTasks' => function ($query) use ($taskType) {
                $query->where('task_type', $taskType)
                      ->whereHas('status', function ($q) {
                          $q->where('is_completed', true);
                      });
            }])
            ->orderBy('assigned_tasks_count', 'desc')
            ->limit($limit)
            ->get();
            
        // Eğer yeterli sonuç bulunamazsa, daha genel bir arama yap
        if ($users->count() < $limit) {
            $additionalUsers = User::query()
                ->whereNotIn('id', $users->pluck('id'))
                ->withCount('assignedTasks')
                ->orderBy('assigned_tasks_count', 'desc')
                ->limit($limit - $users->count())
                ->get();
                
            $users = $users->merge($additionalUsers);
        }
        
        return $users;
    }
    
    /**
     * Kullanıcının tamamladığı görevleri getir
     *
     * @param User $user
     * @param Project|null $project
     * @return Collection
     */
    private function getUserCompletedTasks(User $user, ?Project $project = null): Collection
    {
        $query = $user->assignedTasks()
            ->whereHas('status', function ($q) {
                $q->where('is_completed', true);
            });
            
        if ($project) {
            $query->where('project_id', $project->id);
        }
        
        return $query->get();
    }
    
    /**
     * Kullanıcının tamamladığı görevlere göre beceri setini belirle
     *
     * @param Collection $completedTasks
     * @return array
     */
    private function determineUserSkills(Collection $completedTasks): array
    {
        $skills = [];
        
        foreach ($completedTasks as $task) {
            // Görev başlığı ve açıklamasından anahtar kelimeleri çıkar
            $keywords = $this->extractKeywords($task->title . ' ' . $task->description);
            
            foreach ($keywords as $keyword) {
                if (!isset($skills[$keyword])) {
                    $skills[$keyword] = 0;
                }
                
                $skills[$keyword]++;
            }
        }
        
        // Becerileri ağırlıklarına göre sırala
        arsort($skills);
        
        // En önemli becerileri döndür (en çok 10 tane)
        return array_slice($skills, 0, 10, true);
    }
    
    /**
     * Metinden anahtar kelimeleri çıkar
     *
     * @param string $text
     * @return array
     */
    private function extractKeywords(string $text): array
    {
        // Basit bir anahtar kelime çıkarma algoritması
        // Gerçek uygulamada daha gelişmiş NLP teknikleri kullanılabilir
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        $words = explode(' ', $text);
        $words = array_filter($words, function ($word) {
            return strlen($word) > 3; // Kısa kelimeleri filtrele
        });
        
        // Stop kelimeleri filtrele (İngilizce için)
        $stopWords = ['the', 'and', 'that', 'have', 'for', 'not', 'with', 'this', 'but', 'from', 'they', 'will', 'would', 'there', 'their', 'what', 'about', 'which', 'when', 'make', 'like', 'time', 'just', 'know', 'take', 'into', 'year', 'your', 'good', 'some', 'could', 'them', 'than', 'then', 'look', 'only', 'come', 'over', 'think', 'also', 'back', 'after', 'work', 'first', 'well', 'even', 'want', 'because', 'these', 'give', 'most'];
        
        $words = array_diff($words, $stopWords);
        
        return array_values($words);
    }
    
    /**
     * Kullanıcının performans metriklerini topla
     *
     * @param User $user
     * @param Project|null $project
     * @return array
     */
    private function collectUserPerformanceMetrics(User $user, ?Project $project = null): array
    {
        $metrics = [
            'task_type_efficiency' => [],
            'average_completion_time' => 0,
            'completed_tasks_count' => 0,
        ];
        
        $completedTasks = $this->getUserCompletedTasks($user, $project);
        
        if ($completedTasks->isEmpty()) {
            return $metrics;
        }
        
        $metrics['completed_tasks_count'] = $completedTasks->count();
        
        // Görev türüne göre tamamlama süresini hesapla
        $taskTypeCompletionTimes = [];
        
        foreach ($completedTasks as $task) {
            $taskType = $task->task_type;
            
            // Görevin oluşturulması ile tamamlanması arasındaki süre
            $createdAt = $task->created_at;
            $completedAt = $task->updated_at; // Basitleştirmek için updated_at kullanıyoruz
            
            $completionTime = $completedAt->diffInHours($createdAt);
            
            if (!isset($taskTypeCompletionTimes[$taskType])) {
                $taskTypeCompletionTimes[$taskType] = [];
            }
            
            $taskTypeCompletionTimes[$taskType][] = $completionTime;
        }
        
        // Her görev türü için ortalama tamamlama süresini hesapla
        foreach ($taskTypeCompletionTimes as $taskType => $times) {
            $metrics['task_type_efficiency'][$taskType] = array_sum($times) / count($times);
        }
        
        // Görev türlerini verimliliğe göre sırala (en verimli olanlar önce)
        asort($metrics['task_type_efficiency']);
        
        // Genel ortalama tamamlama süresi
        $allTimes = array_merge(...array_values($taskTypeCompletionTimes));
        $metrics['average_completion_time'] = array_sum($allTimes) / count($allTimes);
        
        return $metrics;
    }
}
