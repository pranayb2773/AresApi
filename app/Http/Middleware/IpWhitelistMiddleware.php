<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \ErrorException
     */
    public function handle(Request $request, Closure $next)
    {
        $ipWhiteList = config('access.ipWhitelist');
        $arrIpAddress = explode(';', $ipWhiteList);
        if(! in_array('all', $arrIpAddress))
        {
            if(! in_array($request->ip(), $arrIpAddress))
            {
                $message = 'IP address is not whitelisted';
                throw new \ErrorException($message);
            }
        }
        return $next($request);
    }
}
