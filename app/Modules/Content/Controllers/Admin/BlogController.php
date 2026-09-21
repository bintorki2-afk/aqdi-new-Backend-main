<?php

namespace App\Modules\Content\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\BlogIndexRequest;
use App\Http\Requests\Admin\V2\StoreBlogRequest;
use App\Http\Requests\Admin\V2\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Http\Traits\Responser;
use App\Models\Blog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class BlogController extends Controller
{
    use Responser;

    /**
     * Display a listing of blogs
     */
    public function index(BlogIndexRequest $request)
    {
        try {
            $blogs = Blog::latest()->paginate($this->perPageFromRequest($request, 10));

            return $this->paginatedApiResponse(
                $blogs,
                BlogResource::collection($blogs)
            );

        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created blog
     */
    public function store(StoreBlogRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            $data['is_active'] = $data['is_active'] ?? 0;

            $blog = Blog::create($data);

            return $this->apiResponse(
                new BlogResource($blog),
                trans('api.created_successfully'),
                201
            );

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified blog
     */
    public function show($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            return $this->apiResponse(
                new BlogResource($blog),
                trans('api.success')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    /**
     * Update the specified blog
     */
    public function update(UpdateBlogRequest $request, $id)
    {
        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if ($blog->image) {
                    deleteFile($blog->image);
                }
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            $blog->update($data);

            return $this->apiResponse(
                new BlogResource($blog->fresh()),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified blog
     */
    public function destroy($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            if ($blog->image) {
                deleteFile($blog->image);
            }

            $blog->delete();

            return $this->apiResponse([], trans('api.deleted_successfully'));

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        try {
            $blog = Blog::findOrFail($id);

            $blog->update([
                'is_active' => !$blog->is_active
            ]);

            return $this->apiResponse(
                new BlogResource($blog->fresh()),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    /**
     * Blog statistics
     */
    public function statistics()
    {
        try {
            return $this->apiResponse([
                'total' => Blog::count(),
                'active' => Blog::where('is_active', 1)->count(),
                'inactive' => Blog::where('is_active', 0)->count(),
            ], trans('api.success'));

        } catch (\Throwable $e) {
            return $this->errorMessage(
                trans('api.error_occurred') . ': ' . $e->getMessage(),
                500
            );
        }
    }
}
