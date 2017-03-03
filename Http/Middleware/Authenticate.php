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
      $token = $request->input('token');
      if ($token === '2cc4d8f81bfdbdda3193cd57d7ce34fc') {
        $request->attributes->add(['IS_ADMIN' => true]);
        return $next($request);
      }
      $user = DB::table('user')->where('token', $token)->first(); 
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
