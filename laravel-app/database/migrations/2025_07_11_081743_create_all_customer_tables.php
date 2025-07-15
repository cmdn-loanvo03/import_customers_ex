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
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255);
            $table->string('email')->unique();
            $table->string('phone', 15)->unique();
            $table->date('date_of_birth');
            $table->foreignId('gender_id')->constrained('genders');
            $table->foreignId('customer_type_id')->constrained('customer_types');
            $table->foreignId('segment_id')->nullable()->constrained('customer_segments');
            $table->string('national_id', 12)->unique();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('address_line', 255);
            $table->string('province', 100);
            $table->string('district', 100);
            $table->string('ward', 100);
            $table->timestamps();
        });

        Schema::create('customer_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->enum('status', ['success', 'failed']);
            $table->integer('total_rows');
            $table->integer('failed_rows');
            $table->text('message')->nullable();
            $table->timestamp('imported_at')->useCurrent();
        });

        Schema::create('customer_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_log_id')->constrained('customer_import_logs')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('raw_data');
            $table->text('failed_reason');
            $table->timestamp('created_at')->useCurrent();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('all_customer_tables');
    }
};
