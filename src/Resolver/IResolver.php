<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resolver;

interface IResolver
{
    public function resolve(): ?string;
}
