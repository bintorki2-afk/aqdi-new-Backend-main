<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\RefundableContract;

/**
 * Canonical return-request fields for admin order list rows and order detail.
 *
 * has_return_request is true only when a refundable_contracts row exists.
 * Do not infer a request from contract_status_id, refund_amount, or return_contract.
 */
class ContractReturnRequestFields
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Contract $contract, ?RefundableContract $refund): array
    {
        $hasRequest = $refund !== null;
        $refundAmountValue = self::refundAmount($refund);
        $draftContractNumber = $hasRequest && $contract->getKey()
            ? str_pad((string) $contract->id, 6, '0', STR_PAD_LEFT)
            : null;
        $approval = self::managementApproval($refund);
        $refundId = $refund?->id;

        return [
            'has_return_request' => $hasRequest,
            'return_request_status' => self::status($refund),
            'refund_contract_id' => $refundId,
            'contract_id' => $contract->getKey(),
            'draft_contract_number' => $draftContractNumber,
            // JSON null when no row — never 0, "0.00", or "".
            'refund_amount' => $refundAmountValue,
            'return_contract' => $draftContractNumber !== null && $refundAmountValue !== null,
            'refund_id' => $refundId,
            'refundable_contract_id' => $refundId,
            // JSON null when no row — never {}, 0, or [].
            'refund' => $hasRequest ? [
                'id' => $refund->id,
                'refund_amount' => $refundAmountValue,
                'admin_confirmed' => $refund->admin_confirmed,
                'is_refunded' => (bool) $refund->is_refunded,
                'notes' => $refund->notes,
                'reference_number' => RefundableContractReference::for($refund),
                'management_approval' => $approval,
            ] : null,
            'refundable_contract' => $hasRequest ? [
                'id' => $refund->id,
                'refund_amount' => $refundAmountValue,
                'admin_confirmed' => $refund->admin_confirmed,
                'is_refunded' => (bool) $refund->is_refunded,
            ] : null,
            'admin_confirmed' => $refund?->admin_confirmed,
            'management_approval' => $approval,
            'is_refunded' => $hasRequest ? (bool) $refund->is_refunded : null,
            'customer_refunded' => $hasRequest ? (bool) $refund->is_refunded : null,
            'refunded' => $hasRequest ? (bool) $refund->is_refunded : null,
            'reference_number' => $hasRequest ? RefundableContractReference::for($refund) : null,
            'refund_notes' => $refund?->notes,
        ];
    }

    /**
     * pending | approved | rejected | refunded | null (no refundable_contracts row).
     */
    public static function status(?RefundableContract $refund): ?string
    {
        if ($refund === null) {
            return null;
        }

        if ($refund->is_refunded) {
            return 'refunded';
        }

        if ($refund->admin_confirmed === null) {
            return 'pending';
        }

        if (self::isAdminConfirmedTrue($refund->admin_confirmed)) {
            return 'approved';
        }

        return 'rejected';
    }

    /**
     * Amount from the refundable row, or JSON null when no row exists.
     * A stored 0 on an existing row is 0.0 — that still means a request exists.
     */
    public static function refundAmount(?RefundableContract $refund): ?float
    {
        if ($refund === null) {
            return null;
        }

        $amount = $refund->refund_amount;

        if ($amount === null || $amount === '') {
            return null;
        }

        return round((float) $amount, 2);
    }

    /**
     * @return array{approved: bool|null, label_ar: string}|null
     */
    public static function managementApproval(?RefundableContract $refund): ?array
    {
        if ($refund === null) {
            return null;
        }

        if ($refund->admin_confirmed === null) {
            return [
                'approved' => null,
                'label_ar' => 'بانتظار الموافقة',
            ];
        }

        if (self::isAdminConfirmedTrue($refund->admin_confirmed)) {
            return [
                'approved' => true,
                'label_ar' => 'تم الموافقة',
            ];
        }

        return [
            'approved' => false,
            'label_ar' => 'لم تتم الموافقة',
        ];
    }

    private static function isAdminConfirmedTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }
}
