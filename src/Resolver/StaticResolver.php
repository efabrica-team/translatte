<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resolver;

class StaticResolver implements IResolver
{
    /** @var string */
    private $lang;

    public function __construct(string $lang)
    {
        $this->lang = $lang;
    }

    public function resolve(): ?string
    {
        return $this->lang;
    }
}
