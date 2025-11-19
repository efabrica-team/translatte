<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Resource;

use Nette\Utils\Finder;
use SplFileInfo;

class NeonDirectoryResource implements IResource
{
    private array $directories;

    private array $ignoredPrefixes;

    public function __construct(array $directories, array $ignoredPrefixes = ['messages'])
    {
        $this->directories = $directories;
        $this->ignoredPrefixes = $ignoredPrefixes;
    }

    public function load(string $lang): array
    {
        $directories = [];
        /** @var SplFileInfo $file */
        foreach (Finder::find('*.*.neon')->from(...$this->directories) as $file) {
            $matchCount = preg_match(
                '~^(?P<prefix>.*?)\.(?P<lang>[^\.]+)\.(?P<format>[^\.]+)$~',
                $file->getFilename(),
                $matches
            );

            if ($matchCount !== 1) {
                continue;
            }

            $resource = new NeonResource($file->getPathname(), $matches['lang'], in_array($matches['prefix'], $this->ignoredPrefixes, true) ? '' : $matches['prefix'] . '.');
            $directories[] = $resource->load($lang);
        }

        return array_merge(...$directories);
    }
}
