<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Record;

class NullRecord implements RecordInterface
{
    public function save(string $message, ?array $destination = null): void
    {
        // Nothing to do
    }
}
