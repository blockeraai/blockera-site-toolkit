<?php

namespace BlockeraAI\SiteToolkit\Entities;

use League\OAuth2\Server\Entities\Traits\ScopeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class ScopeEntity implements ScopeEntityInterface
{
    use ScopeTrait;
    use EntityTrait;
}
