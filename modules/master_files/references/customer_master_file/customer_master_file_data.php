<?php
// entries/customer_master_file/customer_master_file_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/general/number_series.php';

header('Content-Type: application/json');

function saveCustomer(array $data): array {
    $name = trim($data['customer_name'] ?? '');
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Customer name is required.']);
        exit;
    }

    $ref = consumeNextReference('customer_master_file');

    $customer = supabase_post(SB_API . 'm_customers', [
        'ref'                 => $ref,
        'code'                => trim($data['code'] ?? '') ?: null,
        'customer_name'       => $name,
        'customer_type_ref'   => !empty($data['customer_type_ref']) ? $data['customer_type_ref'] : null,
        'contact_person'      => trim($data['contact_person'] ?? '') ?: null,
        'phone'               => trim($data['phone']   ?? '') ?: null,
        'mobile'              => trim($data['mobile']  ?? '') ?: null,
        'email'               => trim($data['email']   ?? '') ?: null,
        'address'             => trim($data['address'] ?? '') ?: null,
        'city'                => trim($data['city']    ?? '') ?: null,
        'country'             => trim($data['country'] ?? '') ?: null,
        'tax_number'          => trim($data['tax_number'] ?? '') ?: null,
        'credit_limit'        => $data['credit_limit'] ?? 0,
        'payment_terms_days'  => $data['payment_terms_days'] ?? 0,
        'remarks'             => $data['remarks'] ?? '',
        'record_status'       => $data['record_status'] ?? 'active',
        'created_by' => current_user()['ref'] ?? null,
        'updated_by' => current_user()['ref'] ?? null,
    ]);

    return $customer;
}

function updateCustomer(array $data): array {
    $ref = trim($data['ref'] ?? '');
    if ($ref === '' || !recordExists('m_customers', $ref)) {
        return ['error' => 'Record not found.'];
    }

    $name = trim($data['customer_name'] ?? '');
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Customer name is required.']);
        exit;
    }

    return supabase_patch(SB_API . 'm_customers?ref=eq.' . urlencode($ref), [
        'code'                => trim($data['code'] ?? '') ?: null,
        'customer_name'       => $name,
        'customer_type_ref'   => !empty($data['customer_type_ref']) ? $data['customer_type_ref'] : null,
        'contact_person'      => trim($data['contact_person'] ?? '') ?: null,
        'phone'               => trim($data['phone']   ?? '') ?: null,
        'mobile'              => trim($data['mobile']  ?? '') ?: null,
        'email'               => trim($data['email']   ?? '') ?: null,
        'address'             => trim($data['address'] ?? '') ?: null,
        'city'                => trim($data['city']    ?? '') ?: null,
        'country'             => trim($data['country'] ?? '') ?: null,
        'tax_number'          => trim($data['tax_number'] ?? '') ?: null,
        'credit_limit'        => $data['credit_limit'] ?? 0,
        'payment_terms_days'  => $data['payment_terms_days'] ?? 0,
        'remarks'             => $data['remarks'] ?? '',
        'record_status'       => $data['record_status'] ?? 'active',
        'updated_by' => current_user()['ref'] ?? null,
    ]);
}

function listCustomers(): array {
    return supabase_get(SB_API . 'm_customers?select=ref,code,customer_name,customer_type_ref,contact_person,phone,mobile,email,address,city,country,tax_number,credit_limit,payment_terms_days,remarks,record_status,m_customer_types(ref,name)&order=id.asc');
}

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    echo json_encode(listCustomers());
} elseif ($action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(saveCustomer($body));
} elseif ($action === 'update') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(updateCustomer($body));
} elseif ($action === 'exists') {
    $ref = $_GET['ref'] ?? '';
    echo json_encode(['exists' => $ref !== '' && recordExists('m_customers', $ref)]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
