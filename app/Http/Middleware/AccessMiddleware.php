<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 6/9/2019
 * Time: 12:14 PM
 */

namespace App\Http\Middleware;

use Closure;
use Exception;
use http\Env\Response;


class AccessMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->get('access_token');

        if ($token == null) {
            return \response()->json([
                "error" => "Please inert an access token or register for token"], 401);
        }
        if ($token != env("ACCESS_TOKEN")) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'Token is not valid please insert a valid access token.'
            ], 401);
        }

        if (env('ACCESS_TOKEN') == null) {
            return response()->json([
                'error' => "Please insert a valid token for your application start"],
                401);
        } else {
            return $next($request);
        }
    }
}