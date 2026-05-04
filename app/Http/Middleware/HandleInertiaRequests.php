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
        $sharedLeagues = [];
        $sidebarPath = storage_path('app/public/leagues_sidebar.json');
        
        if (file_exists($sidebarPath)) {
            $sharedLeagues = json_decode(file_get_contents($sidebarPath), true);
        }

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
