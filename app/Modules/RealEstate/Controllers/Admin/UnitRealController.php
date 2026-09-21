<?php

namespace App\Modules\RealEstate\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitsResource;
use App\Http\Traits\Responser;
use App\Models\UnitsReal;
use Illuminate\Http\Request;

class UnitRealController extends Controller
{
    use Responser;

    /**
     * Display a listing of units
     */
    public function index(Request $request)
    {
        try {
            $query = UnitsReal::with(['user', 'realEstate', 'unitType', 'unitUsage']);

             if ($request->has('real_estates_units_id')) {
                $query->where('real_estates_units_id', $request->real_estates_units_id);
            }

             if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

             if ($request->has('unit_type_id')) {
                $query->where('unit_type_id', $request->unit_type_id);
            }

             if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('unit_number', 'like', "%{$search}%")
                      ->orWhere('unit_area', 'like', "%{$search}%");
                });
            }

            $units = $query->latest()->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $units,
                UnitsResource::collection($units)
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created unit
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'real_estates_units_id' => 'nullable|exists:real_estates,id',
                'unit_type_id' => 'nullable|exists:unit_types,id',
                'unit_usage_id' => 'nullable|exists:usage_units,id',
                'unit_area' => 'nullable|string|max:255',
                'unit_number' => 'nullable|string|max:255',
                'floor_number' => 'nullable|string|max:255',
                'property_city_id' => 'nullable|exists:cities,id',
                'water_meter_number' => 'nullable|string|max:255',
                'electricity_meter_number' => 'nullable|string|max:255',
                'electricity_meter_ownership' => 'nullable|in:owner,tenant',
                'water_meter_ownership' => 'nullable|in:owner,tenant',
                'Number_parking_spaces' => 'nullable|string|max:255',
                'tootal_rooms' => 'nullable|string|max:255',
                'The_number_of_toilets' => 'nullable|string|max:255',
                'The_number_of_halls' => 'nullable|string|max:255',
                'The_number_of_kitchens' => 'nullable|string|max:255',
                'split_ac' => 'nullable|string|max:255',
                'window_ac' => 'nullable|string|max:255',
                'sub_delay' => 'nullable|string|max:255',
                'real_estates_units' => 'nullable|string|max:255',
            ]);

            $unit = UnitsReal::create($validated);

            return $this->apiResponse(
                $unit->load(['user', 'realEstate', 'unitType', 'unitUsage']),
                trans('api.created_successfully'),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified unit
     */
    public function show($id)
    {
        try {
            $unit = UnitsReal::with(['user', 'realEstate', 'unitType', 'unitUsage', 'contracts'])
                ->find($id);

            if (!$unit) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            return $this->apiResponse(
                $unit,
                trans('api.success')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified unit
     */
    public function update(Request $request, $id)
    {
        try {
            $unit = UnitsReal::find($id);

            if (!$unit) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'real_estates_units_id' => 'nullable|exists:real_estates,id',
                'unit_type_id' => 'nullable|exists:unit_types,id',
                'unit_usage_id' => 'nullable|exists:usage_units,id',
                'unit_area' => 'nullable|string|max:255',
                'unit_number' => 'nullable|string|max:255',
                'floor_number' => 'nullable|string|max:255',
                'property_city_id' => 'nullable|exists:cities,id',
                'water_meter_number' => 'nullable|string|max:255',
                'electricity_meter_number' => 'nullable|string|max:255',
                'electricity_meter_ownership' => 'nullable|in:owner,tenant',
                'water_meter_ownership' => 'nullable|in:owner,tenant',
                'Number_parking_spaces' => 'nullable|string|max:255',
                'tootal_rooms' => 'nullable|string|max:255',
                'The_number_of_toilets' => 'nullable|string|max:255',
                'The_number_of_halls' => 'nullable|string|max:255',
                'The_number_of_kitchens' => 'nullable|string|max:255',
                'split_ac' => 'nullable|string|max:255',
                'window_ac' => 'nullable|string|max:255',
                'sub_delay' => 'nullable|string|max:255',
                'real_estates_units' => 'nullable|string|max:255',
            ]);

            $unit->update($validated);

            return $this->apiResponse(
                $unit->fresh(['user', 'realEstate', 'unitType', 'unitUsage']),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified unit
     */
    public function destroy($id)
    {
        try {
            $unit = UnitsReal::find($id);

            if (!$unit) {
                return $this->errorMessage(
                    trans('api.not_found'),
                    404
                );
            }

            $unit->delete();

            return $this->apiResponse(
                [],
                trans('api.deleted_successfully')
            );
        } catch (\Exception $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
}
