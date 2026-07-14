<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Record;

interface RecordInterface
{
    /**
     * @param array{file?: string, line?: int|null, trace?: array<int, string>}|null $destination direct caller and full call chain the translation was requested from
     */
    public function save(string $message, ?array $destination = null): void;
}
