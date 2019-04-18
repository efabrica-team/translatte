<?php

declare(strict_types=1);

namespace Efabrica\Tests;

use PHPUnit\Framework\TestCase;
use Efabrica\Translatte\Resolver\StaticResolver;
use Efabrica\Translatte\Resolver\ChainResolver;

class ResolverTest extends TestCase
{
    public function testStaticResolver(): void
    {
        $resolver = new StaticResolver('sk_SK');
        $this->assertSame('sk_SK', $resolver->resolve());
    }

    public function testChainResolver(): void
    {
        $resolver1 = new ChainResolver([
            new StaticResolver('sk_SK')
        ]);
        $this->assertSame('sk_SK', $resolver1->resolve());

        $resolver2 = new ChainResolver([
            new StaticResolver(''),
            new StaticResolver('en_US')
        ]);
        $this->assertSame('en_US', $resolver2->resolve());

        $resolver3 = new ChainResolver([
            new StaticResolver(''),
            new StaticResolver('')
        ]);
        $this->assertNull($resolver3->resolve());
    }
}
