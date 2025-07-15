<?php

$filename = '1_million_customers.csv';
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

// Generate 1,000,000 records
for ($i = 1; $i <= 1_000_000; $i++) {
    $fullName = "Customer $i";
    $email = "customer$i@example.com";
    $phone = "090000" . str_pad($i % 1000000, 6, '0', STR_PAD_LEFT);
    $dob = date('Y-m-d', strtotime('-' . rand(18, 60) . ' years'));
    $gender = $genders[array_rand($genders)];
    $type = $customerTypes[array_rand($customerTypes)];
    $purchase = rand(1_000_000, 200_000_000);
    $nationalId = str_pad((string)$i, 12, '0', STR_PAD_LEFT);
    $addressLine = "Số " . rand(1, 999) . " Đường ABC";
    $province = $provinces[array_rand($provinces)];
    $district = $districts[array_rand($districts)];
    $ward = $wards[array_rand($wards)];

    $row = [
        $fullName, $email, $phone, $dob, $gender,
        $type, $purchase, $nationalId,
        $addressLine, $province, $district, $ward
    ];

    fputcsv($handle, $row);

    // Progress indicator (optional)
    if ($i % 100000 === 0) {
        echo "Generated $i rows...\n";
    }
}

fclose($handle);
echo "✅ Done. File saved as $filename\n";
