<?php

namespace App\Services;

class StatisticalModelService
{
    /**
     * Calculate Poisson probability
     * P(x; λ) = (e^-λ * λ^x) / x!
     */
    public function poisson(int $x, float $lambda): float
    {
        return (exp(-$lambda) * pow($lambda, $x)) / $this->factorial($x);
    }

    private function factorial(int $n): int
    {
        if ($n <= 1) return 1;
        $res = 1;
        for ($i = 2; $i <= $n; $i++) {
            $res *= $i;
        }
        return $res;
    }

    /**
     * Calculate win/draw/loss probabilities for two teams based on their expected goals (lambda)
     */
    public function calculateProbabilities(float $homeLambda, float $awayLambda, int $maxGoals = 8): array
    {
        $homeWin = 0;
        $draw = 0;
        $awayWin = 0;

        for ($h = 0; $h <= $maxGoals; $h++) {
            for ($a = 0; $a <= $maxGoals; $a++) {
                $prob = $this->poisson($h, $homeLambda) * $this->poisson($a, $awayLambda);

                if ($h > $a) {
                    $homeWin += $prob;
                } elseif ($h < $a) {
                    $awayWin += $prob;
                } else {
                    $draw += $prob;
                }
            }
        }

        // Normalize (ensure sum is 1)
        $total = $homeWin + $draw + $awayWin;
        
        return [
            'home' => round(($homeWin / $total) * 100, 1) . '%',
            'draw' => round(($draw / $total) * 100, 1) . '%',
            'away' => round(($awayWin / $total) * 100, 1) . '%',
            'expected_score' => round($homeLambda, 1) . ' - ' . round($awayLambda, 1)
        ];
    }

    public function calculateValueBets(array $modelProbs, array $bookmakers): array
    {
        $valueBets = [];
        $homeProb = (float)str_replace('%', '', $modelProbs['home']) / 100;
        $drawProb = (float)str_replace('%', '', $modelProbs['draw']) / 100;
        $awayProb = (float)str_replace('%', '', $modelProbs['away']) / 100;

        foreach ($bookmakers as $bookmaker) {
            foreach ($bookmaker['bets'] as $bet) {
                if ($bet['name'] === 'Match Winner' || $bet['name'] === 'Home/Away') {
                    foreach ($bet['values'] as $outcome) {
                        $odds = (float)$outcome['odd'];
                        $modelProb = 0;

                        if ($outcome['value'] === 'Home') $modelProb = $homeProb;
                        elseif ($outcome['value'] === 'Draw') $modelProb = $drawProb;
                        elseif ($outcome['value'] === 'Away') $modelProb = $awayProb;

                        if ($modelProb > 0) {
                            $value = ($modelProb * $odds) - 1;
                            if ($value > 0.05) { // 5% edge
                                $valueBets[] = [
                                    'bookmaker' => $bookmaker['name'],
                                    'outcome' => $outcome['value'],
                                    'odds' => $odds,
                                    'model_prob' => round($modelProb * 100, 1) . '%',
                                    'value' => round($value * 100, 1) . '%'
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $valueBets;
    }
}
