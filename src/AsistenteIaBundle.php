<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AsistenteIaBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
