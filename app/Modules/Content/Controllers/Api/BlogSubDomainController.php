<?php

namespace App\Modules\Content\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V2\StoreBlogRequest;
use App\Http\Requests\Admin\V2\UpdateBlogRequest;
use App\Http\Requests\Api\Website\PublicBlogIndexRequest;
use App\Http\Resources\BlogResource;
use App\Http\Resources\Website\BlogListItemResource;
use App\Http\Traits\Responser;
use App\Models\Blog;
use App\Models\Seo;
use App\Services\PublicBlogService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;


class BlogSubDomainController extends Controller
{
    use Responser;

    public function __construct(private readonly PublicBlogService $publicBlogs)
    {
    }

    public function blogs(PublicBlogIndexRequest $request): JsonResponse
    {
        $blogs = $this->publicBlogs->paginate($request);

        return $this->withPublicCache(response()->json([
            'data' => BlogListItemResource::collection(collect($blogs->items()))->resolve(),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
            ],
        ]), 'list');
    }

    public function meta(): JsonResponse
    {
        return $this->withPublicCache(response()->json($this->publicBlogs->meta()), 'meta');
    }

    public function singleBlog(Request $request, $slug): JsonResponse
    {
        $found = $this->publicBlogs->findPublicBySlug((string) $slug);

        if (! $found) {
            return response()->json([
                'message' => __('api.blog_not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var Blog $blog */
        $blog = $found['blog'];

        if (! $this->publicBlogs->shouldSkipViewTracking($request)) {
            $blog->incrementViews();
        }

        return $this->withPublicCache(response()->json([
            'data' => $this->publicBlogs->showResource($blog, $found['extra']),
        ]), 'show');
    }

    private function withPublicCache(JsonResponse $response, string $type): JsonResponse
    {
        $header = (string) config('blogs.cache.'.$type, 'public, max-age=60');

        return $response->header('Cache-Control', $header);
    }


     /**
     * Store a newly created blog
     */
    public function store(StoreBlogRequest $request)
    {
        if ($denied = $this->denyUnlessSeo($request)) {
            return $denied;
        }

        try {
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $this->fillImageDimensions($data, $request->file('image'));
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            if ($request->hasFile('og_image')) {
                $data['og_image'] = fileUploader($request->file('og_image'), 'blogs');
            }

            $data['is_active'] = $data['is_active'] ?? 0;
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $blog = Blog::create($data);

            if (is_array($tags)) {
                $blog->syncTagsInput($tags);
            }

            $this->publicBlogs->forgetMetaCache();

            return $this->apiResponse(
                new BlogResource($blog->load('tags')),
                trans('api.created_successfully'),
                201
            );

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Throwable $e) {
            report($e);

            return $this->errorMessage(
                trans('api.error_occurred'),
                500
            );
        }
    }

    

    /**
     * Update the specified blog
     */
    public function update(UpdateBlogRequest $request, $id)
    {
        if ($denied = $this->denyUnlessSeo($request)) {
            return $denied;
        }

        try {
            $blog = Blog::findOrFail($id);
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if ($blog->image) {
                    deleteFile($blog->image);
                }
                $this->fillImageDimensions($data, $request->file('image'));
                $data['image'] = fileUploader($request->file('image'), 'blogs');
            }

            if ($request->hasFile('og_image')) {
                if ($blog->og_image) {
                    deleteFile($blog->og_image);
                }
                $data['og_image'] = fileUploader($request->file('og_image'), 'blogs');
            }

            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $blog->update($data);

            if (is_array($tags)) {
                $blog->syncTagsInput($tags);
            }

            $this->publicBlogs->forgetMetaCache();

            return $this->apiResponse(
                new BlogResource($blog->fresh()->load('tags')),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        } catch (\Throwable $e) {
            report($e);

            return $this->errorMessage(
                trans('api.error_occurred'),
                500
            );
        }
    }

    /**
     * Remove the specified blog
     */
    public function destroy($id)
    {
        if ($denied = $this->denyUnlessSeo(request())) {
            return $denied;
        }

        try {
            $blog = Blog::findOrFail($id);

            if ($blog->image) {
                deleteFile($blog->image);
            }

            $blog->delete();

            $this->publicBlogs->forgetMetaCache();

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
        if ($denied = $this->denyUnlessSeo(request())) {
            return $denied;
        }

        try {
            $blog = Blog::findOrFail($id);

            $blog->update([
                'is_active' => !$blog->is_active
            ]);

            $this->publicBlogs->forgetMetaCache();

            return $this->apiResponse(
                new BlogResource($blog->fresh()),
                trans('api.updated_successfully')
            );

        } catch (ModelNotFoundException) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }
    }

    // Login Seo 

    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation error',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $credentials = $request->only('email', 'password');

    if (!Auth::guard('seo')->attempt($credentials)) {
        return response()->json([
            'status'  => false,
            'message' => 'يرجى التأكد من البريد الالكتروني وكلمة المرور',
        ], 401);
    }

    /** @var \App\Models\User $user */
    $user = Auth::guard('seo')->user();

    // Create API token (Sanctum)
    $token = $user->createToken('seo-token')->plainTextToken;

    return response()->json([
        'status'  => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'data'    => [
            'user'  => $user,
            'token' => $token,
        ],
    ], 200);
}

    private function denyUnlessSeo(Request $request): ?JsonResponse
    {
        if ($request->user() instanceof Seo) {
            return null;
        }

        return $this->errorMessage(trans('api.forbidden'), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillImageDimensions(array &$data, UploadedFile $file): void
    {
        $path = $file->getPathname();
        if ($path === '' || ! is_file($path)) {
            return;
        }

        $dimensions = @getimagesize($path);
        if (! is_array($dimensions)) {
            return;
        }

        $data['image_width'] = $dimensions[0];
        $data['image_height'] = $dimensions[1];
    }
}
