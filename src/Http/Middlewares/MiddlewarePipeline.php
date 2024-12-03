<?php

namespace BlockeraAI\SiteToolkit\Http\Middlewares;

use BlockeraAI\SiteToolkit\Http\Middlewares\Contracts\Middleware;

class MiddlewarePipeline
{
    private $middlewares = [];

    public function pipe(Middleware $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function process($request, callable $handler = null)
    {
        $handler = $handler ?? function () {
            return true;
        };

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, Middleware $middleware) {
                return function ($request) use ($next, $middleware) {
                    return $middleware->handle($request, $next);
                };
            },
            $handler
        );

        return $pipeline($request);
    }
}
