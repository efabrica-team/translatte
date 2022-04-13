<?php

declare(strict_types=1);

namespace Efabrica\Translatte\CurrentTranslate;

interface ICurrentTranslate
{
    public function init(): void;

    public function store(array $data): void;

    public function get(): array;
}
