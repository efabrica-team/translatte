<?php

declare(strict_types=1);

namespace Efabrica\Translatte;

use Efabrica\Translatte\Helper\Arr;

class Dictionary
{
    private $lang;

    private $records;

    public function __construct(string $lang, array $records = [])
    {
        $this->lang = $lang;
        $this->records = Arr::flatten($records);
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function extend(Dictionary $dictionary): void
    {
        if ($this->lang !== $dictionary->getLang()) {
            throw new \Exception(); // @TODO
        }

        $this->records = array_merge($this->records, $dictionary->getRecords());
    }

    public function findTranslation(string $key): ?string
    {
        return array_key_exists($key, $this->records) ? $this->records[$key] : null;
    }
}
