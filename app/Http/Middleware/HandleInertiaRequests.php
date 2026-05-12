<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedLeagues = \App\Models\FootballLeague::query()
            ->where('is_active', true)
            ->orderBy('country')
            ->orderBy('name')
            ->get()
            ->groupBy('country')
            ->map(function ($leagues, $countryName) {
                return [
                    'country_name' => $countryName,
                    'country_code' => $leagues->first()->country_code,
                    'leagues' => $leagues->map(function ($l) {
                        return [
                            'id' => $l->id,
                            'name' => $l->name,
                            'logo_url' => $l->logo_url,
                        ];
                    })->values(),
                ];
            })->values()->toArray();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'sharedLeagues' => $sharedLeagues,
            'globalFavoriteTeams' => $request->user() ? $request->user()->favoriteTeams()->get()->toArray() : [],
        ];
    }
}
