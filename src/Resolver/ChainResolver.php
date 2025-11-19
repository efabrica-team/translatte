<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resolver;

class ChainResolver implements IResolver
{
    /** @var array */
    private $resolvers;

    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function resolve(): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $lang = $resolver->resolve();
            if (!empty($lang)) {
                return $lang;
            }
        }

        return null;
    }
}
