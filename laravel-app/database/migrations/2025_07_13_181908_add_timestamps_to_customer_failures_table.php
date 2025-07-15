<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimestampsToCustomerFailuresTable extends Migration
{
    public function up()
    {
        Schema::table('customer_failures', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_failures', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('customer_failures', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('customer_failures', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
}

