<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Entities\UserEntity;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class UserRepository implements \League\OAuth2\Server\Repositories\UserRepositoryInterface
{
    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        $user = get_user_by('login', $username);

        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            $userEntity = new UserEntity($user->ID);
            $userEntity->setIdentifier($user->ID);
            return $userEntity;
        }

        return null;
    }

    /**
     * Get the clients of the user.
     *
     * @param integer $user_id The ID of the user.
     * @return array The clients of the user.
     */
    public function getClients(int $user_id): array
    {
        global $wpdb;

        $clients_table = $wpdb->prefix . 'auth_clients';
        $licenses_table = $wpdb->prefix . 'auth_licenses';
        $clients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, l.subscription_id as subscription_id 
                FROM $clients_table c
                LEFT JOIN $licenses_table l ON l.client_id = c.client_id
                WHERE c.user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return $clients;
    }
}
