<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Record;

interface RecordInterface
{
    public function save(string $message): void;
}
