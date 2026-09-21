<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New contract schema (contracts-new).
 *
 * Built from the Website + Dashboard frontend field-usage audit.
 * Does not alter or replace the existing `contracts` table.
 *
 * Lookup catalogs reused as-is: users, employees, contract_statuses,
 * draft_contract_statuses, contract_periods, payment_types, tenant_roles,
 * regions, cities, unit_types, unit_usages, real_estates, real_units, coupons.
 *
 * Display-only / computed aliases are NOT stored (API can derive them):
 * contract_type_trans, contract_type_key, instrument_type_trans,
 * instrument_type_key, status_label/color from relation, relation_labels,
 * lat/lng (= latitude/longitude), journey[] (from status histories),
 * waiting_minutes / received_since / is_received (from received row),
 * units_count (= units count), tenant_name aliases, id_number_of_* alias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts_new', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id');
            $table->enum('contract_type', ['housing', 'commercial']);
            $table->enum('app_or_web', ['app', 'web'])->default('web');
            $table->unsignedTinyInteger('step')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->boolean('is_real')->default(false);
            $table->unsignedBigInteger('real_id')->nullable();
            $table->unsignedBigInteger('real_units_id')->nullable();
            $table->string('name_real_estate')->nullable();
            $table->unsignedBigInteger('contract_status_id')->nullable();
            $table->unsignedBigInteger('draft_contract_status_id')->nullable();
            $table->string('draft_contract_number', 32)->nullable();
            $table->string('time_to_documentation_contract')->nullable();
            $table->timestamps();

            $table->index(
                ['user_id', 'is_completed', 'is_delete', 'contract_type'],
                'contracts_new_user_incomplete_index'
            );
            $table->index(['is_draft', 'draft_contract_status_id'], 'contracts_new_draft_status_index');
            $table->index(['contract_type', 'contract_status_id'], 'contracts_new_type_status_index');
            $table->index('created_at');
            $table->index('is_completed');

            $table->foreign('user_id', 'contracts_new_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('real_id', 'contracts_new_real_id_foreign')
                ->references('id')->on('real_estates')->nullOnDelete();
            $table->foreign('real_units_id', 'contracts_new_real_units_id_foreign')
                ->references('id')->on('real_units')->nullOnDelete();
            $table->foreign('contract_status_id', 'contracts_new_contract_status_id_foreign')
                ->references('id')->on('contract_statuses')->nullOnDelete();
            $table->foreign('draft_contract_status_id', 'contracts_new_draft_status_id_foreign')
                ->references('id')->on('draft_contract_statuses')->nullOnDelete();
        });

        Schema::create('contract_new_owners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('name_owner')->nullable();
            $table->string('property_owner_id_num')->nullable();
            $table->string('property_owner_mobile')->nullable();
            $table->text('property_owner_dob')->nullable();
            $table->enum('type_dob_property_owner', ['hijri', 'gregorian'])->default('hijri');
            $table->string('property_owner_iban')->nullable();
            $table->boolean('add_legal_agent_of_owner')->default(false);
            $table->string('id_num_of_property_owner_agent')->nullable();
            $table->text('dob_of_property_owner_agent')->nullable();
            $table->enum('type_dob_property_owner_agent', ['hijri', 'gregorian'])->nullable();
            $table->string('mobile_of_property_owner_agent')->nullable();
            $table->text('copy_of_the_authorization_or_agency')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->index('property_owner_id_num');
            $table->foreign('contract_id', 'contract_new_owners_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_tenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->enum('tenant_entity', ['person', 'institution'])->nullable();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_id_num')->nullable();
            $table->string('tenant_mobile')->nullable();
            $table->text('tenant_dob')->nullable();
            $table->enum('type_tenant_dob', ['hijri', 'gregorian'])->nullable();
            $table->string('tenant_entity_unified_registry_number')->nullable();
            $table->enum('authorization_type', [
                'owner_and_representative_of_record',
                'agent_for_the_tenant',
                'agent_or_authorized_by_registry_owner',
            ])->nullable();
            $table->string('id_num_of_property_tenant_agent')->nullable();
            $table->string('mobile_of_property_tenant_agent')->nullable();
            $table->text('dob_of_property_tenant_agent')->nullable();
            $table->enum('type_dob_tenant_agent', ['hijri', 'gregorian'])->nullable();
            $table->text('copy_of_the_authorization_or_agency')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->index('tenant_id_num');
            $table->foreign('contract_id', 'contract_new_tenants_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->enum('address_method', ['photo', 'link', 'manual'])->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('address_url')->nullable();
            $table->text('image_address')->nullable();
            $table->unsignedBigInteger('property_place_id')->nullable();
            $table->unsignedBigInteger('property_city_id')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('street')->nullable();
            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('extra_figure')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->foreign('contract_id', 'contract_new_addresses_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('property_place_id', 'contract_new_addresses_place_id_foreign')
                ->references('id')->on('regions')->nullOnDelete();
            $table->foreign('property_city_id', 'contract_new_addresses_city_id_foreign')
                ->references('id')->on('cities')->nullOnDelete();
        });

        Schema::create('contract_new_deeds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('instrument_type', 80)->nullable();
            $table->string('deed_number')->nullable();
            $table->string('deed_addition_method', 20)->nullable();
            $table->text('image_instrument')->nullable();
            $table->text('image_instrument_from_the_front')->nullable();
            $table->text('image_instrument_from_the_back')->nullable();
            $table->text('Image_inheritance_certificate')->nullable();
            $table->text('copy_power_of_attorney_from_heirs_to_agent')->nullable();
            $table->text('copy_of_the_endowment_registration_certificate')->nullable();
            $table->text('copy_of_the_trusteeship_deed')->nullable();
            $table->boolean('is_multiple_trusteeship_deed_copy')->default(false);
            $table->text('copy_of_guardians_power_of_attorney_for_agent')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->index('instrument_type');
            $table->index('deed_number');
            $table->foreign('contract_id', 'contract_new_deeds_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('real_unit_id')->nullable();
            $table->unsignedBigInteger('unit_type_id')->nullable();
            $table->unsignedBigInteger('unit_usage_id')->nullable();
            $table->string('unit_number')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('unit_area')->nullable();
            $table->string('tootal_rooms')->nullable();
            $table->string('number_of_rooms')->nullable();
            $table->string('The_number_of_halls')->nullable();
            $table->string('The_number_of_kitchens')->nullable();
            $table->string('The_number_of_toilets')->nullable();
            $table->string('The_number_of_the_toilet')->nullable();
            $table->integer('window_ac')->nullable();
            $table->integer('split_ac')->nullable();
            $table->string('number_of_unit_air_conditioners')->nullable();
            $table->string('Number_parking_spaces')->nullable();
            $table->string('Gasmeter')->nullable();
            $table->boolean('kitchen_tank')->default(false);
            $table->boolean('furnished')->default(false);
            $table->string('type_furnished')->nullable();
            $table->boolean('electricity_meter')->default(false);
            $table->string('electricity_meter_number')->nullable();
            $table->enum('electricity_meter_ownership', ['owner', 'tenant'])->nullable();
            $table->boolean('water_meter')->default(false);
            $table->string('water_meter_number')->nullable();
            $table->enum('water_meter_ownership', ['owner', 'tenant'])->nullable();
            $table->json('Services')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index(['contract_id', 'real_unit_id'], 'contract_new_units_contract_real_index');
            $table->foreign('contract_id', 'contract_new_units_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('real_unit_id', 'contract_new_units_real_unit_id_foreign')
                ->references('id')->on('real_units')->nullOnDelete();
            $table->foreign('unit_type_id', 'contract_new_units_unit_type_id_foreign')
                ->references('id')->on('unit_types')->nullOnDelete();
            $table->foreign('unit_usage_id', 'contract_new_units_unit_usage_id_foreign')
                ->references('id')->on('unit_usages')->nullOnDelete();
        });

        Schema::create('contract_new_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('contract_period_id')->nullable();
            $table->string('duration_preset', 32)->nullable();
            $table->unsignedTinyInteger('duration_years')->nullable();
            $table->unsignedTinyInteger('duration_months')->nullable();
            $table->unsignedSmallInteger('total_months')->nullable();
            $table->decimal('annual_rent_amount_for_the_unit', 12, 2)->nullable();
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->string('contract_starting_date', 20)->nullable();
            $table->enum('type_contract_starting_date', ['hijri', 'gregorian'])->default('hijri');
            $table->boolean('conditions')->default(false);
            $table->text('other_conditions')->nullable();
            $table->boolean('additional_terms')->default(false);
            $table->text('text_additional_terms')->nullable();
            $table->boolean('tenant_roles')->default(false);
            $table->unsignedBigInteger('tenant_role_id')->nullable();
            $table->longText('notes')->nullable();
            $table->text('notes_edits')->nullable();
            $table->decimal('daily_fine', 12, 2)->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->foreign('contract_id', 'contract_new_terms_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('contract_period_id', 'contract_new_terms_period_id_foreign')
                ->references('id')->on('contract_periods')->nullOnDelete();
            $table->foreign('payment_type_id', 'contract_new_terms_payment_type_id_foreign')
                ->references('id')->on('payment_types')->nullOnDelete();
            $table->foreign('tenant_role_id', 'contract_new_terms_tenant_role_id_foreign')
                ->references('id')->on('tenant_roles')->nullOnDelete();
        });

        Schema::create('contract_new_other_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('text');
            $table->timestamps();

            $table->index('contract_id');
            $table->foreign('contract_id', 'contract_new_other_conditions_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_tenant_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('tenant_role_id');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'tenant_role_id'], 'contract_new_tenant_roles_unique');
            $table->foreign('contract_id', 'contract_new_tenant_roles_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('tenant_role_id', 'contract_new_tenant_roles_role_id_foreign')
                ->references('id')->on('tenant_roles')->cascadeOnDelete();
        });

        Schema::create('contract_new_financials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->decimal('contract_period_price', 12, 2)->nullable();
            $table->decimal('application_fees', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->decimal('electricity_meter_fee', 12, 2)->nullable();
            $table->decimal('water_meter_fee', 12, 2)->nullable();
            $table->decimal('services_total', 12, 2)->nullable();
            $table->decimal('meter_fees_total', 12, 2)->nullable();
            $table->decimal('doc_fee', 12, 2)->nullable();
            $table->json('doc_fee_lines')->nullable();
            $table->text('doc_fee_text')->nullable();
            $table->boolean('has_extra_months')->default(false);
            $table->decimal('total_price', 12, 2)->nullable();
            $table->decimal('payable_amount', 12, 2)->nullable();
            $table->decimal('cart_amount', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('amount_payment', 12, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('payment_status', 20)->nullable();
            $table->boolean('payment_confirmed')->default(false);
            $table->timestamps();

            $table->unique('contract_id');
            $table->index('is_paid');
            $table->foreign('contract_id', 'contract_new_financials_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_financial_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('name')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('service_name')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('service_price', 12, 2)->nullable();
            $table->enum('contract_type', ['housing', 'commercial'])->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('contract_id');
            $table->foreign('contract_id', 'contract_new_fin_services_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('service_id', 'contract_new_fin_services_service_id_foreign')
                ->references('id')->on('services_pricings')->nullOnDelete();
        });

        Schema::create('contract_new_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('code_coupon')->nullable();
            $table->enum('type_coupon', ['ratio', 'value'])->nullable();
            $table->decimal('value_coupon', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('total_price_before_coupon', 12, 2)->nullable();
            $table->decimal('total_price_after_coupon', 12, 2)->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->foreign('contract_id', 'contract_new_coupons_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('coupon_id', 'contract_new_coupons_coupon_id_foreign')
                ->references('id')->on('coupons')->nullOnDelete();
        });

        Schema::create('contract_new_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('contract_uuid')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('tran_currency', 10)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->text('payment_url')->nullable();
            $table->text('payment_success_url')->nullable();
            $table->text('payment_error_url')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('contract_id');
            $table->index('contract_uuid');
            $table->index('status');
            $table->foreign('contract_id', 'contract_new_payments_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('user_id', 'contract_new_payments_user_id_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('contract_new_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('invoice_number')->nullable();
            $table->string('order_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('platform_subtitle')->nullable();
            $table->string('title')->nullable();
            $table->string('datetime_label')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('contract_type_label')->nullable();
            $table->string('total_due_label')->nullable();
            $table->string('total_amount_label')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('status_label')->nullable();
            $table->string('status_color')->nullable();
            $table->string('print_label')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->timestamps();

            $table->index('contract_id');
            $table->index('invoice_number');
            $table->foreign('contract_id', 'contract_new_invoices_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedSmallInteger('index')->default(0);
            $table->string('description')->nullable();
            $table->string('name')->nullable();
            $table->string('amount_label')->nullable();
            $table->string('price_label')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->foreign('invoice_id', 'contract_new_invoice_items_invoice_id_foreign')
                ->references('id')->on('contract_new_invoices')->cascadeOnDelete();
        });

        Schema::create('contract_new_employee_paid', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('contract_uuid')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('customer_mobile', 32)->nullable();
            $table->enum('contract_type', ['housing', 'commercial'])->nullable();
            $table->unsignedBigInteger('contract_period_id')->nullable();
            $table->string('draft_contract_number', 32)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('cart_amount', 12, 2)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->boolean('already_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('contract_uuid');
            $table->index(['employee_id', 'is_paid'], 'contract_new_employee_paid_emp_paid_index');
            $table->foreign('contract_id', 'contract_new_employee_paid_contract_id_foreign')
                ->references('id')->on('contracts_new')->nullOnDelete();
            $table->foreign('employee_id', 'contract_new_employee_paid_employee_id_foreign')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('contract_period_id', 'contract_new_employee_paid_period_id_foreign')
                ->references('id')->on('contract_periods')->nullOnDelete();
        });

        Schema::create('contract_new_received', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('employee_id');
            $table->enum('status', ['pending', 'finish'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->index('employee_id');
            $table->index('received_at');
            $table->foreign('contract_id', 'contract_new_received_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('employee_id', 'contract_new_received_employee_id_foreign')
                ->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('contract_new_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('key')->nullable();
            $table->enum('status_type', ['contract', 'draft'])->default('contract');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('status')->nullable();
            $table->string('status_label')->nullable();
            $table->string('status_color')->nullable();
            $table->string('status_color_text')->nullable();
            $table->text('description')->nullable();
            $table->text('status_description')->nullable();
            $table->text('client_explanation')->nullable();
            $table->text('status_client_explanation')->nullable();
            $table->enum('state', ['completed', 'current', 'pending'])->nullable();
            $table->string('source', 40)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'id'], 'contract_new_status_histories_contract_id_index');
            $table->index(['contract_id', 'status_type'], 'contract_new_status_histories_type_index');
            $table->foreign('contract_id', 'contract_new_status_histories_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_ejar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('ejar_contract_number')->nullable();
            $table->text('ejar_status_notes')->nullable();
            $table->string('ejar_contract_draft_number')->nullable();
            $table->string('draft_contact_number_mode', 20)->nullable();
            $table->string('draft_contact_number', 30)->nullable();
            $table->string('status_attachment')->nullable();
            $table->timestamps();

            $table->unique('contract_id');
            $table->foreign('contract_id', 'contract_new_ejar_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
        });

        Schema::create('contract_new_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('accept_return_employee_id')->nullable();
            $table->boolean('has_draft_contract')->default(false);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('admin_confirmed')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->boolean('accept_return_contract')->default(false);
            $table->timestamps();

            $table->index('contract_id');
            $table->foreign('contract_id', 'contract_new_refunds_contract_id_foreign')
                ->references('id')->on('contracts_new')->cascadeOnDelete();
            $table->foreign('user_id', 'contract_new_refunds_user_id_foreign')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_id', 'contract_new_refunds_employee_id_foreign')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('accept_return_employee_id', 'contract_new_refunds_accept_emp_foreign')
                ->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_new_refunds');
        Schema::dropIfExists('contract_new_ejar');
        Schema::dropIfExists('contract_new_status_histories');
        Schema::dropIfExists('contract_new_received');
        Schema::dropIfExists('contract_new_employee_paid');
        Schema::dropIfExists('contract_new_invoice_items');
        Schema::dropIfExists('contract_new_invoices');
        Schema::dropIfExists('contract_new_payments');
        Schema::dropIfExists('contract_new_coupons');
        Schema::dropIfExists('contract_new_financial_services');
        Schema::dropIfExists('contract_new_financials');
        Schema::dropIfExists('contract_new_tenant_roles');
        Schema::dropIfExists('contract_new_other_conditions');
        Schema::dropIfExists('contract_new_terms');
        Schema::dropIfExists('contract_new_units');
        Schema::dropIfExists('contract_new_deeds');
        Schema::dropIfExists('contract_new_addresses');
        Schema::dropIfExists('contract_new_tenants');
        Schema::dropIfExists('contract_new_owners');
        Schema::dropIfExists('contracts_new');
    }
};
