<?php

namespace Tests\Unit\Contracts;

use App\Models\Contract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractMassAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('contract_type')->nullable();
            $table->boolean('accept_retrun_contract')->nullable();
            $table->unsignedBigInteger('accept_retrun_contract_employee_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contracts');

        parent::tearDown();
    }

    public function test_sensitive_columns_are_not_fillable(): void
    {
        $contract = new Contract();
        $contract->fill([
            'id' => 99,
            'uuid' => '111111',
            'accept_retrun_contract' => true,
            'accept_retrun_contract_employee_id' => 4,
            'contract_type' => 'housing',
        ]);

        $this->assertNull($contract->id);
        $this->assertNull($contract->uuid);
        $this->assertNotTrue((bool) $contract->accept_retrun_contract);
        $this->assertNull($contract->accept_retrun_contract_employee_id);
        $this->assertSame('housing', $contract->contract_type);
    }
}
