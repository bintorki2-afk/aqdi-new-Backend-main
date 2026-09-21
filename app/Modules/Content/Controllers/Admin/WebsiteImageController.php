<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\WebsiteImage;
use App\Support\WebsiteImageDefinitions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class WebsiteImageController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = WebsiteImage::query()->orderBy('sort_order')->orderBy('id');

            if ($request->filled('search')) {
                $search = (string) $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                        ->orWhere('label_ar', 'like', "%{$search}%")
                        ->orWhere('label_en', 'like', "%{$search}%")
                        ->orWhere('alt_ar', 'like', "%{$search}%")
                        ->orWhere('meta_title_ar', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            $items = $query->get()->map(fn (WebsiteImage $image) => $image->toAdminRow())->values()->all();

            return $this->apiResponse([
                'summary' => [
                    'total' => count($items),
                    'active' => collect($items)->where('is_active', true)->count(),
                    'with_alt' => collect($items)->filter(fn ($row) => filled($row['alt_ar']) || filled($row['alt_en']))->count(),
                    'with_meta' => collect($items)->filter(fn ($row) => filled($row['meta_title_ar']) || filled($row['meta_description_ar']))->count(),
                ],
                'items' => $items,
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $image = WebsiteImage::query()->findOrFail($id);

            return $this->apiResponse($image->toAdminRow(), trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate($this->rules());
            if ($request->hasFile('image')) {
                $data['path'] = fileUploader($request->file('image'), 'website-images');
            }
            unset($data['image']);

            $image = WebsiteImage::query()->create($data);

            return $this->apiResponse($image->toAdminRow(), trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorMessage($this->firstError($e), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $image = WebsiteImage::query()->findOrFail($id);
            $data = $request->validate($this->rules($image->id));

            if ($request->hasFile('image')) {
                if ($image->path) {
                    deleteFile($image->path);
                }
                $data['path'] = fileUploader($request->file('image'), 'website-images');
            }
            unset($data['image']);

            $image->update($data);

            return $this->apiResponse($image->fresh()->toAdminRow(), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorMessage($this->firstError($e), 422);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $image = WebsiteImage::query()->findOrFail($id);
            if ($image->path) {
                deleteFile($image->path);
            }
            $image->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function syncDefaults()
    {
        try {
            $created = 0;
            $updated = 0;

            foreach (WebsiteImageDefinitions::all() as $row) {
                $existing = WebsiteImage::query()->where('key', $row['key'])->first();
                if ($existing) {
                    $existing->fill([
                        'label_ar' => $row['label_ar'],
                        'label_en' => $row['label_en'] ?? null,
                        'static_path' => $row['static_path'] ?? $existing->static_path,
                        'sort_order' => $row['sort_order'] ?? $existing->sort_order,
                    ]);
                    if (! filled($existing->alt_ar) && filled($row['alt_ar'] ?? null)) {
                        $existing->alt_ar = $row['alt_ar'];
                    }
                    if (! filled($existing->alt_en) && filled($row['alt_en'] ?? null)) {
                        $existing->alt_en = $row['alt_en'];
                    }
                    if (! filled($existing->meta_title_ar) && filled($row['meta_title_ar'] ?? null)) {
                        $existing->meta_title_ar = $row['meta_title_ar'];
                    }
                    if (! filled($existing->meta_description_ar) && filled($row['meta_description_ar'] ?? null)) {
                        $existing->meta_description_ar = $row['meta_description_ar'];
                    }
                    $existing->save();
                    $updated++;
                } else {
                    WebsiteImage::query()->create(array_merge([
                        'is_active' => true,
                    ], $row));
                    $created++;
                }
            }

            return $this->apiResponse([
                'created' => $created,
                'updated' => $updated,
                'items' => WebsiteImage::query()->orderBy('sort_order')->get()->map->toAdminRow()->values()->all(),
            ], trans('api.success'));
        } catch (Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?int $ignoreId = null): array
    {
        $required = $ignoreId ? 'sometimes|required' : 'required';

        return [
            'key' => [
                $required,
                'string',
                'max:64',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('website_images', 'key')->ignore($ignoreId),
            ],
            'label_ar' => [$required, 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'static_path' => ['nullable', 'string', 'max:500'],
            'alt_ar' => ['nullable', 'string', 'max:255'],
            'alt_en' => ['nullable', 'string', 'max:255'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:1000'],
            'meta_description_en' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:4096'],
        ];
    }

    protected function firstError(ValidationException $e): string
    {
        return collect($e->errors())->flatten()->first() ?: 'البيانات غير صالحة.';
    }
}
