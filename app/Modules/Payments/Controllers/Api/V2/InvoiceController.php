<?php

namespace App\Modules\Payments\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\InvoiceResource;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\ContractInvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use Responser;

    public function __construct(private readonly ContractInvoiceService $invoices)
    {
    }

    /**
     * List invoices for the authenticated user's contracts.
     * GET /api/v2/invoices
     */
    public function index(Request $request)
    {
        $userId = (int) auth()->id();

        $contracts = Contract::query()
            ->where('user_id', $userId)
            ->where('is_delete', 0)
            ->where(function ($q) {
                $q->where('is_completed', 1)
                    ->orWhereHas('refundableContract')
                    ->orWhereHas('invoices');
            })
            ->with(['user', 'contractStatus', 'refundableContract'])
            ->orderByDesc('id')
            ->paginate($this->perPageFromRequest($request, 10));

        $items = collect($contracts->items())
            ->map(fn (Contract $contract) => $this->invoices->forContract($contract))
            ->values();

        return $this->apiResponse(
            [
                'data' => InvoiceResource::collection($items),
                'pagination' => $this->paginate($contracts),
            ],
            trans('api.success')
        );
    }

    /**
     * Invoice for a contract (by contracts.id).
     * GET /api/v2/invoices/{contractId}
     * GET /api/v2/contracts/{contractId}/invoice
     */
    public function show(int $contractId)
    {
        $contract = $this->findOwnedContract($contractId);

        if (! $contract) {
            return $this->errorMessage(trans('api.contract_not_found'), 404);
        }

        return $this->apiResponse(
            new InvoiceResource($this->invoices->forContract($contract)),
            trans('api.success')
        );
    }

    /**
     * Invoice by invoice_number (e.g. INV-47990).
     * GET /api/v2/invoices/number/{invoiceNumber}
     */
    public function showByNumber(string $invoiceNumber)
    {
        $invoiceNumber = urldecode($invoiceNumber);

        $invoice = Invoice::query()
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (! $invoice || ! $invoice->contract_id) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        $contract = $this->findOwnedContract((int) $invoice->contract_id);

        if (! $contract) {
            return $this->errorMessage(trans('api.not_found'), 404);
        }

        return $this->apiResponse(
            new InvoiceResource($this->invoices->forContract($contract)),
            trans('api.success')
        );
    }

    private function findOwnedContract(int $contractId): ?Contract
    {
        return Contract::query()
            ->where('user_id', auth()->id())
            ->where('is_delete', 0)
            ->with(['user', 'contractStatus', 'refundableContract'])
            ->find($contractId);
    }
}
