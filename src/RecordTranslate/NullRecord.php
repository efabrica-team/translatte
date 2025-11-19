<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Record;

final class NullRecord implements RecordInterface
{
    public function save(string $message): void
    {
        // Nothing to do
    }
}
