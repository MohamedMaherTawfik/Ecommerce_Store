<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxRuleRequest;
use App\Models\TaxRule;

class TaxRuleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(TaxRule::orderByDesc('priority')->paginate(20), 'Tax rules loaded.');
    }

    public function show(int $id)
    {
        return $this->success(TaxRule::findOrFail($id), 'Tax rule loaded.');
    }

    public function store(TaxRuleRequest $request)
    {
        return $this->success(TaxRule::create($request->validated()), 'Tax rule created.');
    }

    public function update(TaxRuleRequest $request, int $id)
    {
        $rule = TaxRule::findOrFail($id);
        $rule->update($request->validated());

        return $this->success($rule->fresh(), 'Tax rule updated.');
    }

    public function destroy(int $id)
    {
        TaxRule::findOrFail($id)->delete();

        return $this->success([], 'Tax rule deleted.');
    }
}
