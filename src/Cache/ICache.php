<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Cache;

use Efabrica\Translatte\Dictionary;

interface ICache
{
    public function store(string $lang, array $data): void;

    public function load(string $lang): ?Dictionary;
}
