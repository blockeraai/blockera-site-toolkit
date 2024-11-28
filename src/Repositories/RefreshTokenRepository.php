<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use BlockeraAI\SiteToolkit\Entities\RefreshTokenEntity;

class RefreshTokenRepository implements \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
{
    use RefreshTokenTrait;
    use EntityTrait;

    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $table = $this->wpdb->prefix . 'auth_refresh_tokens';
        $this->wpdb->insert($table, [
            'refresh_token' => $refreshTokenEntity->getIdentifier(),
            'access_token'  => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'expires_at'    => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $table = $this->wpdb->prefix . 'auth_refresh_tokens';
        $this->wpdb->delete($table, ['refresh_token' => $tokenId]);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $table = $this->wpdb->prefix . 'auth_refresh_tokens';
        $token = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT refresh_token FROM $table WHERE refresh_token = %s", $tokenId)
        );

        return empty($token);
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        // Create a new instance of the RefreshTokenEntity.
        return new RefreshTokenEntity();
    }
}
