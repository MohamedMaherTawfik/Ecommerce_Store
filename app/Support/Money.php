<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function toMinorUnits(int|float|string|null $value): int
    {
        $value = trim((string) ($value ?? '0'));

        if (preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $value, $matches) !== 1) {
            throw new InvalidArgumentException('Invalid monetary value.');
        }

        $fraction = str_pad($matches[3] ?? '', 3, '0');
        $minor = ((int) $matches[2] * 100) + (int) substr($fraction, 0, 2);

        if ((int) $fraction[2] >= 5) {
            $minor++;
        }

        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }

    public static function fromMinorUnits(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $absolute = abs($minor);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
