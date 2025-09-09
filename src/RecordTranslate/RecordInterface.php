<?php

namespace Efabrica\Translatte\Record;

interface RecordInterface
{
    public function save(string $message): void;
}
