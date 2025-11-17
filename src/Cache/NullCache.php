<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Cache;

use Efabrica\Translatte\Dictionary;

final class NullCache implements ICache
{
    public function store(string $lang, array $data): void
    {
        // Nothing to do
    }

    public function load(string $lang): ?Dictionary
    {
        return null;
    }
}
