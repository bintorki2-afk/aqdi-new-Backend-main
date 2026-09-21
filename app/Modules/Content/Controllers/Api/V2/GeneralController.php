<?php

namespace App\Modules\Content\Controllers\Api\V2;

use App\Models\Ad;

class GeneralController extends \App\Http\Controllers\Api\GeneralController
{
    public function ads()
    {
        $ads = Ad::get()
            ->where('is_active', 1)
            ->latest('id')
            ->get();

        return $this->apiResponse($ads, trans('api.success'));
    }
}

