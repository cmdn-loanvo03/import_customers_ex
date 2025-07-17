<?php

namespace App\Repositories\TempCustomer;

use Illuminate\Support\Facades\DB;

class TempCustomerRepository
{
    protected string $table;

    public function __construct(string $suffix)
    {
        $this->table = 'temp_customers_' . $suffix;
    }

    public function createTable(): void
    {
        DB::statement("
            CREATE TEMPORARY TABLE {$this->table} (
                full_name VARCHAR(255),
                email VARCHAR(255) COLLATE utf8mb4_unicode_ci,
                phone VARCHAR(20),
                date_of_birth DATE,
                gender_id TINYINT,
                customer_type_id TINYINT,
                segment_id TINYINT,
                national_id VARCHAR(20),
                address_line VARCHAR(255),
                province VARCHAR(100),
                district VARCHAR(100),
                ward VARCHAR(100)
            )
        ");
    }

    public function insertBatch(array $rows): void
    {
        DB::table($this->table)->insert($rows);
    }

    public function transfer(): void
    {
        DB::transaction(function () {
            DB::insert("
                INSERT INTO customers (full_name, email, phone, date_of_birth, gender_id, customer_type_id, segment_id, national_id, created_at, updated_at)
                SELECT full_name, email, phone, date_of_birth, gender_id, customer_type_id, segment_id, national_id, NOW(), NOW()
                FROM {$this->table}
            ");

            DB::insert("
                INSERT INTO customer_addresses (customer_id, address_line, province, district, ward, created_at, updated_at)
                SELECT c.id, t.address_line, t.province, t.district, t.ward, NOW(), NOW()
                FROM {$this->table} t
                JOIN customers c ON c.email = t.email
            ");
        });
    }

    public function dropTable(): void
    {
        DB::statement("DROP TEMPORARY TABLE IF EXISTS {$this->table}");
    }
}
