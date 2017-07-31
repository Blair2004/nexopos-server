<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken {

    protected $except_urls = [
        'api/gcp/submit-print-job'
    ];

    public function handle($request, Closure $next)
    {
        $regex = '#' . implode('|', $this->except_urls) . '#';

        if ($this->isReading($request) || $this->tokensMatch($request) || preg_match($regex, $request->path()))
        {
            return $this->addCookieToResponse($request, $next($request));
        }

        throw new TokenMismatchException;
    }

}

// namespace App\Http\Middleware;

// use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

// class VerifyCsrfToken extends BaseVerifier
// {
//     /**
//      * The URIs that should be excluded from CSRF verification.
//      *
//      * @var array
//      */
//     protected $except = [
//         //
//     ];
// }
