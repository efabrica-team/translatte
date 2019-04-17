<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

use Efabrica\Translatte\Dictionary;
use Efabrica\Translatte\Helper\Arr;
use Nette\Neon\Neon;

class NeonResource implements IResource
{
    /** @var string */
    private $filepath;

    /** @var string */
    private $lang;

    /** @var string */
    private $prefix;

    public function __construct(string $filepath, string $lang, string $prefix = '')
    {
        $this->filepath = $filepath;
        $this->lang = $lang;
        $this->prefix = $prefix;
    }

    public function load(string $lang): array
    {
        if ($lang !== $this->lang) {
            return [];
        }

        $content = @file_get_contents($this->filepath);
        if ($content === false) {
            // @TODO: exception?
            return [];
        }

        $records = Neon::decode($content);
        return [new Dictionary($lang, Arr::flatten($records, $this->prefix))];
    }
}
