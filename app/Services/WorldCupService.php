<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WorldCupService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('WC2026_API_KEY');
        $this->baseUrl = 'https://api.wc2026api.com'; 
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    public function getTeams()
    {
        return Cache::remember('wc2026_teams_final', 3600, function () {
            $response = Http::withHeaders($this->getHeaders())->withoutVerifying()->get("{$this->baseUrl}/teams")->json();
            if (isset($response['error'])) return [];
            return is_array($response) ? $response : ($response['data'] ?? []);
        });
    }

    public function getGroups()
    {
        return Cache::remember('wc2026_groups_final', 3600, function () {
            $response = Http::withHeaders($this->getHeaders())->withoutVerifying()->get("{$this->baseUrl}/groups")->json();
            if (isset($response['error'])) return [];
            return is_array($response) ? $response : ($response['data'] ?? []);
        });
    }

    public function getMatches($params = [])
    {
        $cacheKey = 'wc2026_matches_final_' . md5(serialize($params));
        return Cache::remember($cacheKey, 300, function () use ($params) {
            $response = Http::withHeaders($this->getHeaders())->withoutVerifying()->get("{$this->baseUrl}/matches", $params)->json();
            if (isset($response['error'])) return [];
            return is_array($response) ? $response : ($response['data'] ?? []);
        });
    }

    public function getStadiums()
    {
        return Cache::remember('wc2026_stadiums_final', 86400, function () {
            $response = Http::withHeaders($this->getHeaders())->withoutVerifying()->get("{$this->baseUrl}/stadiums")->json();
            if (isset($response['error'])) return [];
            return is_array($response) ? $response : ($response['data'] ?? []);
        });
    }

    public function getLiveSandbox()
    {
        $response = Http::withHeaders($this->getHeaders())->withoutVerifying()->get("{$this->baseUrl}/test/match")->json();
        if (isset($response['error'])) return null;
        return $response;
    }
}


