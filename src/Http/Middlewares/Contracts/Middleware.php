<?php

namespace BlockeraAI\SiteToolkit\Http\Middlewares\Contracts;

interface Middleware
{
    /**
     * Handle the middleware.
     *
     * @param array $request The request data.
     * @param callable $next The next middleware in the chain.
     * 
     * @return bool True if the middleware is successful, false otherwise.
     */
    public function handle(array $request, callable $next): bool;
}
