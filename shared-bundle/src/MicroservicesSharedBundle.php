<?php

declare(strict_types=1);

namespace Microservices\SharedBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MicroservicesSharedBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
