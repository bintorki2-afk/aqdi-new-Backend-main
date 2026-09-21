<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V2\Api\OrderResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use Illuminate\Http\Request;

class IncompleteContract extends Controller
{
    use Responser;

    public function orders(Request $request)
    {
        $orders = Contract::where('is_completed', 0)->latest()->paginate($this->perPageFromRequest($request));

        return $this->paginatedApiResponse(
            $orders,
            OrderResource::collection($orders)
        );
    }


    
}
