<?php

namespace BlockeraAI\SiteToolkit\Http\Middlewares;

use Blockera\Utils\Utils;
use BlockeraAI\SiteToolkit\Http\Middlewares\Contracts\Middleware;
use BlockeraAI\SiteToolkit\Repositories\ClientRepository;

class RefererMiddleware implements Middleware
{
    public function handle(array $request, callable $next): bool
    {
        if (empty(isset($request['HTTP_REFERER'])) || !is_user_logged_in()) {
            return false;
        }

        $fromLicenseManager = str_ends_with($request['HTTP_REFERER'], 'my-account/license-manager');
        $fromAuthorization = false === strpos($request['HTTP_REFERER'], urlencode('authorize/?'));

        if ($fromLicenseManager || $fromAuthorization) {
            $params = [];
            parse_str($request['QUERY_STRING'], $params);

            if (parse_url(home_url())['host'] !== parse_url($request['HTTP_REFERER'])['host']) {
                $domain = Utils::extractDomainName($request['HTTP_REFERER'], true);

                $client = new ClientRepository();
                $isRegisteredClient = $client->getClientBy(
                    'domain',
                    substr(
                        empty($domain) ? $request['HTTP_REFERER'] : $domain,
                        0,
                        -1
                    )
                );

                return !empty($params['state']) && !empty($params['response_type']) && !empty($params['approval_prompt']) && empty($isRegisteredClient);
            }

            return false;
        }

        if (is_callable($next)) {
            return $next($request);
        }

        return true;
    }
}
