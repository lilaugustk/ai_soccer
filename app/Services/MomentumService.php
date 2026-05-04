<?php

namespace App\Services;

class MomentumService
{
    /**
     * Calculate momentum shifts over the match duration based on events.
     * Returns an array of values from -100 to 100 for each minute.
     */
    public function calculateMomentum(array $events, int $elapsed): array
    {
        $momentum = array_fill(0, max($elapsed, 90), 0);
        
        $weights = [
            'Goal' => 50,
            'Shot on Goal' => 20,
            'Shot off Goal' => 10,
            'Corner' => 8,
            'Yellow Card' => -5,
            'Red Card' => -30,
            'Penalty' => 40,
        ];

        foreach ($events as $event) {
            $minute = $event['time']['elapsed'] ?? 0;
            if ($minute >= count($momentum)) continue;
            
            $type = $event['type'];
            $detail = $event['detail'] ?? '';
            $weight = $weights[$type] ?? ($weights[$detail] ?? 0);
            
            $side = ($event['team']['id'] ?? null); // We'll need to know which team is home/away
            
            // This is a simplified version. In a real engine, we'd also use possession and dangerous attacks.
            // For now, let's assume the caller will handle side mapping.
        }

        return $momentum;
    }

    /**
     * A more practical version that takes home/away team IDs
     */
    public function getMatchMomentum(array $events, int $homeTeamId, int $awayTeamId, int $elapsed): array
    {
        $momentum = array_fill(0, max($elapsed + 1, 95), 0);
        
        $weights = [
            'Goal' => 40,
            'Shot on Goal' => 15,
            'Shot off Goal' => 5,
            'Corner' => 7,
            'Yellow Card' => -10,
            'Red Card' => -40,
        ];

        foreach ($events as $event) {
            $minute = $event['time']['elapsed'] ?? 0;
            if ($minute >= count($momentum)) continue;
            
            $type = $event['type'];
            $weight = $weights[$type] ?? 0;
            
            $teamId = $event['team']['id'] ?? 0;
            $multiplier = ($teamId == $homeTeamId) ? 1 : (($teamId == $awayTeamId) ? -1 : 0);
            
            // Spread the momentum effect over the next 5 minutes
            for ($i = 0; $i < 5; $i++) {
                if ($minute + $i < count($momentum)) {
                    $decay = (5 - $i) / 5;
                    $momentum[$minute + $i] += $weight * $multiplier * $decay;
                }
            }
        }

        // Smooth and Bound
        foreach ($momentum as &$val) {
            $val = max(-100, min(100, $val));
        }

        return $momentum;
    }
}
