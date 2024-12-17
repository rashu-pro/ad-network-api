<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class BindRepositories
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, string $guard = null)
    {
        // Determine the current guard
        $guard = $guard ?? Auth::getDefaultDriver();

        // Define bindings based on guard
        $bindings = [
            'admin' => [
                \App\Contracts\Repositories\DigitalAssetRepository::class => \App\Repositories\AdminDigitalAssetRepo::class,
            ],
            'publisher' => [
                \App\Contracts\Repositories\DigitalAssetRepository::class => \App\Repositories\UserDigitalAssetRepo::class,
            ],
            'advertiser' => [
                \App\Contracts\Repositories\DigitalAssetRepository::class => \App\Repositories\UserDigitalAssetRepo::class,
            ],
        ];

        // Apply the bindings for the current guard
        if (isset($bindings[$guard])) {
            foreach ($bindings[$guard] as $interface => $implementation) {
                App::bind($interface, $implementation);
            }
        }

        return $next($request);
    }
}
