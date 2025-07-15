<?php

$filename = 'test_customers_200.csv';
$handle = fopen($filename, 'w');

// Header (phải khớp với code import)
$headers = [
    'full_name', 'email', 'phone', 'date_of_birth', 'gender_name',
    'customer_type_name', 'total_purchase', 'national_id',
    'address_line', 'province', 'district', 'ward'
];
fputcsv($handle, $headers);

// Sample values
$genders = ['Male', 'Female'];
$customerTypes = ['external', 'internal'];
$provinces = ['Hà Nội', 'TP.HCM', 'Đà Nẵng'];
$districts = ['Quận 1', 'Quận 2', 'Quận 3'];
$wards = ['Phường A', 'Phường B', 'Phường C'];

for ($i = 1; $i <= 200; $i++) {
    $fullName = "Test User $i";

    // ✅ Tạo lỗi ở dòng chẵn
    if ($i % 10 === 0) {
        $email = "invalid-email-format"; // lỗi email
        $phone = "";                     // thiếu phone
        $dob = "not-a-date";            // lỗi ngày
        $gender = "Unknown";            // không tồn tại trong genders
        $type = "invalid_type";         // không tồn tại trong DB
        $purchase = "abc";              // lỗi numeric
        $nationalId = str_repeat("1", 10); // thiếu số
        $addressLine = "";
        $province = "";
        $district = "";
        $ward = "";
    } else {
        $email = "test$i@example.com";
        $phone = "09123" . str_pad($i, 5, '0', STR_PAD_LEFT);
        $dob = date('Y-m-d', strtotime('-' . rand(18, 60) . ' years'));
        $gender = $genders[array_rand($genders)];
        $type = $customerTypes[array_rand($customerTypes)];
        $purchase = rand(10_000, 150_000_000);
        $nationalId = str_pad((string)$i, 12, '0', STR_PAD_LEFT);
        $addressLine = "Số " . rand(1, 100) . " Đường ABC";
        $province = $provinces[array_rand($provinces)];
        $district = $districts[array_rand($districts)];
        $ward = $wards[array_rand($wards)];
    }

    $row = [
        $fullName, $email, $phone, $dob, $gender,
        $type, $purchase, $nationalId,
        $addressLine, $province, $district, $ward
    ];

    fputcsv($handle, $row);
}

fclose($handle);
echo "✅ Tạo file test: $filename thành công.\n";
