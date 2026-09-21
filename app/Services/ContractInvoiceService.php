<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefundableContract;
use App\Support\DocFee;
use Illuminate\Support\Carbon;

class ContractInvoiceService
{
    /**
     * Build (and persist if needed) the invoice payload for the mobile invoice screen.
     *
     * @return array<string, mixed>
     */
    public function forContract(Contract $contract): array
    {
        $contract->loadMissing(['user', 'contractStatus', 'refundableContract']);

        $payment = $this->resolveSuccessfulPayment($contract);
        $invoice = $this->ensureInvoiceRecord($contract, $payment);
        $amount = $this->resolveAmount($contract, $payment, $invoice);
        $status = $this->resolveStatus($contract);
        $description = $this->lineDescription($contract);
        $issuedAt = $this->resolveIssuedAt($contract, $payment, $invoice);

        $items = [
            [
                'index' => 1,
                'description' => $description,
                'amount' => $amount,
                'amount_label' => $this->formatAmountLabel($amount),
            ],
        ];

        return [
            'id' => $invoice->id,
            'contract_id' => $contract->id,
            'contract_uuid' => $contract->uuid,
            'platform_name' => 'عقدي',
            'platform_subtitle' => 'منصة توثيق عقود الإيجار',
            'title' => 'الفاتورة',
            'invoice_number' => $invoice->invoice_number,
            'invoice_no' => $invoice->invoice_number,
            'date' => $issuedAt->format('Y/m/d'),
            'time' => $issuedAt->locale('ar')->translatedFormat('h:i a'),
            'datetime_label' => $issuedAt->format('Y/m/d').' · '.$issuedAt->locale('ar')->translatedFormat('h:i a'),
            'issued_at' => $issuedAt->toIso8601String(),
            'reference_number' => $this->resolveReferenceNumber($contract, $payment, $invoice),
            'customer_name' => (string) ($contract->user?->name ?? ''),
            'order_number' => '#'.$contract->id,
            'order_no' => (string) $contract->id,
            'contract_type' => (string) $contract->contract_type,
            'contract_type_label' => Contract::contractTypeLabel((string) $contract->contract_type),
            'items' => $items,
            'total_amount' => $amount,
            'total_amount_label' => $this->formatAmountLabel($amount),
            'total_due_label' => 'الإجمالي المستحق',
            'currency' => 'ريال',
            'status' => $status['status'],
            'status_label' => $status['status_label'],
            'status_color' => $status['status_color'],
            'print_label' => 'طباعة / تحميل الفاتورة',
            'is_paid' => (bool) $contract->is_completed,
            'is_refunded' => $status['status'] === 'refunded',
        ];
    }

    public function ensureInvoiceRecord(Contract $contract, ?Payment $payment = null): Invoice
    {
        $existing = Invoice::query()
            ->where('contract_id', $contract->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $payment ??= $this->resolveSuccessfulPayment($contract);
        $amount = $this->resolveAmount($contract, $payment, null);
        $issuedAt = $this->resolveIssuedAt($contract, $payment, null);

        return Invoice::query()->create([
            'invoice_number' => 'INV-'.$contract->id,
            'order_number' => (string) $contract->id,
            'date' => $issuedAt->toDateString(),
            'customer_phone' => $contract->user?->mobile ?? null,
            'description' => $this->lineDescription($contract),
            'rental_fees' => 0,
            'service_fees' => $amount,
            'total_amount' => $amount,
            'contract_id' => $contract->id,
        ]);
    }

    private function resolveSuccessfulPayment(Contract $contract): ?Payment
    {
        if (! filled($contract->uuid)) {
            return null;
        }

        return Payment::query()
            ->successfulMatchingContractUuid($contract->uuid)
            ->latest('id')
            ->first();
    }

    private function resolveAmount(Contract $contract, ?Payment $payment, ?Invoice $invoice): float
    {
        if ($payment && $payment->amount !== null) {
            return round((float) $payment->amount, 2);
        }

        if ($invoice && $invoice->total_amount !== null) {
            return round((float) $invoice->total_amount, 2);
        }

        $docFee = DocFee::forContract($contract);
        if ($docFee && isset($docFee['doc_fee'])) {
            return round((float) $docFee['doc_fee'], 2);
        }

        return round((float) ($contract->getPriceContractAttribute() ?? 0), 2);
    }

    /**
     * @return array{status: string, status_label: string, status_color: string}
     */
    private function resolveStatus(Contract $contract): array
    {
        $refund = $contract->relationLoaded('refundableContract')
            ? $contract->refundableContract
            : RefundableContract::query()->where('contract_id', $contract->id)->latest('id')->first();

        if ($refund && $refund->is_refunded) {
            return [
                'status' => 'refunded',
                'status_label' => 'مُسترجعة',
                'status_color' => '#DC2626',
            ];
        }

        if ((int) $contract->contract_status_id === ContractStatus::RETURN_ID) {
            return [
                'status' => 'returned',
                'status_label' => 'مسترجع',
                'status_color' => '#DC2626',
            ];
        }

        if ((bool) $contract->is_completed) {
            return [
                'status' => 'paid',
                'status_label' => 'مدفوعة',
                'status_color' => '#16A34A',
            ];
        }

        return [
            'status' => 'unpaid',
            'status_label' => 'غير مدفوعة',
            'status_color' => '#6B7280',
        ];
    }

    private function lineDescription(Contract $contract): string
    {
        $typeLabel = Contract::contractTypeLabel((string) $contract->contract_type);

        return 'رسوم توثيق عقد إيجار '.$typeLabel;
    }

    private function resolveIssuedAt(Contract $contract, ?Payment $payment, ?Invoice $invoice): Carbon
    {
        if ($payment?->payment_date) {
            $date = Carbon::parse($payment->payment_date);
            if ($payment->created_at) {
                $date->setTimeFrom($payment->created_at);
            }

            return $date;
        }

        if ($invoice?->date) {
            $date = Carbon::parse($invoice->date);
            if ($invoice->created_at) {
                $date->setTimeFrom($invoice->created_at);
            }

            return $date;
        }

        if ($contract->updated_at && $contract->is_completed) {
            return Carbon::parse($contract->updated_at);
        }

        return Carbon::parse($contract->created_at ?? now());
    }

    private function resolveReferenceNumber(Contract $contract, ?Payment $payment, Invoice $invoice): string
    {
        if ($payment && filled($payment->name) && preg_match('/^[A-Za-z0-9_\-]+$/', (string) $payment->name)) {
            // Gateway / transaction reference when stored on payment.name
            if (! str_contains((string) $payment->name, ' ')) {
                return (string) $payment->name;
            }
        }

        if ($payment) {
            return (string) $payment->id;
        }

        return '70'.str_pad((string) $contract->id, 8, '0', STR_PAD_LEFT);
    }

    private function formatAmountLabel(float $amount): string
    {
        $formatted = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');

        return $formatted.' ريال';
    }
}
