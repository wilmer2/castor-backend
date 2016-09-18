<?php

namespace App\Http\Middleware;

use Closure;

class isAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
      $user = currentUser();
      $user->loadRole();

      if($user->role == 2) {
         return response('Forbidden', 403);
      }
      return $next($request);
    }
}
