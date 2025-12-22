<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Invoice Identification (MANDATORY)
            $table->string('invoice_number')->unique()->comment('Version 2.0: Unique sequential number');
            $table->enum('invoice_type', ['01', '02', '03', '04', '11', '12', '13', '14'])
                ->default('01')
                ->comment('01-Invoice, 02-Credit Note, 03-Debit Note, 04-Refund Note, 11- Self-billed Invoice, 12- Self-billed Credit Note, 13- Self-billed Debit Note, 14- Self-billed Refund Note');
            $table->dateTime('invoice_date_time')->comment('Version 2.0: Date and time');
            $table->string('original_einvoice_reference')->nullable()->comment('For credit/debit/refund notes');
            
            // Billing Information
            $table->enum('frequency_of_billing', ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'])
                ->nullable()
                ->comment('01-Daily, 02-Weekly, 03-Biweekly, 04-Monthly, 05-Bimonthly, 06-Quaterly, 07-Half-yearly, 08-Yearly, 09-Other, 10-Not Applicable');
            $table->date('billing_period_start_date')->nullable();
            $table->date('billing_period_end_date')->nullable();
            
            // ==================== SUPPLIER DETAILS (MANDATORY) ====================
            $table->string('supplier_name');
            $table->string('supplier_tin')->comment('Tax Identification Number');
            $table->string('supplier_registration_number')->comment('BRN/ID/Passport');
            $table->string('supplier_sst_registration_number')->nullable();
            $table->string('supplier_tourism_tax_number')->nullable();
            $table->string('supplier_email');
            $table->string('supplier_msic_code', 5)->comment('5-digit Malaysia SIC - MANDATORY');
            $table->string('supplier_business_activity_description');
            $table->text('supplier_address_line1');
            $table->text('supplier_address_line2')->nullable();
            $table->text('supplier_address_line3')->nullable();
            $table->string('supplier_postal_code', 20)->nullable()->comment('Optional if foreign');
            $table->string('supplier_city', 100)->nullable()->comment('Optional if foreign');
            $table->string('supplier_state', 100);
            $table->string('supplier_country', 2)->default('MY')->comment('ISO 3166-1 alpha-2');
            $table->string('supplier_contact_number', 20);
            
            // ==================== BUYER DETAILS (MANDATORY) ====================
            $table->string('buyer_name');
            $table->string('buyer_tin')->default('EI00000000010')->comment('EI00000000010 if not available');
            $table->string('buyer_registration_number')->nullable()->comment('BRN/ID/Passport');
            $table->string('buyer_sst_registration_number')->nullable();
            $table->string('buyer_email')->nullable();
            $table->text('buyer_address_line1');
            $table->text('buyer_address_line2')->nullable();
            $table->text('buyer_address_line3')->nullable();
            $table->string('buyer_postal_code', 20)->nullable()->comment('Optional if foreign');
            $table->string('buyer_city', 100)->nullable()->comment('Optional if foreign');
            $table->string('buyer_state', 100);
            $table->string('buyer_country', 2)->default('MY')->comment('ISO 3166-1 alpha-2');
            $table->string('buyer_contact_number', 20)->nullable();
            
            // ==================== PAYMENT INFORMATION (OPTIONAL) ====================
            $table->enum('payment_mode', ['01', '02', '03', '04', '05', '06', '07'])
                ->nullable()
                ->comment('01-Cash, 02-Cheque, 03-BankTransfer, 04-CreditCard, 05-DebitCard, 06-EWallet, 07-Others');
            $table->string('payment_terms')->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_reference_number')->nullable();
            $table->string('bank_account_number')->nullable();
            
            // ==================== SHIPPING INFORMATION (ANNEXURE) ====================
            $table->string('shipping_recipient_name')->nullable()->comment('If different from buyer');
            $table->string('shipping_recipient_tin')->nullable();
            $table->string('shipping_recipient_registration')->nullable();
            $table->text('shipping_address_line1')->nullable();
            $table->text('shipping_address_line2')->nullable();
            $table->text('shipping_address_line3')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_state', 100)->nullable();
            $table->string('shipping_country', 2)->nullable()->comment('ISO 3166-1 alpha-2');
            
            // ==================== OTHER REFERENCES ====================
            $table->string('bill_reference_number')->nullable()->comment('For utilities/telecoms');
            
            // ==================== CUSTOMS INFORMATION (FOR IMPORTS/EXPORTS) ====================
            $table->string('customs_form_reference')->nullable()->comment('Form 1, 9, etc.');
            $table->string('incoterms', 10)->nullable()->comment('If applicable');
            $table->string('free_trade_agreement_info')->nullable();
            $table->string('authorisation_number_for_certified_exporter')->nullable();
            
            // ==================== TOTALS AND AMOUNTS (MANDATORY) ====================
            $table->string('currency_code', 3)->default('MYR')->comment('ISO 4217');
            $table->decimal('currency_exchange_rate', 12, 6)->nullable()->comment('If not MYR');
            $table->decimal('total_excluding_tax', 15, 2);
            $table->decimal('total_including_tax', 15, 2);
            $table->decimal('total_payable_amount', 15, 2);
            $table->decimal('total_discount_value', 15, 2)->default(0)->comment('Invoice level');
            $table->decimal('total_fee_charge_amount', 15, 2)->default(0)->comment('Invoice level');
            $table->decimal('total_tax_amount', 15, 2)->default(0);
            
            // ==================== IRBM VALIDATION FIELDS ====================
            $table->enum('status', [
                'draft', 
                'validated', 
                'submitted', 
                'approved', 
                'rejected', 
                'cancelled'
            ])->default('draft');
            $table->string('myinvois_uid')->nullable()->unique()->comment('Unique Identifier from IRBM');
            $table->text('qr_code_data')->nullable()->comment('QR code for validation');
            $table->string('irbm_unique_identifier')->nullable()->comment('After validation');
            $table->dateTime('validation_date_time')->nullable();
            $table->text('digital_signature')->nullable()->comment('For API submissions');
            
            // ==================== SYSTEM FIELDS ====================
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // ==================== INDEXES ====================
            $table->index('user_id');
            $table->index('invoice_number');
            $table->index('invoice_type');
            $table->index('status');
            $table->index('invoice_date_time');
            $table->index('supplier_tin');
            $table->index('buyer_tin');
            $table->index('myinvois_uid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
