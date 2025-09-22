<?php

namespace App\Http\Controllers;

use App\Models\ApiEndpoint;
use App\Models\ApiEndpointHistory;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiEndpointController extends Controller
{
    /**
     * API endpoint'leri listele
     */
    public function index(Request $request)
    {
        $query = ApiEndpoint::query()->with(['project', 'task']);

        // Proje veya task filtreleme
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        // Sadece kullanıcının kendi oluşturduğu endpoint'leri göster
        $query->where('user_id', auth()->id());

        return $query->latest()->paginate(10);
    }

    /**
     * Yeni API endpoint oluştur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:2000',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'description' => 'nullable|string',
            'headers' => 'nullable|array',
            'params' => 'nullable|array',
            'body' => 'nullable|array',
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // Task ve proje kontrolü
        if (!empty($validated['task_id'])) {
            $task = Task::findOrFail($validated['task_id']);

            // Task'ın projesini otomatik olarak ekle
            if (empty($validated['project_id'])) {
                $validated['project_id'] = $task->project_id;
            }

            // Kullanıcının bu task'a erişim yetkisi var mı?
            if ($task->project->user_id !== auth()->id()) {
                abort(403, 'Bu göreve erişim yetkiniz yok.');
            }
        } elseif (!empty($validated['project_id'])) {
            $project = Project::findOrFail($validated['project_id']);

            // Kullanıcının bu projeye erişim yetkisi var mı?
            if ($project->user_id !== auth()->id()) {
                abort(403, 'Bu projeye erişim yetkiniz yok.');
            }
        }

        // Kullanıcı ID'sini ekle
        $validated['user_id'] = auth()->id();

        $apiEndpoint = ApiEndpoint::create($validated);

        return response()->json($apiEndpoint, 201);
    }

    /**
     * API endpoint detaylarını göster
     */
    public function show(ApiEndpoint $apiEndpoint)
    {
        // Yetki kontrolü
        if ($apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'e erişim yetkiniz yok.');
        }

        return $apiEndpoint->load(['project', 'task', 'history' => function ($query) {
            $query->latest()->limit(5);
        }]);
    }

    /**
     * API endpoint'i güncelle
     */
    public function update(Request $request, ApiEndpoint $apiEndpoint)
    {
        // Yetki kontrolü
        if ($apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'i düzenleme yetkiniz yok.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|string|max:2000',
            'method' => 'sometimes|required|string|in:GET,POST,PUT,PATCH,DELETE',
            'description' => 'nullable|string',
            'headers' => 'nullable|array',
            'params' => 'nullable|array',
            'body' => 'nullable|array',
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // Task ve proje kontrolü
        if (!empty($validated['task_id'])) {
            $task = Task::findOrFail($validated['task_id']);

            // Kullanıcının bu task'a erişim yetkisi var mı?
            if ($task->project->user_id !== auth()->id()) {
                abort(403, 'Bu göreve erişim yetkiniz yok.');
            }
        }

        if (!empty($validated['project_id'])) {
            $project = Project::findOrFail($validated['project_id']);

            // Kullanıcının bu projeye erişim yetkisi var mı?
            if ($project->user_id !== auth()->id()) {
                abort(403, 'Bu projeye erişim yetkiniz yok.');
            }
        }

        $apiEndpoint->update($validated);

        return response()->json($apiEndpoint);
    }

    /**
     * API endpoint'i sil
     */
    public function destroy(ApiEndpoint $apiEndpoint)
    {
        // Yetki kontrolü
        if ($apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'i silme yetkiniz yok.');
        }

        $apiEndpoint->delete();

        return response()->json(null, 204);
    }

    /**
     * API endpoint'i çalıştır
     */
    public function execute(Request $request, ApiEndpoint $apiEndpoint)
    {
        // Yetki kontrolü
        if ($apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint\'i çalıştırma yetkiniz yok.');
        }

        // İstek parametrelerini doğrula
        $validated = $request->validate([
            'headers' => 'nullable|array',
            'params' => 'nullable|array',
            'body' => 'nullable|array',
        ]);

        // Endpoint'ten varsayılan değerleri al
        $headers = $validated['headers'] ?? $apiEndpoint->headers ?? [];
        $params = $validated['params'] ?? $apiEndpoint->params ?? [];
        $body = $validated['body'] ?? $apiEndpoint->body ?? [];

        // URL'yi oluştur
        $url = $apiEndpoint->url;

        // URL parametrelerini ekle
        if (!empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        // Başlangıç zamanını kaydet
        $startTime = microtime(true);

        try {
            // HTTP isteğini yap
            $response = Http::withHeaders($headers);

            // Metoda göre isteği yap
            switch ($apiEndpoint->method) {
                case 'GET':
                    $response = $response->get($url);
                    break;
                case 'POST':
                    $response = $response->post($url, $body);
                    break;
                case 'PUT':
                    $response = $response->put($url, $body);
                    break;
                case 'PATCH':
                    $response = $response->patch($url, $body);
                    break;
                case 'DELETE':
                    $response = $response->delete($url, $body);
                    break;
                default:
                    return response()->json(['error' => 'Geçersiz HTTP metodu'], 400);
            }

            // Bitiş zamanını hesapla
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000); // milisaniye cinsinden

            // Yanıt başlıklarını al
            $responseHeaders = $response->headers();

            // Yanıt gövdesini al
            $responseBody = $response->json() ?: $response->body();

            // Yanıt durum kodunu al
            $statusCode = $response->status();

            // Geçmiş kaydını oluştur
            $history = ApiEndpointHistory::create([
                'api_endpoint_id' => $apiEndpoint->id,
                'request_url' => $url,
                'request_method' => $apiEndpoint->method,
                'request_headers' => $headers,
                'request_body' => $body,
                'response_status_code' => $statusCode,
                'response_headers' => $responseHeaders,
                'response_body' => $responseBody,
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
            ]);

            // Yanıtı döndür
            return response()->json([
                'status_code' => $statusCode,
                'headers' => $responseHeaders,
                'body' => $responseBody,
                'execution_time_ms' => $executionTime,
                'history_id' => $history->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'API isteği başarısız oldu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint geçmişini listele
     */
    public function history(ApiEndpoint $apiEndpoint)
    {
        // Yetki kontrolü
        if ($apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu API endpoint geçmişine erişim yetkiniz yok.');
        }

        return $apiEndpoint->history()->latest()->paginate(10);
    }

    /**
     * Geçmiş kaydının detaylarını göster
     */
    public function historyDetail(ApiEndpointHistory $history)
    {
        // Yetki kontrolü
        if ($history->apiEndpoint->user_id !== auth()->id()) {
            abort(403, 'Bu geçmiş kaydına erişim yetkiniz yok.');
        }

        return $history;
    }
}
