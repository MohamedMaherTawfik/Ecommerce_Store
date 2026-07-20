<?php

namespace App\Imports;

use App\Models\Categories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class CategoriesImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private int $created = 0;

    private int $updated = 0;

    private int $duplicates = 0;

    private array $seenNames = [];

    public function __construct(private readonly bool $updateExisting = false) {}

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $name = trim((string) $data['name']);
        $normalizedName = Str::lower($name);

        if (isset($this->seenNames[$normalizedName])) {
            $this->duplicates++;

            return;
        }

        $this->seenNames[$normalizedName] = true;
        $existing = Categories::withTrashed()->where('name', $name)->first();

        if ($existing && ! $this->updateExisting) {
            $this->duplicates++;

            return;
        }

        DB::transaction(function () use ($data, $name, $existing) {
            $payload = [
                'name' => $name,
                'slug' => $this->uniqueSlug($data['slug'] ?? $name, $existing?->id),
                'is_active' => $this->booleanValue($data['status'] ?? $data['is_active'] ?? true),
            ];

            if ($existing) {
                $existing->restore();
                $existing->update($payload);
                $this->updated++;
            } else {
                Categories::create($payload);
                $this->created++;
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.slug' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function report(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'duplicates' => $this->duplicates,
            'failed' => count($this->failures()),
            'failures' => collect($this->failures())->map(fn ($failure) => [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ])->values()->all(),
        ];
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Categories::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'active', 'enabled'], true);
    }
}
