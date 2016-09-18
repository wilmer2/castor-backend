<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
      if(!auth()->check()) {
         return response('Unauthorized.', 401);
      }

      return $next($request);
    }
   
}
