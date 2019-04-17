<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

use Nette\Utils\Finder;

class NeonDirectoryResource implements IResource
{
    /** @var array */
    private $directories;

    /** @var array */
    private $ignoredPrefixes;

    public function __construct(array $directories, array $ignoredPrefixes = ['messages'])
    {
        $this->directories = $directories;
        $this->ignoredPrefixes = $ignoredPrefixes;
    }

    public function load(string $lang): array
    {
        $directories = [];

        foreach (Finder::find("*.*.neon")->from($this->directories) as $file) {
            if (!preg_match('~^(?P<prefix>.*?)\.(?P<lang>[^\.]+)\.(?P<format>[^\.]+)$~', $file->getFilename(), $matches)) {
                continue;
            }

            $resource = new NeonResource($file->getPathname(), $matches['lang'], in_array($matches['prefix'], $this->ignoredPrefixes) ? '' : $matches['prefix'] . ".");
            $directories = array_merge($directories, $resource->load($lang));
        }

        return $directories;
    }
}
