<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class SpreadsheetCellSanitizer
{
    public static function forExport(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', ltrim($value)) === 1 ? "'".$value : $value;
    }

    public static function rejectFormula(array $row, array $fields): void
    {
        foreach ($fields as $field) {
            $value = $row[$field] ?? null;
            if (is_string($value) && preg_match('/^[=+\-@]/', ltrim($value)) === 1) {
                throw ValidationException::withMessages([
                    $field => ["Spreadsheet formulas are not allowed in {$field}."],
                ]);
            }
        }
    }
}
