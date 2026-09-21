<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdController extends Controller
{
    use Responser;

    public function index(Request $request)
    {
        try {
            $query = Ad::query();

            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->string('search') . '%');
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', (bool) $request->is_active);
            }

            $ads = $query->latest()->paginate((int) $request->get('per_page', 20));

            return $this->apiResponse([
                'items' => $ads->items(),
                'pagination' => $this->paginate($ads),
            ], trans('api.success'));
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $validated['image'] = fileUploader($request->file('image'), 'ads');
            $validated['is_active'] = $validated['is_active'] ?? true;

            $ad = Ad::query()->create($validated);

            return $this->apiResponse($ad, trans('api.created_successfully'), 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $ad = Ad::query()->findOrFail($id);

            return $this->apiResponse($ad, trans('api.success'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $ad = Ad::query()->findOrFail($id);

            $validated = $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            if ($request->hasFile('image')) {
                if ($ad->image) {
                    deleteFile($ad->image);
                }
                $validated['image'] = fileUploader($request->file('image'), 'ads');
            }

            $ad->update($validated);

            return $this->apiResponse($ad->fresh(), trans('api.updated_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $ad = Ad::query()->findOrFail($id);

            if ($ad->image) {
                deleteFile($ad->image);
            }

            $ad->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(trans('api.error_occurred') . ': ' . $e->getMessage(), 500);
        }
    }
}
