<?php

namespace App\Imports;

use App\Models\Categories;
use App\Models\Products;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class ProductsImport implements OnEachRow, SkipsOnFailure, WithChunkReading, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private int $created = 0;

    private int $updated = 0;

    private int $duplicates = 0;

    private array $seenSkus = [];

    public function __construct(private readonly bool $updateExisting = false) {}

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $sku = trim((string) $data['sku']);
        $normalizedSku = Str::lower($sku);

        if (isset($this->seenSkus[$normalizedSku])) {
            $this->duplicates++;

            return;
        }

        $this->seenSkus[$normalizedSku] = true;
        $existing = Products::withTrashed()->where('sku', $sku)->first();

        if ($existing && ! $this->updateExisting) {
            $this->duplicates++;

            return;
        }

        DB::transaction(function () use ($data, $sku, $existing) {
            $categoryId = $this->categoryId($data);
            $payload = [
                'name' => trim((string) $data['name']),
                'slug' => $this->uniqueSlug((string) $data['name'], $existing?->id),
                'sku' => $sku,
                'description' => $data['description'] ?? null,
                'price' => (float) $data['price'],
                'tax' => (float) ($data['tax'] ?? 0),
                'category_id' => $categoryId,
                'is_active' => $this->booleanValue($data['status'] ?? $data['is_active'] ?? true),
            ];

            if ($existing) {
                $existing->restore();
                $existing->update($payload);
                $product = $existing;
                $this->updated++;
            } else {
                $product = Products::create($payload);
                $this->created++;
            }

            Stock::updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => max(0, (int) ($data['quantity'] ?? 0))]
            );
        });
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.sku' => ['required', 'string', 'max:100'],
            '*.price' => ['required', 'numeric', 'min:0'],
            '*.quantity' => ['nullable', 'integer', 'min:0'],
            '*.tax' => ['nullable', 'numeric', 'min:0'],
            '*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            '*.category' => ['nullable', 'string', 'max:255'],
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

    private function categoryId(array $data): ?int
    {
        if (! empty($data['category_id'])) {
            return (int) $data['category_id'];
        }

        if (! empty($data['category'])) {
            return Categories::where('name', trim((string) $data['category']))->value('id');
        }

        return null;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::random(8);
        $slug = $base;
        $suffix = 2;

        while (Products::withTrashed()
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
