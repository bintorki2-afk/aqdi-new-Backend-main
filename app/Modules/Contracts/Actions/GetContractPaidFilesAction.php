<?php

namespace App\Modules\Contracts\Actions;

use App\Models\Contract;

class GetContractPaidFilesAction
{
    /**
     * @return list<array{file: string, created_at: mixed, user: mixed, contract_uuid: mixed}>
     */
    public function execute(string $uuid): array
    {
        $contracts = Contract::where('uuid', $uuid)->whereNotNull('file')->get();
        $files = [];

        foreach ($contracts as $contract) {
            $filePath = getFilePath($contract->file);
            $filePath = str_replace('public/', '', $filePath);

            $files[] = [
                'file' => $filePath,
                'created_at' => $contract->created_at,
                'user' => $contract->user_id,
                'contract_uuid' => $contract->uuid,
            ];
        }

        return $files;
    }
}
