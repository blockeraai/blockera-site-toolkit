<?php

namespace BlockeraAI\SiteToolkit\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;

class ClientEntity implements ClientEntityInterface
{
    /**
     * The client identifier.
     *
     * @var string
     */
    protected string $identifier;

    /**
     * The user name.
     *
     * @var integer
     */
    protected string $name;

    /**
     * The redirect URI for the client.
     *
     * @var string
     */
    protected string $redirectURI;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRedirectUri(): string|array
    {
        return $this->redirectURI;
    }

    public function setRedirectURI(string $redirectURI): ClientEntity
    {
        $this->redirectURI = $redirectURI;

        return $this;
    }

    public function isConfidential(): bool
    {
        return true;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }
}
