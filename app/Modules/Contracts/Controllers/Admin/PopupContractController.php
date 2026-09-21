<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\PopupContractResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\PopupContract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PopupContractController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = PopupContract::query();

            if ($request->filled('instrument_type')) {
                $query->where(
                    'instrument_type',
                    Contract::normalizeInstrumentType($request->string('instrument_type')->toString())
                );
            }

            if ($request->has('popup_status_contract')) {
                $query->where('popup_status_contract', $request->boolean('popup_status_contract'));
            }

            if ($request->has('popup_status_realestate')) {
                $query->where('popup_status_realestate', $request->boolean('popup_status_realestate'));
            }

            $items = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => PopupContractResource::collection($items->items()),
                'pagination' => $this->paginate($items),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $popup = $this->persistPopup($request);

            return $this->apiResponse(
                new PopupContractResource($popup),
                trans('api.created_successfully'),
                201
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $popup = PopupContract::query()->findOrFail($id);

            return $this->apiResponse(
                new PopupContractResource($popup),
                trans('api.success')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $popup = PopupContract::query()->findOrFail($id);
            $popup = $this->persistPopup($request, $popup);

            return $this->apiResponse(
                new PopupContractResource($popup),
                trans('api.updated_successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $popup = PopupContract::query()->findOrFail($id);
            $popup->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function persistPopup(Request $request, ?PopupContract $popup = null): PopupContract
    {
        if ($request->filled('instrument_type')) {
            $request->merge([
                'instrument_type' => Contract::normalizeInstrumentType($request->input('instrument_type')),
            ]);
        }

        $validated = $request->validate($this->rules($popup !== null));

        if (array_key_exists('popup_status_contract', $validated)) {
            $validated['popup_status_contract'] = $request->boolean('popup_status_contract');
        }

        if (array_key_exists('popup_status_realestate', $validated)) {
            $validated['popup_status_realestate'] = $request->boolean('popup_status_realestate');
        }

        if ($popup) {
            $popup->update($validated);

            return $popup->fresh();
        }

        return PopupContract::query()->create($validated);
    }

    private function rules(bool $isUpdate = false): array
    {
        return [
            'instrument_type' => [
                $isUpdate ? 'sometimes' : 'required',
                'required',
                Rule::in(Contract::instrumentTypes()),
            ],
            'popup_status_contract' => ($isUpdate ? 'sometimes|' : '').'boolean',
            'popup_status_realestate' => ($isUpdate ? 'sometimes|' : '').'boolean',
            'content_popup' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:2048',
        ];
    }
}
