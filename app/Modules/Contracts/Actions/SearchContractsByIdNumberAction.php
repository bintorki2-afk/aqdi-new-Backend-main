<?php

namespace App\Modules\Contracts\Actions;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Collection;

class SearchContractsByIdNumberAction
{
    public function execute(string $searchTerm): Collection
    {
        return Contract::where('tenant_id_num', 'like', '%'.$searchTerm.'%')
            ->orWhere('property_owner_id_num', 'like', '%'.$searchTerm.'%')
            ->get();
    }
}
