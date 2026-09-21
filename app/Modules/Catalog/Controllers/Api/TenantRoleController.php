<?php

namespace App\Modules\Catalog\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\TenantRole;
use App\Modules\Catalog\Resources\Admin\TenantRoleResource;
use App\Shared\Responses\Responser;

class TenantRoleController extends Controller
{
    use Responser;

    public function index()
    {
        $roles = TenantRole::query()->orderBy('id')->get();

        return $this->apiResponse(
            TenantRoleResource::collection($roles),
            trans('api.roles')
        );
    }
}
