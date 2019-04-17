<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

interface IResource
{
    /**
     * Returns array of Dictionary
     * @param string $lang
     * @return array
     */
    public function load(string $lang): array;
}
