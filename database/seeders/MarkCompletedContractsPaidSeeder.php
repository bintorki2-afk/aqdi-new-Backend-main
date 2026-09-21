<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MarkCompletedContractsPaidSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = Contract::query()
            ->where('is_completed', true)
            ->where('is_delete', false)
            ->whereDoesntHave('contractPayments', fn ($q) => $q->where('status', 'success'))
            ->get(['id', 'uuid', 'contract_type', 'created_at']);

        if ($contracts->isEmpty()) {
            $this->command?->info('MarkCompletedContractsPaidSeeder: nothing to do.');

            return;
        }

        $now = Carbon::now();
        $count = 0;

        foreach ($contracts as $c) {
            Payment::query()->create([
                'name' => 'عقد توثيق',
                'amount' => $c->contract_type === 'commercial' ? 299 : 199,
                'payment_date' => ($c->created_at ?? $now)->copy()->toDateString(),
                'contract_uuid' => $c->uuid,
                'tran_currency' => 'SAR',
                'payment_method' => 'mada',
                'status' => 'success',
            ]);

            $count++;
        }

        $this->command?->info('MarkCompletedContractsPaidSeeder: marked '.$count.' completed contracts as paid.');
    }
}

