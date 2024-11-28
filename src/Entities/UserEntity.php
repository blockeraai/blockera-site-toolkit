<?php

namespace BlockeraAI\SiteToolkit\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
    use EntityTrait;

    public function __construct(int $identifier)
    {
        $this->setIdentifier((string)$identifier);
    }

    /**
     * Find the user by their credentials.
     *
     * This is where you would check the credentials (username, password, etc.)
     * against the WordPress database. In this example, we'll use WordPress functions.
     *
     * @param string $username The username or email.
     * @param string $password The password.
     *
     * @return UserEntity|null
     */
    public static function findUserByCredentials($username, $password): ?UserEntity
    {
        // Try to find the user by their email or username
        $user = get_user_by('email', $username) ?: get_user_by('login', $username);

        if ($user && wp_check_password($password, $user->data->user_pass, $user->ID)) {
            // If the credentials are valid, return a new UserEntity
            return new self($user->ID);
        }

        // Invalid credentials, return null
        return null;
    }

    /**
     * Get the user by identifier (e.g., user ID).
     *
     * @param int $identifier
     *
     * @return UserEntity|null
     */
    public static function getUserByIdentifier($identifier): ?UserEntity
    {
        // Retrieve the user by ID from the WordPress database
        $user = get_user_by('id', $identifier);

        if ($user) {
            return new self($user->ID);
        }

        return null;
    }
}
