<?php

namespace App\Http\Controllers\api\admin\product;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ProductColors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ColorsController extends Controller
{
    use ApiResponse;

    public function create(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'color' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $size = ProductColors::create($data);

            return $this->success($size);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->error('something went wrong');
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'color' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $size = ProductColors::find($id);
            if (! $size) {
                return $this->notFound('Color not found');
            }
            $size->update($data);

            return $this->success($size);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->error('something went wrong');
        }
    }

    public function delete($id)
    {
        try {
            $size = ProductColors::find($id);
            $size->delete();

            return $this->success($size);
        } catch (\Throwable $th) {
            Log::error($th);

            return $this->error('something went wrong');
        }
    }
}
