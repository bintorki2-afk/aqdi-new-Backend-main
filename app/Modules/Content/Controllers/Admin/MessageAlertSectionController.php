<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\MergesMessageAlertRequestAliases;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\MessageAlertSection;
use App\Support\MessageAlertType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageAlertSectionController extends Controller
{
    use MergesMessageAlertRequestAliases;
    use Responser;

    public function index(Request $request)
    {
        try {
            $type = MessageAlertType::tryNormalize($request->input('type'));

            $query = MessageAlertSection::query()
                ->where('type', $type)
                ->orderBy('sort_order')
                ->orderBy('id');

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $sections = $query->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => $sections->items(),
                'pagination' => $this->paginate($sections),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Compact list for dropdowns (no pagination).
     * Query: type = client | employee (default client).
     */
    public function options(Request $request, ?string $audience = null)
    {
        try {
            $type = $audience !== null && $audience !== ''
                ? MessageAlertType::normalize($audience)
                : MessageAlertType::normalize($request->input('type'));

            $items = MessageAlertSection::query()
                ->where('type', $type)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name_ar', 'name_en', 'sort_order', 'type']);

            return $this->apiResponse($items, trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->mergeMessageAlertSectionAliases($request);
            $data = $request->validate($this->rules());
            $data['type'] = MessageAlertType::normalize($data['type'] ?? $request->input('type'));
            $section = MessageAlertSection::query()->create($data);

            return $this->apiResponse($section, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $section = MessageAlertSection::query()->findOrFail($id);

            return $this->apiResponse($section, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $section = MessageAlertSection::query()->findOrFail($id);
            $this->mergeMessageAlertSectionAliases($request);
            $data = $request->validate($this->rules(true));
            if ($request->has('type')) {
                $data['type'] = MessageAlertType::normalize($request->input('type'));
            }
            $section->update($data);

            return $this->apiResponse($section->fresh(), trans('api.updated_successfully'));
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
            $section = MessageAlertSection::query()->findOrFail($id);
            $section->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return [
            'name_ar' => "{$required}|string|max:255",
            'name_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'type' => 'nullable|'.MessageAlertType::validationRule(),
        ];
    }
}
