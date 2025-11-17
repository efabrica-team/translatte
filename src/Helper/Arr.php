<?php

declare(strict_types=1);

namespace Efabrica\Translatte\Helper;

final class Arr
{
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + self::flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
}
