<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Entities\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class ClientRepository implements \League\OAuth2\Server\Repositories\ClientRepositoryInterface
{
    /**
     * The WordPress database object.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * ClientRepository constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    /**
     * Get the client by identifier.
     *
     * @param string $clientIdentifier The client identifier.
     *
     * @return \stdClass|null The client object or null if not found.
     */
    public function getClient(string $clientIdentifier): ?\stdClass
    {
        $table = $this->wpdb->prefix . 'auth_clients';
        $client = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $table WHERE client_id = %s", $clientIdentifier)
        );

        if (!$client) {
            return null;
        }

        return $client;
    }

    /**
     * Get the client by field name and value.
     *
     * @param string $field The field name.
     * @param mixed $value The field value.
     *
     * @return \stdClass|null The client object or null if not found.
     */
    public function getClientBy(string $field, $value): ?\stdClass
    {
        $table = $this->wpdb->prefix . 'auth_clients';
        $client = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $table WHERE $field = %s", $value)
        );

        if (!$client) {
            return null;
        }

        return $client;
    }

    /**
     * Get the client entity by identifier.
     *
     * @param string $clientIdentifier The client identifier.
     *
     * @return ClientEntityInterface|null The client entity or null if not found.
     */
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->getClient($clientIdentifier);

        if (!$client) {
            return null;
        }

        $clientEntity = new ClientEntity();
        $clientEntity->setName($client->user_id);
        $clientEntity->setIdentifier($client->client_id);
        $clientEntity->setRedirectUri($client->redirect_uri);

        return $clientEntity;
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $table = $this->wpdb->prefix . 'auth_clients';
        $client = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $table WHERE client_id = %s", $clientIdentifier)
        );

        if (!$client || !hash_equals($client->client_secret, $clientSecret)) {
            return false;
        }

        // Optionally check if the client supports the requested grant type
        $allowedGrantTypes = explode(',', $client->grant_type);
        if (!in_array($grantType, $allowedGrantTypes)) {
            return false;
        }

        return true;
    }
}
