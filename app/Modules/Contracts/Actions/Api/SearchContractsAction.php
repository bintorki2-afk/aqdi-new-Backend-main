<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;
use Illuminate\Support\Collection;

class SearchContractsAction
{
    public function execute(string $searchTerm, ?int $userId = null): Collection
    {
        return Contract::query()
            ->ownedBy($userId ?? Contract::requireApiUserId())
            ->where(function ($query) use ($searchTerm) {
                $query->where('tenant_id_num', 'like', '%'.$searchTerm.'%')
                    ->orWhere('property_owner_id_num', 'like', '%'.$searchTerm.'%');
            })
            ->get();
    }
}
