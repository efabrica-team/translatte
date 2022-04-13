<?php

declare(strict_types=1);

namespace Efabrica\Translatte\CurrentTranslate;

class DumbCurrentTranslate implements ICurrentTranslate
{
    public function init(): void
    {
    }

    public function store(array $data): void
    {
    }

    public function get(): array
    {
        return [];
    }
}
