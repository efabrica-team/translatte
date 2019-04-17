<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Cache;

class NullCache implements ICache
{
    public function store(string $lang, array $data): void
    {
        // Nothing to do
    }

    public function load(string $lang): ?array
    {
        return null;
    }
}
