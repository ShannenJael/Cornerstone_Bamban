<?php
// YouTube API handler - added to existing working file to bypass cache issues
if (isset($_GET['ytmode'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    parse_str($_SERVER['QUERY_STRING'] ?? '', $p);
    $mode = $p['ytmode'];
    $pid = $p['playlistId'] ?? ($p['pid'] ?? '');
    $cid = $p['channelId'] ?? ($p['cid'] ?? '');
    $max = min(10, max(1, intval($p['maxResults'] ?? 3)));
    $key = 'AIzaSyAsVWkfl8u6urcct-ofHJdeR6PQYGtg66U';
    if ($mode === 'playlistItems' && $pid) {
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=$max&playlistId=" . urlencode($pid) . "&key=$key";
    } elseif ($mode === 'channelLatest' && $cid) {
        $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&order=date&type=video&channelId=" . urlencode($cid) . "&maxResults=$max&key=$key";
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ytmode or missing id']);
        exit;
    }
    $r = @file_get_contents($url);
    echo $r ?: json_encode(['error' => 'API failed']);
    exit;
}

/**
 * Cornerstone Baptist Church - Google Calendar Sync
 *
 * This script fetches events from Google Calendar using the Calendar API
 * with service account authentication and updates the calendar-events.json file.
 *
 * Usage:
 *   - Manual: Visit https://cornerstonebaptistchurch.com/php/calendar-sync.php?key=YOUR_SECRET_KEY
 *   - Cron:   curl "https://cornerstonebaptistchurch.com/php/calendar-sync.php?key=YOUR_SECRET_KEY"
 *
 * Set up a cron job to run daily:
 *   0 6 * * * curl -s "https://cornerstonebaptistchurch.com/php/calendar-sync.php?key=YOUR_SECRET_KEY" > /dev/null
 */

// ============================================
// CONFIGURATION - UPDATE THESE VALUES
// ============================================

// Set timezone to Central Time (adjust if your church is in a different timezone)
date_default_timezone_set('America/Chicago');

// Secret key to prevent unauthorized access (change this!)
$secretKey = 'HarvestSync2026!';

// Path to service account JSON key file (downloaded from Google Cloud Console)
// IMPORTANT: Store this file OUTSIDE of public_html for security!
$serviceAccountKeyFile = dirname(__FILE__) . '/../../../service-account-key.json';

// Email address to impersonate (must be a Google Workspace user with calendar access)
// This should be your admin email or the calendar owner's email
$impersonateEmail = '';

// Google Calendar IDs
$calendarIds = array(
    'church' => 'c_c23d79b234acea3d70cb943a69159569ad65f2d9e53013d3c4003dd3c153c322@group.calendar.google.com',
    'academy' => 'c_f0b032a8c6150a777f6f2573b2d43208742eadb33ac32eafea0eb41d6f3ecf68@group.calendar.google.com'
);

// Output file path
$outputFile = dirname(__FILE__) . '/../data/calendar-events.json';

// How many months of events to include (past and future)
$monthsBack = 1;
$monthsForward = 12;

// ============================================
// SECURITY CHECK
// ============================================

header('Content-Type: application/json');

// Verify secret key
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    echo json_encode(array('error' => 'Unauthorized. Provide valid key parameter.'));
    exit;
}

// ============================================
// GOOGLE CALENDAR API AUTHENTICATION
// ============================================

/**
 * Generate JWT for service account authentication
 */
function generateJWT($serviceAccount, $impersonateEmail) {
    $now = time();
    $expiration = $now + 3600; // 1 hour

    $header = array(
        'alg' => 'RS256',
        'typ' => 'JWT'
    );

    $claimSet = array(
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $expiration,
        'iat' => $now,
        'sub' => $impersonateEmail // Impersonate this user
    );

    $base64Header = base64UrlEncode(json_encode($header));
    $base64ClaimSet = base64UrlEncode(json_encode($claimSet));

    $signatureInput = $base64Header . '.' . $base64ClaimSet;

    // Sign with private key
    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
    openssl_free_key($privateKey);

    $base64Signature = base64UrlEncode($signature);

    return $signatureInput . '.' . $base64Signature;
}

/**
 * Base64 URL encode (without padding)
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Get access token using service account JWT
 */
function getAccessToken($serviceAccount, $impersonateEmail) {
    $jwt = generateJWT($serviceAccount, $impersonateEmail);

    $postData = http_build_query(array(
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ));

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 30
        )
    ));

    $response = @file_get_contents('https://oauth2.googleapis.com/token', false, $context);

    if ($response === false) {
        error_log('Calendar sync: Failed to get access token');
        return null;
    }

    $responseData = json_decode($response, true);
    return isset($responseData['access_token']) ? $responseData['access_token'] : null;
}

/**
 * Fetch events from Google Calendar API
 */
function fetchCalendarEvents($calendarId, $source, $accessToken, $startDate, $endDate) {
    $events = array();

    // Build API URL
    $params = array(
        'timeMin' => $startDate . 'T00:00:00Z',
        'timeMax' => $endDate . 'T23:59:59Z',
        'maxResults' => 250,
        'singleEvents' => 'true',
        'orderBy' => 'startTime'
    );

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' .
           urlencode($calendarId) . '/events?' . http_build_query($params);

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $accessToken,
            'timeout' => 30
        )
    ));

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        error_log("Calendar sync: Failed to fetch $source calendar events");
        return $events;
    }

    $data = json_decode($response, true);

    if (!isset($data['items'])) {
        return $events;
    }

    // Parse each event
    foreach ($data['items'] as $item) {
        if (!isset($item['summary'])) {
            continue; // Skip events without a title
        }

        $event = array(
            'title' => $item['summary'],
            'source' => $source
        );

        // Parse start date/time
        if (isset($item['start']['date'])) {
            // All-day event
            $event['start'] = $item['start']['date'];
            $event['allDay'] = true;

            if (isset($item['end']['date'])) {
                // Google Calendar API end date is exclusive for all-day events
                // Subtract one day to get the actual last day
                $endTimestamp = strtotime($item['end']['date']) - 86400;
                $event['end'] = date('Y-m-d', $endTimestamp);
            }
        } else if (isset($item['start']['dateTime'])) {
            // Timed event
            $event['start'] = convertToLocalTime($item['start']['dateTime']);
            $event['allDay'] = false;

            if (isset($item['end']['dateTime'])) {
                $event['end'] = convertToLocalTime($item['end']['dateTime']);
            }
        }

        // Add location if available
        if (isset($item['location']) && !empty($item['location'])) {
            $event['location'] = $item['location'];
        }

        $events[] = $event;
    }

    return $events;
}

/**
 * Convert UTC/timezone datetime to local format (Y-m-d\TH:i:s)
 */
function convertToLocalTime($dateTimeStr) {
    $timestamp = strtotime($dateTimeStr);
    return date('Y-m-d\TH:i:s', $timestamp);
}

// ============================================
// MAIN SYNC LOGIC
// ============================================

$allEvents = array();
$errors = array();

// Load service account credentials
if (!file_exists($serviceAccountKeyFile)) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Service account key file not found at: ' . $serviceAccountKeyFile,
        'hint' => 'Download the JSON key file from Google Cloud Console and place it at the configured path'
    ));
    exit;
}

$serviceAccount = json_decode(file_get_contents($serviceAccountKeyFile), true);

if (!$serviceAccount) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Failed to parse service account key file'
    ));
    exit;
}

// Get access token
$accessToken = getAccessToken($serviceAccount, $impersonateEmail);

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Failed to obtain access token',
        'hint' => 'Verify that domain-wide delegation is set up correctly for the service account'
    ));
    exit;
}

// Calculate date range
$startDate = date('Y-m-d', strtotime("-$monthsBack months"));
$endDate = date('Y-m-d', strtotime("+$monthsForward months"));

// Fetch events from each calendar
foreach ($calendarIds as $source => $calendarId) {
    $events = fetchCalendarEvents($calendarId, $source, $accessToken, $startDate, $endDate);

    if (empty($events)) {
        $errors[] = "No events retrieved from $source calendar";
    } else {
        $allEvents = array_merge($allEvents, $events);
    }
}

// Sort events by start date
usort($allEvents, function($a, $b) {
    return strcmp($a['start'], $b['start']);
});

// Build output structure
$output = array(
    'lastUpdated' => date('Y-m-d'),
    'lastSynced' => date('Y-m-d H:i:s'),
    'calendars' => array(
        'church' => 'Cornerstone Baptist Church',
        'academy' => 'Harvest Christian Academy'
    ),
    'events' => $allEvents
);

// Write to file
$jsonOutput = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($outputFile, $jsonOutput) === false) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Failed to write output file',
        'errors' => $errors
    ));
    exit;
}

// Return success response
echo json_encode(array(
    'success' => true,
    'message' => 'Calendar sync completed',
    'lastSynced' => $output['lastSynced'],
    'eventCount' => count($allEvents),
    'bySource' => array(
        'church' => count(array_filter($allEvents, function($e) { return $e['source'] === 'church'; })),
        'academy' => count(array_filter($allEvents, function($e) { return $e['source'] === 'academy'; }))
    ),
    'errors' => $errors
));
?>



