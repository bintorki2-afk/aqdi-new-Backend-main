<?php

namespace App\Modules\Leads\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\Contract;
use App\Models\Lead;
use Illuminate\Http\Request;

/**
 * CR2 "العملاء المحتملون" (potential customers).
 *
 * - store():  called when a visitor reaches the payment screen but has not paid;
 *             captures/updates a lead (name, phone, contract ref, amount).
 * - index():  dashboard listing of leads.
 */
class LeadController extends Controller
{
    use Responser;

    /**
     * Capture (or refresh) a potential-customer lead.
     * POST /api/v2/leads
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'regex:/^05\d{8}$/'],
            // A lead must reference one of the caller's contracts (the website always sends both);
            // otherwise any logged-in user could flood the dashboard with arbitrary name/phone rows.
            'contract_uuid' => ['required_without:contract_id', 'nullable', 'string', 'max:191'],
            'contract_id' => ['required_without:contract_uuid', 'nullable', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        // Enrich from the contract when a reference is given. The contract MUST belong to the
        // caller: otherwise any logged-in user could post another user's uuid and read back the
        // owner's name / phone (IDOR found in QA). Unknown / foreign references → 404.
        $contract = null;
        $userId = (int) auth()->id();
        if (! empty($validated['contract_uuid'])) {
            $contract = Contract::query()->ownedBy($userId)->where('uuid', $validated['contract_uuid'])->first();
            abort_if($contract === null, 404, trans('api.not_found'));
        } elseif (! empty($validated['contract_id'])) {
            $contract = Contract::query()->ownedBy($userId)->find($validated['contract_id']);
            abort_if($contract === null, 404, trans('api.not_found'));
        }

        if ($contract) {
            $validated['contract_uuid'] ??= (string) $contract->uuid;
            $validated['contract_id'] ??= $contract->id;
            $validated['contract_type'] ??= $contract->contract_type;
            $validated['name'] = $validated['name'] ?? $contract->name_owner ?? $contract->user?->name;
            $validated['phone'] = $validated['phone'] ?? $contract->user?->mobile;
            if (! isset($validated['amount']) || $validated['amount'] === null) {
                $validated['amount'] = \App\Support\ContractPricing::total($contract);
            }

            // Do not downgrade an already-paid contract to a potential lead.
            if ((bool) $contract->is_completed) {
                $validated['status'] = Lead::STATUS_PAID;
                $validated['converted_at'] = now();
            }
        }

        $validated['source'] = $validated['source'] ?? ($request->input('app_or_web', 'web'));
        $validated['status'] = $validated['status'] ?? Lead::STATUS_POTENTIAL;

        // One lead per contract reference: update it in place, otherwise create a new one.
        $lead = null;
        if (! empty($validated['contract_uuid'])) {
            $lead = Lead::query()->where('contract_uuid', $validated['contract_uuid'])->latest('id')->first();
        }

        if ($lead) {
            // Never revert a paid lead back to potential.
            if ($lead->status === Lead::STATUS_PAID) {
                unset($validated['status'], $validated['converted_at']);
            }
            $lead->fill($validated)->save();
        } else {
            $lead = Lead::query()->create($validated);
        }

        return $this->apiResponse($lead->fresh(), trans('api.success'), 201);
    }

    /**
     * Dashboard listing of leads.
     * GET /api/admin/leads?status=potential
     */
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('contract_uuid', 'like', "%{$term}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $leads = $query->orderByDesc('id')->paginate($perPage);

        return $this->apiResponse([
            'summary' => [
                'total' => Lead::query()->count(),
                'potential' => Lead::query()->where('status', Lead::STATUS_POTENTIAL)->count(),
                'paid' => Lead::query()->where('status', Lead::STATUS_PAID)->count(),
            ],
            'items' => $leads->items(),
            'pagination' => $this->paginate($leads),
        ], trans('api.success'));
    }
}
