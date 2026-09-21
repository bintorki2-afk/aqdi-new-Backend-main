<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\MergesMessageAlertRequestAliases;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\MessageAlertSection;
use App\Models\MessageAlertSectionItem;
use App\Support\MessageAlertType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageAlertSectionItemController extends Controller
{
    use MergesMessageAlertRequestAliases;
    use Responser;

    /**
     * List items belonging to one section (REST-friendly path).
     */
    public function indexForSection(Request $request, int $sectionId)
    {
        MessageAlertSection::query()->findOrFail($sectionId);
        $request->merge(['message_alert_section_id' => $sectionId]);

        return $this->index($request);
    }

    /**
     * Create an item under a section (`message_alert_section_id` taken from URL).
     */
    public function storeForSection(Request $request, int $sectionId)
    {
        MessageAlertSection::query()->findOrFail($sectionId);
        $request->merge(['message_alert_section_id' => $sectionId]);

        return $this->store($request);
    }

    public function index(Request $request)
    {
        try {
            $type = MessageAlertType::normalize($request->input('type'));

            $query = MessageAlertSectionItem::query()
                ->whereHas('section', fn ($q) => $q->where('type', $type))
                ->orderBy('sort_order')
                ->orderBy('id');

            if ($request->filled('message_alert_section_id')) {
                $query->where('message_alert_section_id', (int) $request->input('message_alert_section_id'));
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            }

            $items = $query->with('section:id,name_ar,name_en,type')->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => $items->items(),
                'pagination' => $this->paginate($items),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * Items for one section — for the “بند القسم” dropdown.
     */
    public function options(Request $request, ?string $audience = null)
    {
        try {
            $this->mergeMessageAlertSectionItemAliases($request);

            $request->validate([
                'message_alert_section_id' => 'required|exists:message_alert_sections,id',
                'type' => 'nullable|in:client,employee',
            ]);

            $section = MessageAlertSection::query()->findOrFail((int) $request->input('message_alert_section_id'));
            $expected = $audience !== null && $audience !== ''
                ? MessageAlertType::normalize($audience)
                : ($request->filled('type') ? MessageAlertType::normalize($request->input('type')) : null);

            if ($expected !== null && $section->type !== $expected) {
                throw ValidationException::withMessages([
                    'type' => [__('The section does not belong to the requested type.')],
                    'message_alert_section_id' => [__('The section does not belong to the requested type.')],
                ]);
            }

            $items = MessageAlertSectionItem::query()
                ->where('message_alert_section_id', $section->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'message_alert_section_id', 'name_ar', 'name_en', 'sort_order']);

            return $this->apiResponse($items, trans('api.success'));
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $this->mergeMessageAlertSectionItemAliases($request);
            $item = MessageAlertSectionItem::query()->create(
                $request->validate($this->rules())
            );

            return $this->apiResponse($item->load('section:id,name_ar,name_en,type'), trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $item = MessageAlertSectionItem::query()->with('section:id,name_ar,name_en,type')->findOrFail($id);

            return $this->apiResponse($item, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $item = MessageAlertSectionItem::query()->findOrFail($id);
            $this->mergeMessageAlertSectionItemAliases($request);
            $item->update($request->validate($this->rules(true)));

            return $this->apiResponse($item->fresh()->load('section:id,name_ar,name_en,type'), trans('api.updated_successfully'));
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
            $item = MessageAlertSectionItem::query()->findOrFail($id);
            $item->delete();

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
            'message_alert_section_id' => "{$required}|exists:message_alert_sections,id",
            'name_ar' => "{$required}|string|max:255",
            'name_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
