<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator;

class CustomerImportRowRequest
{
    public static function validate(array $row): array
    {
        $validator = Validator::make($row, [
            'full_name' => 'required|string',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|unique:customers,phone',
            'date_of_birth' => 'required|date',
            'gender_name' => 'required|exists:genders,name',
            'customer_type_name' => 'required|exists:customer_types,name',
            'total_purchase' => 'required|numeric',
            'national_id' => 'required|digits:12|unique:customers,national_id',
            'address_line' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string',
            'ward' => 'required|string',
        ]);

        return $validator->fails()
            ? ['status' => false, 'errors' => $validator->errors()->all()]
            : ['status' => true];
    }
}
