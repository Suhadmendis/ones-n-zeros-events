<?php
// modules/settings/financial_settings/stg_user_preferences/stg_user_preferences_data.php

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

$u        = current_user();
$user_ref = $u['ref'] ?? null;

if (!$user_ref) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';

// ── get ───────────────────────────────────────────────────────────────────────
if ($action === 'get') {
    $rows = supabase_get(SB_API . 'm_user_preferences?select=check_gl_default&user_ref=eq.' . urlencode($user_ref) . '&limit=1');
    if (!empty($rows)) {
        echo json_encode($rows[0]);
    } else {
        echo json_encode(['check_gl_default' => true]);
    }
    exit;
}

// ── save ──────────────────────────────────────────────────────────────────────
if ($action === 'save') {
    $body             = json_decode(file_get_contents('php://input'), true) ?? [];
    $check_gl_default = !empty($body['check_gl_default']) ? true : false;

    // Upsert: check if row exists
    $existing = supabase_get(SB_API . 'm_user_preferences?select=id&user_ref=eq.' . urlencode($user_ref) . '&limit=1');

    if (!empty($existing)) {
        supabase_patch(
            SB_API . 'm_user_preferences?user_ref=eq.' . urlencode($user_ref),
            ['check_gl_default' => $check_gl_default, 'updated_at' => gmdate('Y-m-d\TH:i:s\Z')]
        );
    } else {
        supabase_post(SB_API . 'm_user_preferences', [
            'user_ref'         => $user_ref,
            'check_gl_default' => $check_gl_default,
        ]);
    }

    echo json_encode(['success' => true, 'check_gl_default' => $check_gl_default]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
