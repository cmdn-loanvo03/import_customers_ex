<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerMetadataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('genders')->insert([
            ['name' => 'male'],
            ['name' => 'female'],
            ['name' => 'other'],
        ]);

        DB::table('customer_types')->insert([
            ['name' => 'internal'],
            ['name' => 'external'],
        ]);

        DB::table('customer_segments')->insert([
            ['name' => 'high_value'],
            ['name' => 'at_risk'],
            ['name' => 'normal'],
        ]);

    }
}
