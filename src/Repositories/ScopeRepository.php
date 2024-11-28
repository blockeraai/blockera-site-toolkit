<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use BlockeraAI\SiteToolkit\Entities\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class ScopeRepository implements \League\OAuth2\Server\Repositories\ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        $scopes = [
            'read' => [
                'description' => 'Grants read-only access to the API.',
            ],
            'write' => [
                'description' => 'Grants write access to modify data through the API.',
            ]
        ];

        if (empty($scopes[$identifier])) {
            return null;
        }


        $scopeEntity = new ScopeEntity();
        $scopeEntity->setIdentifier($identifier);

        return $scopeEntity;
    }

    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        string|null $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        return $scopes;
    }
}
