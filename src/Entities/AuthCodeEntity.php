<?php

namespace BlockeraAI\SiteToolkit\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AuthCodeEntity implements AuthCodeEntityInterface
{
    use EntityTrait;
    use AuthCodeTrait;
    use TokenEntityTrait;
}
