<?php
// event_calendar_data.php — read-only calendar feed for the Event Calendar view.
//
// Aggregates dated records from across the Event Management modules into one
// shape the calendar grid can render. Currently sources Site Visits only;
// once an Event Booking module exists, add another supabase_get() block here
// that appends to $events in the same shape (source/ref/date/time/status/title/url).

require_once __DIR__ . '/../../../../server/supabase.php';
require_once __DIR__ . '/../../../../server/session.php';

header('Content-Type: application/json');

function listCalendarEvents(string $from, string $to): array {
    $events = [];

    $visits = supabase_get(
        SB_API . 'm_site_visits?select=ref,visit_date,visit_time,visit_status,m_venues(venue_name)'
        . '&visit_date=gte.' . urlencode($from)
        . '&visit_date=lte.' . urlencode($to)
        . '&order=visit_date.asc'
    );
    foreach ($visits as $v) {
        $venueName = $v['m_venues']['venue_name'] ?? 'No venue';
        $events[] = [
            'source' => 'site_visit',
            'ref'    => $v['ref'],
            'date'   => $v['visit_date'],
            'time'   => $v['visit_time'],
            'status' => $v['visit_status'],
            'title'  => 'Site Visit — ' . $venueName,
            'url'    => '/home.php?page=site_visit',
        ];
    }

    return $events;
}

$action = $_GET['action'] ?? '';

if ($action === 'list_events') {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-t');
    echo json_encode(listCalendarEvents($from, $to));
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
