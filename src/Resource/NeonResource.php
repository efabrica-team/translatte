<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

use Efabrica\Translatte\Dictionary;
use Efabrica\Translatte\Helper\Arr;
use Nette\Neon\Neon;
use RuntimeException;

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
            throw new RuntimeException(sprintf('Unable to read translation file "%s".', $this->filepath));
        }

        // an empty file is a file with no translations, which is allowed; anything that parses to
        // a non-map is malformed, and staying silent about it caches the emptiness it produces
        $records = Neon::decode($content) ?? [];
        if (!is_array($records)) {
            throw new RuntimeException(sprintf('Translation file "%s" does not contain a map.', $this->filepath));
        }

        return [new Dictionary($lang, Arr::flatten($records, $this->prefix))];
    }
}
