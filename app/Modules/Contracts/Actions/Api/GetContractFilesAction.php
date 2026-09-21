<?php

namespace App\Modules\Contracts\Actions\Api;

use App\Models\Contract;

class GetContractFilesAction
{
    /**
     * @return list<array{file: string, created_at: mixed, user: mixed, contract_uuid: mixed}>
     */
    public function execute(string $uuid, ?int $userId = null): array
    {
        $contracts = Contract::query()
            ->ownedBy($userId ?? Contract::requireApiUserId())
            ->where('uuid', $uuid)
            ->whereNotNull('file')
            ->get();
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
