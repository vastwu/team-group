<?php

namespace App\Http\Middleware;

use Closure;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Error;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
      if (!$request->has('token')) {
        return response()->json([
          'error' => '400',
          'reason' => Error::reason['400']
        ]);
      }
      $user = DB::table('user')->where('token', $request->input('token'))->first(); 
      if (!$user) {
        return response()->json([
          'error' => '401',
          'reason' => Error::reason['401']
        ]);
      }
      $uid = $user->id;
      $request->attributes->add(['TOKEN_UID' => $uid]);
      return $next($request);
    }
}
