<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Entities\ClientEntity;
use BlockeraAI\SiteToolkit\Entities\AccessTokenEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class AccessTokenRepository implements \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface
{
    use AccessTokenTrait;

    // Optionally, you can add custom methods or properties if needed

    /**
     * @var \wpdb
     */
    private $wpdb;

    private string $tableName = 'auth_access_tokens';

    /**
     * AccessTokenRepository constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $table = $this->wpdb->prefix . $this->tableName;

        $this->wpdb->insert($table, [
            'access_token' => $accessTokenEntity->getIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => implode(' ', $accessTokenEntity->getScopes()),
            'expires_at' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $table = $this->wpdb->prefix . $this->tableName;

        $this->wpdb->delete($table, ['access_token' => $tokenId]);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $table = $this->wpdb->prefix . $this->tableName;

        $token = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT access_token FROM $table WHERE access_token = %s", $tokenId)
        );

        return empty($token);
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $accessToken = new AccessTokenEntity();

        // Set the client entity.
        $accessToken->setClient($clientEntity);

        // Set the user identifier, if available.
        $accessToken->setUserIdentifier($userIdentifier);

        // Add the provided scopes to the token.
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    public function getClient(): ClientEntityInterface
    {
        $client = new ClientEntity();

        $client->setIdentifier(get_current_user_id());

        return $client;
    }

    public function getExpiryDateTime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify('+1 year');
    }

    public function getUserIdentifier(): string|null
    {
        return $this->getClient()->getIdentifier();
    }

    public function getScopes(): array
    {
        $entity = new AccessTokenEntity();

        return $entity->getScopes();
    }

    public function getIdentifier(): string
    {
        $entity = new AccessTokenEntity();

        return $entity->getIdentifier();
    }

    public function getTokenBy(string $field, $value): ?string
    {
        $table = $this->wpdb->prefix . $this->tableName;

        return $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT access_token FROM $table WHERE $field = %s", $value)
        );
    }
}
