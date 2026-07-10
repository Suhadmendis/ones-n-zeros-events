<?php

const OSRM_BASE_URL = 'https://router.project-osrm.org';
const OSRM_PROFILES = ['driving', 'walking', 'cycling'];

function osrm_profile(string $profile): string
{
    return in_array($profile, OSRM_PROFILES, true) ? $profile : 'driving';
}

function osrm_single_coordinate(string $value): ?string
{
    return preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $value) ? $value : null;
}

function osrm_coordinate_list(string $value): ?string
{
    $pattern = '/^-?\d+(\.\d+)?,-?\d+(\.\d+)?(;-?\d+(\.\d+)?,-?\d+(\.\d+)?)+$/';
    return preg_match($pattern, $value) ? $value : null;
}

function osrm_request(string $path): array
{
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'ignore_errors' => true],
    ]);

    $json = @file_get_contents(OSRM_BASE_URL . $path, false, $context);

    if ($json === false) {
        return ['code' => 'RequestFailed', 'message' => 'Could not reach the OSRM server'];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : ['code' => 'InvalidResponse', 'message' => 'OSRM returned invalid JSON'];
}

function send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function send_error(string $message, int $status = 400): void
{
    send_json(['code' => 'BadRequest', 'message' => $message], $status);
}
