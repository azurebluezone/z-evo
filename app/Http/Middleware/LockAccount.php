<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Session;

/**
 * LockAccount
 * ----------
 * While an admin has locked their screen, every admin page answers with the
 * unlock form instead of the page that was asked for.
 *
 * It used to redirect to route('locked'). Now that locking is a POST behind
 * auth, that redirect would be a GET to a POST-only route - a 405, or an
 * endless bounce through the middleware that sent it there. Returning the view
 * directly has no such dependency: the middleware answers with the form
 * whatever the route table looks like.
 */
class LockAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if (Session::get('locked') === true) {
            return response()->view('backEnd.auth.locked');
        }

        return $next($request);
    }
}