<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AllowFluid
{

    public array $fluid_ips = [
        '185.212.88.178',
        '62.117.119.176',
        '62.117.119.177',
        '77.108.67.7',
        '77.108.67.8',
        '77.108.67.9',
        '185.180.124.14',
        '77.50.146.14',
        '127.0.0.1',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ( !in_array($request->ip(), $this->fluid_ips) ) {
            return response('В доступе отказано!', 500);
        }

        return $next($request);
    }
}
