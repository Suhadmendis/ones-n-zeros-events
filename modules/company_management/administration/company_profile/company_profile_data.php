<?php
// company_management/company_profile/company_profile_data.php
// Stores extended fields in sys_company_settings (no schema migration required).
// Base fields (name, email, phone, address) are also kept in sync on sys_company_info
// for backward compatibility with print templates throughout the system.

require_once __DIR__ . '/../../../../server/session.php';
require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/general/rbac.php';

header('Content-Type: application/json');

const CP_PREFIX = 'cp_';
const CP_GROUP  = 'Company Profile';

// Boolean fields — stored as 'true'/'false' strings in settings, cast back on read
const CP_BOOL = ['print_logo', 'print_seal'];

// All company profile form fields with their label and type metadata
const CP_META = [
    'name'                 => ['Company Name',         'text'],
    'short_name'           => ['Short Name',           'text'],
    'reg_number'           => ['Registration Number',  'text'],
    'tin'                  => ['TIN',                  'text'],
    'vat_number'           => ['VAT Number',           'text'],
    'business_type'        => ['Business Type',        'select'],
    'industry'             => ['Industry',             'text'],
    'established_date'     => ['Established Date',     'date'],
    'company_status'       => ['Company Status',       'select'],
    'telephone'            => ['Telephone',            'text'],
    'mobile'               => ['Mobile',               'text'],
    'fax'                  => ['Fax',                  'text'],
    'email'                => ['Email',                'text'],
    'website'              => ['Website',              'text'],
    'address_line1'        => ['Address Line 1',       'text'],
    'address_line2'        => ['Address Line 2',       'text'],
    'city'                 => ['City',                 'text'],
    'district'             => ['District',             'text'],
    'province'             => ['Province',             'text'],
    'postal_code'          => ['Postal Code',          'text'],
    'country'              => ['Country',              'text'],
    'logo_path'            => ['Logo Path',            'text'],
    'seal_path'            => ['Seal Path',            'text'],
    'base_currency'        => ['Base Currency',        'select'],
    'financial_year_start' => ['Financial Year Start', 'date'],
    'financial_year_end'   => ['Financial Year End',   'date'],
    'language'             => ['Language',             'select'],
    'timezone'             => ['Timezone',             'select'],
    'date_format'          => ['Date Format',          'select'],
    'time_format'          => ['Time Format',          'select'],
    'bank_name'            => ['Bank Name',            'text'],
    'branch_name'          => ['Branch Name',          'text'],
    'account_name'         => ['Account Name',         'text'],
    'account_number'       => ['Account Number',       'text'],
    'swift_code'           => ['SWIFT Code',           'text'],
    'print_logo'           => ['Print Logo',           'boolean'],
    'print_seal'           => ['Print Seal',           'boolean'],
    'paper_size'           => ['Paper Size',           'select'],
    'report_footer'        => ['Report Footer',        'textarea'],
];

function getCompany(): array {
    $infoRows = supabase_get(SB_API . 'sys_company_info?select=id,name,email,phone,address&limit=1');
    $info     = $infoRows[0] ?? [];

    $settings = supabase_get(
        SB_API . 'sys_company_settings?setting_group=eq.' . urlencode(CP_GROUP) . '&select=setting_key,setting_value'
    );

    $data = ['id' => $info['id'] ?? null];
    foreach ($settings as $s) {
        $field = substr($s['setting_key'], strlen(CP_PREFIX));
        $value = $s['setting_value'] ?? '';
        $data[$field] = in_array($field, CP_BOOL) ? ($value === 'true') : $value;
    }

    // Fall back to sys_company_info for first-time load (before any cp_ rows exist)
    if (!isset($data['name']))  $data['name']  = $info['name']  ?? '';
    if (!isset($data['email'])) $data['email'] = $info['email'] ?? '';

    return $data;
}

function saveUploadedImage(string $field, string $filename): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'], true)) {
        return null;
    }
    $dir = __DIR__ . '/../../../assets/img/company';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dest = $dir . '/' . $filename . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return null;
    }
    return '/assets/img/company/' . $filename . '.' . $ext;
}

function patchCompanyInfo(int $id, array $payload): void {
    $ch = curl_init(SUPABASE_URL . SB_API . 'sys_company_info?id=eq.' . $id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status >= 400) {
        $parsed = json_decode($body, true);
        http_response_code($status);
        echo json_encode(['error' => $parsed['message'] ?? 'Failed to update company info']);
        exit;
    }
}

function upsertSettings(array $rows): void {
    $ch = curl_init(SUPABASE_URL . SB_API . 'sys_company_settings?on_conflict=setting_key');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($rows),
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
            'Prefer: resolution=merge-duplicates',
        ],
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status >= 400) {
        $parsed = json_decode($body, true);
        http_response_code($status);
        echo json_encode(['error' => $parsed['message'] ?? 'Settings save failed']);
        exit;
    }
}

function updateCompany(): array {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }

    $str  = fn($v) => (string) ($v ?? '');
    $bool = fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN);

    // Handle image uploads — only replace when a new file is uploaded
    $logoPath = saveUploadedImage('logo_file', 'logo');
    $sealPath = saveUploadedImage('seal_file', 'seal');

    $values = [
        'name'                 => $str($_POST['name'] ?? ''),
        'short_name'           => $str($_POST['short_name'] ?? ''),
        'reg_number'           => $str($_POST['reg_number'] ?? ''),
        'tin'                  => $str($_POST['tin'] ?? ''),
        'vat_number'           => $str($_POST['vat_number'] ?? ''),
        'business_type'        => $str($_POST['business_type'] ?? ''),
        'industry'             => $str($_POST['industry'] ?? ''),
        'established_date'     => $str($_POST['established_date'] ?? ''),
        'company_status'       => $str($_POST['company_status'] ?? 'Active'),
        'telephone'            => $str($_POST['telephone'] ?? ''),
        'mobile'               => $str($_POST['mobile'] ?? ''),
        'fax'                  => $str($_POST['fax'] ?? ''),
        'email'                => $str($_POST['email'] ?? ''),
        'website'              => $str($_POST['website'] ?? ''),
        'address_line1'        => $str($_POST['address_line1'] ?? ''),
        'address_line2'        => $str($_POST['address_line2'] ?? ''),
        'city'                 => $str($_POST['city'] ?? ''),
        'district'             => $str($_POST['district'] ?? ''),
        'province'             => $str($_POST['province'] ?? ''),
        'postal_code'          => $str($_POST['postal_code'] ?? ''),
        'country'              => $str($_POST['country'] ?? ''),
        'logo_path'            => $logoPath ?? $str($_POST['logo_path'] ?? ''),
        'seal_path'            => $sealPath ?? $str($_POST['seal_path'] ?? ''),
        'base_currency'        => $str($_POST['base_currency'] ?? 'LKR'),
        'financial_year_start' => $str($_POST['financial_year_start'] ?? ''),
        'financial_year_end'   => $str($_POST['financial_year_end'] ?? ''),
        'language'             => $str($_POST['language'] ?? 'en'),
        'timezone'             => $str($_POST['timezone'] ?? 'Asia/Colombo'),
        'date_format'          => $str($_POST['date_format'] ?? 'DD/MM/YYYY'),
        'time_format'          => $str($_POST['time_format'] ?? '24h'),
        'bank_name'            => $str($_POST['bank_name'] ?? ''),
        'branch_name'          => $str($_POST['branch_name'] ?? ''),
        'account_name'         => $str($_POST['account_name'] ?? ''),
        'account_number'       => $str($_POST['account_number'] ?? ''),
        'swift_code'           => $str($_POST['swift_code'] ?? ''),
        'print_logo'           => $bool($_POST['print_logo'] ?? 'true'),
        'print_seal'           => $bool($_POST['print_seal'] ?? 'false'),
        'paper_size'           => $str($_POST['paper_size'] ?? 'A4'),
        'report_footer'        => $str($_POST['report_footer'] ?? ''),
    ];

    // 1. Keep sys_company_info base columns in sync for print templates
    patchCompanyInfo($id, [
        'name'    => $values['name'],
        'email'   => $values['email'],
        'phone'   => $values['telephone'] ?: $values['mobile'],
        'address' => implode(', ', array_filter([
            $values['address_line1'], $values['city'],
            $values['district'],      $values['province'],
        ])) ?: null,
    ]);

    // 2. Upsert all fields to sys_company_settings with cp_ prefix
    $rows = [];
    foreach (CP_META as $field => [$label, $type]) {
        $v = $values[$field];
        $rows[] = [
            'setting_key'   => CP_PREFIX . $field,
            'setting_label' => $label,
            'setting_value' => is_bool($v) ? ($v ? 'true' : 'false') : $v,
            'setting_type'  => $type,
            'setting_group' => CP_GROUP,
        ];
    }
    upsertSettings($rows);

    return array_merge(['id' => $id], $values);
}

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    requireModulePermission('can_view');
    echo json_encode(getCompany());
} elseif ($action === 'update') {
    requireModulePermission('can_edit');
    echo json_encode(updateCompany());
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
