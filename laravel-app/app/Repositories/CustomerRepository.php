<?php

namespace App\Repositories;

use App\Models\{Customer, CustomerAddress, CustomerSegment, CustomerType, Gender};

class CustomerRepository
{
    public function store(array $data, string $segment): Customer
    {
        $gender = Gender::where('name', $data['gender_name'])->first();
        $type = CustomerType::where('name', $data['customer_type_name'])->first();
        $segmentModel = CustomerSegment::where('name', $segment)->first();

        $customer = Customer::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'gender_id' => $gender->id,
            'customer_type_id' => $type->id,
            'segment_id' => $segmentModel->id,
            'national_id' => $data['national_id'],
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_line' => $data['address_line'],
            'province' => $data['province'],
            'district' => $data['district'],
            'ward' => $data['ward'],
        ]);

        return $customer;
    }
}
