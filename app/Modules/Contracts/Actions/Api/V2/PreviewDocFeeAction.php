<?php

namespace App\Modules\Contracts\Actions\Api\V2;

use App\Http\Requests\Api\V2\Contract\DocFeePreviewRequest;
use App\Support\DocFee;

class PreviewDocFeeAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(DocFeePreviewRequest $request): array
    {
        $summary = DocFee::summarize(
            (string) $request->input('contract_type'),
            'other',
            (int) $request->input('duration_years'),
            (int) $request->input('duration_months'),
        );

        return [
            'duration_years' => $summary['duration_years'],
            'duration_months' => $summary['duration_months'],
            'total_months' => $summary['total_months'],
            'billable_years' => $summary['billable_years'],
            'has_extra_months' => $summary['has_extra_months'],
            'amount' => $summary['doc_fee'],
            'doc_fee' => $summary['doc_fee'],
            'doc_fee_lines' => $summary['doc_fee_lines'],
            'text' => implode("\n", $summary['doc_fee_lines']),
        ];
    }
}
