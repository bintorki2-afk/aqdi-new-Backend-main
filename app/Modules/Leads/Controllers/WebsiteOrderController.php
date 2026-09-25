<?php

namespace App\Modules\Leads\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WebsiteOrder;
use App\Shared\Responses\Responser;
use Illuminate\Http\Request;

/**
 * Public intake for website orders (المسار الجديد بدون تسجيل دخول).
 *
 * - store():  public POST /api/v2/orders — the website's server route posts each
 *             submitted order here so it is persisted as the source of truth,
 *             independent of Telegram / email. No user session required.
 * - index():  dashboard listing (GET /api/admin/website-orders).
 *
 * Distinct from Contracts\OrderController, whose "orders" are contract requests.
 */
class WebsiteOrderController extends Controller
{
    use Responser;

    /**
     * Persist a website order. Public (no login) — a logged-out visitor submits it.
     * POST /api/v2/orders
     */
    public function store(Request $request)
    {
        // Optional shared-secret gate. When ORDER_INTAKE_TOKEN is configured on the
        // server, the caller (the website's /api/order route) must send a matching
        // bearer token. Left empty => the endpoint is public (per the intake spec).
        $expected = (string) config('services.order_intake.token', '');
        if ($expected !== '') {
            $provided = (string) $request->bearerToken();
            abort_unless(
                $provided !== '' && hash_equals($expected, $provided),
                401,
                trans('api.unauthorized')
            );
        }

        $validated = $request->validate([
            'orderNumber' => ['required', 'string', 'max:64'],
            'contractType' => ['nullable', 'string', 'max:50'],
            'whatsappNumber' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'max:50'],
            'createdAt' => ['nullable', 'string', 'max:40'],
            'sections' => ['nullable', 'array', 'max:50'],
            'sections.*.title' => ['nullable', 'string', 'max:191'],
            'sections.*.fields' => ['nullable', 'array', 'max:100'],
            'sections.*.fields.*.label' => ['nullable', 'string', 'max:191'],
            'sections.*.fields.*.value' => ['nullable', 'string', 'max:2000'],
        ]);

        // Idempotency: the website retries on failure. Same order_number => return the
        // stored one instead of creating a duplicate.
        $existing = WebsiteOrder::query()->where('order_number', $validated['orderNumber'])->first();
        if ($existing !== null) {
            return $this->apiResponse(['id' => $existing->id, 'ok' => true], trans('api.success'), 200);
        }

        $order = WebsiteOrder::query()->create([
            'order_number' => $validated['orderNumber'],
            'contract_type' => $validated['contractType'] ?? null,
            'whatsapp_number' => $validated['whatsappNumber'] ?? null,
            'sections' => $validated['sections'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'source' => $validated['source'] ?? 'web',
            'status' => WebsiteOrder::STATUS_NEW,
            'submitted_at' => now(),
        ]);

        return $this->apiResponse(['id' => $order->id, 'ok' => true], trans('api.success'), 201);
    }

    /**
     * Dashboard listing of website orders.
     * GET /api/admin/website-orders?status=new&search=...
     */
    public function index(Request $request)
    {
        $query = WebsiteOrder::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('whatsapp_number', 'like', "%{$term}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $orders = $query->orderByDesc('id')->paginate($perPage);

        return $this->apiResponse([
            'summary' => [
                'total' => WebsiteOrder::query()->count(),
                'new' => WebsiteOrder::query()->where('status', WebsiteOrder::STATUS_NEW)->count(),
            ],
            'items' => $orders->items(),
            'pagination' => $this->paginate($orders),
        ], trans('api.success'));
    }
}
