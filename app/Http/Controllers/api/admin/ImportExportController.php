<?php

namespace App\Http\Controllers\api\admin;

use App\Exports\CategoriesExport;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Exports\SampleTemplateExport;
use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Imports\CategoriesImport;
use App\Imports\ProductsImport;
use App\Support\TaggedCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ImportExportController extends Controller
{
    use ApiResponse;

    public function exportProducts(Request $request): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'products.'.$this->extension($request), $this->writer($request));
    }

    public function exportCategories(Request $request): BinaryFileResponse
    {
        return Excel::download(new CategoriesExport, 'categories.'.$this->extension($request), $this->writer($request));
    }

    public function exportOrders(Request $request): BinaryFileResponse
    {
        return Excel::download(new OrdersExport, 'orders.'.$this->extension($request), $this->writer($request));
    }

    public function sample(Request $request, string $type): BinaryFileResponse
    {
        abort_unless(in_array($type, ['products', 'categories'], true), 404);

        $export = $type === 'products'
            ? new SampleTemplateExport(
                ['name', 'sku', 'price', 'quantity', 'category_id', 'category', 'description', 'tax', 'status'],
                [['Sample Product', 'SKU-001', 99.99, 10, null, 'Sample Category', 'Product description', 0, 'active']]
            )
            : new SampleTemplateExport(
                ['name', 'slug', 'status'],
                [['Sample Category', 'sample-category', 'active']]
            );

        return Excel::download($export, "{$type}-import-template.".$this->extension($request), $this->writer($request));
    }

    public function importProducts(Request $request)
    {
        $data = $this->validateImport($request);

        try {
            $import = new ProductsImport((bool) ($data['update_existing'] ?? false));
            Excel::import($import, $data['file']);
            TaggedCache::tags(['products'])->flush();

            return $this->success($import->report(), 'Products import completed.');
        } catch (\Throwable $exception) {
            Log::error('Products import failed', ['exception' => $exception]);

            return $this->error('Products import failed. Verify the template and try again.', 422);
        }
    }

    public function importCategories(Request $request)
    {
        $data = $this->validateImport($request);

        try {
            $import = new CategoriesImport((bool) ($data['update_existing'] ?? false));
            Excel::import($import, $data['file']);
            TaggedCache::tags(['categories'])->flush();

            return $this->success($import->report(), 'Categories import completed.');
        } catch (\Throwable $exception) {
            Log::error('Categories import failed', ['exception' => $exception]);

            return $this->error('Categories import failed. Verify the template and try again.', 422);
        }
    }

    private function validateImport(Request $request): array
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'extensions:xlsx,xls,csv',
                'mimes:xlsx,xls,csv',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv,application/zip',
                'max:5120',
            ],
            'update_existing' => ['sometimes', 'boolean'],
        ]);

        if (strtolower($validated['file']->getClientOriginalExtension()) === 'xlsx') {
            $this->assertSafeArchive($validated['file']->getRealPath());
        }

        return $validated;
    }

    private function assertSafeArchive(string $path): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw ValidationException::withMessages(['file' => ['The XLSX archive is malformed.']]);
        }

        try {
            $totalUncompressed = 0;
            if ($archive->numFiles > 250) {
                throw ValidationException::withMessages(['file' => ['The XLSX archive contains too many entries.']]);
            }

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stats = $archive->statIndex($index);
                $size = (int) ($stats['size'] ?? 0);
                $compressed = max(1, (int) ($stats['comp_size'] ?? 1));
                $totalUncompressed += $size;

                if ($size > 10 * 1024 * 1024 || $size / $compressed > 100) {
                    throw ValidationException::withMessages(['file' => ['The XLSX archive exceeds safe expansion limits.']]);
                }
            }

            if ($totalUncompressed > 25 * 1024 * 1024) {
                throw ValidationException::withMessages(['file' => ['The XLSX archive is too large when expanded.']]);
            }
        } finally {
            $archive->close();
        }
    }

    private function extension(Request $request): string
    {
        return $this->format($request) === 'csv' ? 'csv' : 'xlsx';
    }

    private function writer(Request $request): string
    {
        return $this->format($request) === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;
    }

    private function format(Request $request): string
    {
        return $request->validate([
            'format' => ['sometimes', Rule::in(['csv', 'xlsx'])],
        ])['format'] ?? 'xlsx';
    }
}
