<?php

namespace App\Modules\RealEstate\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Http\Resources\RealEstateResource;
use App\Models\RealEstate;
use Illuminate\Http\Request;

class RealEstateController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = RealEstate::query();

            // Filter by contract_type
            if ($request->filled('contract_type')) {
                $query->where('contract_type', $request->string('contract_type'));
            }

            $reals = $query
                ->latest()
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $reals,
                RealEstateResource::collection($reals)
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    public function show($id)
{
    $realEstate = RealEstate::with(['user', 'units'])->find($id);

    if (! $realEstate) {
        return $this->errorMessage(trans('api.not_found'), 404);
    }

    return $this->apiResponse(
        new RealEstateResource($realEstate),
        trans('api.success')
    );
}

}
