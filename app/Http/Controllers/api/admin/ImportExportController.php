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
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        return $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'update_existing' => ['sometimes', 'boolean'],
        ]);
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
