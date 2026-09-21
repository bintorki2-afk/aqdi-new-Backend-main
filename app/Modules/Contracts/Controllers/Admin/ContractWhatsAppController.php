<?php

namespace App\Modules\Contracts\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\Responser;
use App\Models\ContractWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractWhatsAppController extends Controller
{
    use Responser;

    /**
     * Get all contract WhatsApp records
     */
    public function index(Request $request)
    {
        $query = ContractWhatsApp::with('contractPeriod');

        if ($request->has('is_complete')) {
            $query->where('is_complete', $request->boolean('is_complete'));
        }

        if ($request->has('mobile_number')) {
            $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        }

        $contracts = $query->latest()->paginate($request->get('per_page', 20));

        // Format response based on is_complete status
        $formattedData = $contracts->getCollection()->map(function ($contract) {
            if (!$contract->is_complete) {
                // Incomplete: return only mobile_number, notes, time, date
                return [
                    'id' => $contract->id,
                    'mobile_number' => $contract->mobile_number,
                    'notes' => $contract->notes,
                    'time' => $contract->time,
                    'date' => $contract->date,
                    'is_complete' => $contract->is_complete,
                ];
            }

            return $this->formatCompleteRecord($contract);
        });

        // Replace collection in pagination
        $contracts->setCollection($formattedData);

        return $this->apiResponse($contracts, trans('api.success'));
    }

    /**
     * Create complete contract WhatsApp
     */
    public function storeComplete(Request $request)
    {
        $validator = $this->validateCompleteContract($request);
        
        if ($validator->fails()) {
            return $this->apiResponse(null, $validator->errors()->first(), false, 422);
        }

        $data = $this->prepareCompleteData($request);
        $contract = ContractWhatsApp::with('contractPeriod')->create($data);

        return $this->apiResponse(
            $this->formatCompleteRecord($contract),
             ('تم انشاء عقد واتساب مكتمل بنجاح '),
            true,
            201
        );
    }

    /**
     * Create incomplete contract WhatsApp
     */
    public function storeIncomplete(Request $request)
    {
        $validator = $this->validateIncompleteContract($request);
        
        if ($validator->fails()) {
            return $this->apiResponse(null, $validator->errors()->first(), false, 422);
        }

        $data = $this->prepareIncompleteData($request);
        $contract = ContractWhatsApp::create($data);

        return $this->apiResponse($contract,  ('تم انشاء عقد واتساب غير مكتمل بنجاح '), true, 201);
    }

    /**
     * Validate complete contract data
     */
    private function validateCompleteContract(Request $request)
    {
        return Validator::make($request->all(), [
            'mobile_number' => 'required|string',
            'addition_date' => 'required|date',
            'contract_type' => 'required|in:commercial,residential',
            'without' => 'nullable|boolean',
            'derived_from_bank' => 'nullable|boolean',
            'waqf' => 'nullable|boolean',
            'paper_deed' => 'nullable|boolean',
            'paper_deed_2' => 'nullable|boolean',
            'is_documented' => 'required|boolean',
            'contract_duration' => 'nullable|exists:contract_periods,id',
            'amount_paid_by_client' => 'nullable|numeric|min:0',
            'rental_fees' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }

    /**
     * Validate incomplete contract data
     */
    private function validateIncompleteContract(Request $request)
    {
        return Validator::make($request->all(), [
            'mobile_number' => 'required|string',
            'notes' => 'nullable|string',
            'time' => 'nullable|date_format:H:i',
            'date' => 'nullable|date',
        ]);
    }

    /**
     * Prepare complete contract data
     */
    private function prepareCompleteData(Request $request): array
    {
        return [
            'mobile_number' => $request->mobile_number,
            'addition_date' => $request->addition_date ?? now(),
            'contract_type' => $request->contract_type,
            'without' => $request->boolean('without', false),
            'derived_from_bank' => $request->boolean('derived_from_bank', false),
            'waqf' => $request->boolean('waqf', false),
            'paper_deed' => $request->boolean('paper_deed', false),
            'paper_deed_2' => $request->boolean('paper_deed_2', false),
            'is_documented' => $request->is_documented,
            'contract_duration' => $request->contract_duration,
            'amount_paid_by_client' => $request->amount_paid_by_client,
            'rental_fees' => $request->rental_fees,
            'notes' => $request->notes,
            'is_complete' => true,
        ];
    }

    /**
     * Prepare incomplete contract data
     */
    private function prepareIncompleteData(Request $request): array
    {
        return [
            'mobile_number' => $request->mobile_number,
            'addition_date' => $request->date ? now()->setDateFrom($request->date) : now(),
            'notes' => $request->notes,
            'time' => $request->time,
            'date' => $request->date,
            'is_complete' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCompleteRecord(ContractWhatsApp $contract): array
    {
        $data = $contract->toArray();

        if ($contract->contract_type !== null && $contract->contract_type !== '') {
            $data['contract_type'] = $contract->contract_type_trans;
        }

        if ($contract->contract_duration !== null) {
            $data['contract_duration'] = $contract->contract_duration_name;
        }

        unset($data['contract_period']);

        return $data;
    }
}
