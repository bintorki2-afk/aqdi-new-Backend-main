<?php

namespace App\Interfaces;

use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contract for a payment gateway integration.
 *
 * Any gateway (Moyasar, ClickPay, ...) implementing this interface can be swapped
 * in through the container without touching the controllers that consume it.
 */
interface PaymentGatewayInterface
{
    /**
     * Build a hosted-payment redirect response for a contract.
     *
     * @param  string  $client  "web" keeps frontend redirects; "app" omits them (default for API mobile clients).
     */
    public function createPaymentUrlResponse(string $uuid, string $client = 'web'): JsonResponse;

    /**
     * Request a redirect URL for a contract using a custom amount.
     *
     * @return array{payment_url: string, cart_amount: float, contract_uuid: string, payment_success_url: ?string, payment_error_url: ?string}
     */
    public function requestPaymentRedirectUrl(string $uuid, float $amount): array;

    /**
     * Request a redirect URL for a standalone (contract-less) payment.
     *
     * @return array{payment_url: string, cart_amount: float, contract_uuid: string, payment_success_url: ?string, payment_error_url: ?string}
     */
    public function requestPaymentRedirectUrlWithoutContract(string $contractUuid, float $amount): array;

    /**
     * Handle a gateway callback / webhook and persist the payment outcome.
     */
    public function processIpn(Request $request, string $uuid): void;

    /**
     * Build the payment status payload returned to the client.
     *
     * @return array<string, mixed>
     */
    public function paymentStatusPayload(
        string $uuid,
        string $result,
        ?string $paymentId = null,
        ?string $invoiceId = null
    ): array;

    /**
     * Whether local/gateway state shows this contract uuid as paid.
     */
    public function isPaymentConfirmed(
        string $uuid,
        ?string $paymentId = null,
        ?string $invoiceId = null
    ): bool;

    /**
     * Sync local payment/contract state from the gateway using contract metadata.
     *
     * @return array<string, mixed>
     */
    public function syncGatewayPaymentStatus(string $uuid, ?string $paymentId = null, ?string $invoiceId = null): array;

    /**
     * Calculate the payable amount for a contract (services + period + coupons).
     */
    public function calculateCartAmount(Contract $contract): float;
}
