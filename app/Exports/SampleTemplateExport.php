<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SampleTemplateExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly array $headings,
        private readonly array $sampleRows
    ) {}

    public function collection(): Collection
    {
        return collect($this->sampleRows);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
