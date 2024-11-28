<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Entities\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'auth_codes';

        $wpdb->insert(
            $table,
            [
                'identifier' => $authCodeEntity->getIdentifier(),
                'user_id' => $authCodeEntity->getUserIdentifier(),
                'client_id' => $authCodeEntity->getClient()->getIdentifier(),
                'scopes' => json_encode($authCodeEntity->getScopes()),
                'revoked' => false,
                'expires_at' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
                'redirect_uri' => $authCodeEntity->getRedirectUri()
            ],
            [
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            ]
        );
    }

    public function revokeAuthCode(string $codeId): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'auth_auth_codes';

        $wpdb->update($table, ['revoked' => true], ['identifier' => $codeId]);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'auth_auth_codes';

        $revoked = $wpdb->get_var($wpdb->prepare("SELECT revoked FROM $table WHERE identifier = %s", $codeId));

        return $revoked === '1';
    }
}
