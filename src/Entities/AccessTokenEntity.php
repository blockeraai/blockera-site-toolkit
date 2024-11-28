<?php

namespace BlockeraAI\SiteToolkit\Entities;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use DateTimeImmutable;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use EntityTrait;

    /**
     * @var ClientEntityInterface
     */
    private $client;

    /**
     * @var string|null
     */
    private $userIdentifier;

    /**
     * @var DateTimeImmutable
     */
    private $expiryDateTime;

    /**
     * @var ScopeEntityInterface[]
     */
    private $scopes = [];

    /**
     * Get the expiry date/time of the token.
     *
     * @return DateTimeImmutable
     */
    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiryDateTime;
    }

    /**
     * Set the expiry date/time of the token.
     *
     * @param DateTimeImmutable $expiryDateTime
     */
    public function setExpiryDateTime(DateTimeImmutable $expiryDateTime): void
    {
        $this->expiryDateTime = $expiryDateTime;
    }

    /**
     * Get the client associated with the access token.
     *
     * @return ClientEntityInterface
     */
    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    /**
     * Set the client associated with the access token.
     *
     * @param ClientEntityInterface $client
     */
    public function setClient(ClientEntityInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * Get the user identifier associated with the token.
     *
     * @return string|null
     */
    public function getUserIdentifier(): string|null
    {
        return $this->userIdentifier;
    }

    /**
     * Set the user identifier for the token.
     *
     * @param string|null $userIdentifier
     */
    public function setUserIdentifier(string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * Get the scopes assigned to the token.
     *
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Add a scope to the token.
     *
     * @param ScopeEntityInterface $scope
     */
    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopes[] = $scope;
    }
}
