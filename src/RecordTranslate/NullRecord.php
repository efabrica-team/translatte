<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Record;

class NullRecord implements RecordInterface
{
    /**
     * @param array{file?: string, line?: int|null, trace?: array<int, string>}|null $destination
     */
    public function save(string $message, ?array $destination = null): void
    {
        // Nothing to do
    }
}
