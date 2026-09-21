<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\InstructionSectionImageResource;
use App\Http\Resources\Admin\V2\Api\InstructionSectionResource;
use App\Http\Traits\Responser;
use App\Models\InstructionSection;
use App\Models\InstructionSectionImage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InstructionSectionController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $sections = InstructionSection::query()
                ->with(['images' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPageFromRequest($request));

            return $this->paginatedApiResponse(
                $sections,
                InstructionSectionResource::collection($sections)
            );
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $section = $this->findSection($id);

            return $this->apiResponse(
                new InstructionSectionResource($section),
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
            $section = InstructionSection::query()->findOrFail($id);
            $section->update($request->validate($this->sectionRules()));
            $section = $this->findSection($section->id);

            return $this->apiResponse(
                new InstructionSectionResource($section),
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

    public function toggle(Request $request, int $id)
    {
        try {
            $section = InstructionSection::query()->findOrFail($id);
            $validated = $request->validate([
                'is_active' => ['required', 'boolean'],
            ]);
            $section->update(['is_active' => $validated['is_active']]);
            $section = $this->findSection($section->id);

            return $this->apiResponse(
                new InstructionSectionResource($section),
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
            $section = InstructionSection::query()
                ->with('images')
                ->findOrFail($id);

            foreach ($section->images as $image) {
                $this->deleteImageFile($image);
            }

            $section->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function uploadImage(Request $request, int $id)
    {
        try {
            $section = InstructionSection::query()->findOrFail($id);

            $validated = $request->validate([
                'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
                'title_ar' => ['nullable', 'string', 'max:255'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ]);

            $file = $request->file('image');
            $path = fileUploader($file, 'instructions');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

            $image = $section->images()->create([
                'title_ar' => $validated['title_ar'] ?? null,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_extension' => $extension,
                'sort_order' => $validated['sort_order']
                    ?? ((int) $section->images()->max('sort_order')) + 1,
            ]);

            return $this->apiResponse(
                new InstructionSectionImageResource($image),
                trans('api.created_successfully'),
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    public function destroyImage(int $id, int $imageId)
    {
        try {
            $image = InstructionSectionImage::query()
                ->where('instruction_section_id', $id)
                ->findOrFail($imageId);

            $this->deleteImageFile($image);
            $image->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred').': '.$e->getMessage(), 500);
        }
    }

    private function findSection(int $id): InstructionSection
    {
        return InstructionSection::query()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->findOrFail($id);
    }

    private function deleteImageFile(InstructionSectionImage $image): void
    {
        if ($image->path) {
            deleteFile($image->path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionRules(): array
    {
        return [
            'title_ar' => 'sometimes|required|string|max:255',
            'description_ar' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
