<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
    }

    public function getTacticalInsights(array $matchData): ?string
    {
        if (!$this->apiKey) {
            return "AI Analysis is currently unavailable (API Key missing).";
        }

        try {
            $prompt = $this->buildPrompt($matchData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => 'llama-3.1-70b-versatile',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional football tactical analyst. Provide concise, high-level tactical insights based on the provided match data. Focus on team form, key statistics, and potential game-changing factors. Output in Vietnamese.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Groq API Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Groq API Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function buildPrompt(array $data): string
    {
        $homeTeam = $data['home_team']['name'] ?? 'Home';
        $awayTeam = $data['away_team']['name'] ?? 'Away';
        $league = $data['league']['name'] ?? 'League';
        
        $stats = json_encode($data['statistics'] ?? []);
        $h2h = json_encode($data['h2h'] ?? []);
        
        return "Hãy phân tích trận đấu giữa {$homeTeam} và {$awayTeam} tại giải {$league}. 
        Dưới đây là một số thống kê và dữ liệu đối đầu: 
        Thống kê: {$stats}
        Đối đầu gần đây: {$h2h}
        
        Yêu cầu:
        1. Phân tích phong độ hiện tại.
        2. Nhận định chiến thuật có thể xảy ra.
        3. Dự đoán kịch bản trận đấu.
        Hãy trình bày ngắn gọn bằng tiếng Việt, chia làm các mục rõ ràng.";
    }
}
