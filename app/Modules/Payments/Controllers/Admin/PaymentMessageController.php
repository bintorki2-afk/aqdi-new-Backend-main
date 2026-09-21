<?php

namespace App\Modules\Payments\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\PaymentMessageResource;
use App\Http\Traits\Responser;
use App\Models\PaymentMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentMessageController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = PaymentMessage::query();

            if ($request->filled('type')) {
                $query->where('type', $request->string('type'));
            }

            $items = $query->orderBy('type')->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => PaymentMessageResource::collection($items->items()),
                'pagination' => $this->paginate($items),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $message = $this->persistMessage($request);

            return $this->apiResponse(
                new PaymentMessageResource($message),
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
            $message = PaymentMessage::query()->findOrFail($id);

            return $this->apiResponse(
                new PaymentMessageResource($message),
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
            $message = PaymentMessage::query()->findOrFail($id);
            $message = $this->persistMessage($request, $message);

            return $this->apiResponse(
                new PaymentMessageResource($message),
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
            $message = PaymentMessage::query()->findOrFail($id);
            $message->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function persistMessage(Request $request, ?PaymentMessage $message = null): PaymentMessage
    {
        $validated = $request->validate($this->rules($message));

        if ($message) {
            $message->update($validated);

            return $message->fresh();
        }

        return PaymentMessage::query()->create($validated);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?PaymentMessage $message = null): array
    {
        $isUpdate = $message !== null;

        return [
            'type' => [
                $isUpdate ? 'sometimes' : 'required',
                'required',
                Rule::in(['success', 'failed']),
                Rule::unique('payment_messages', 'type')->ignore($message?->id),
            ],
            'message' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:2048',
            'button_text_2' => 'nullable|string|max:255',
            'button_link_2' => 'nullable|string|max:2048',
        ];
    }
}
