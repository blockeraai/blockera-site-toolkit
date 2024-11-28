<?php

namespace BlockeraAI\SiteToolkit\Repositories;

use BlockeraAI\SiteToolkit\Entities\UserEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;

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
}
