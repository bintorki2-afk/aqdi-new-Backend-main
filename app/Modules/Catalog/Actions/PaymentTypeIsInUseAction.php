<?php

namespace App\Modules\Catalog\Actions;

use App\Models\Contract;

class PaymentTypeIsInUseAction
{
    public function execute(int $id): bool
    {
        return Contract::query()->where('payment_type_id', $id)->exists();
    }
}
