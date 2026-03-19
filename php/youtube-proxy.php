<?php
// Force fresh execution - v2
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');

// Parse query string directly to bypass any caching issues
parse_str($_SERVER['QUERY_STRING'] ?? '', $params);

// Get parameters with fallbacks
$mode = $params['mode'] ?? ($params['m'] ?? '');
$maxResults = isset($params['maxResults']) ? intval($params['maxResults']) : 3;
$playlistId = $params['playlistId'] ?? ($params['pid'] ?? '');
$channelId = $params['channelId'] ?? ($params['cid'] ?? '');

$apiKey = 'AIzaSyAsVWkfl8u6urcct-ofHJdeR6PQYGtg66U';

if ($maxResults < 1) {
    $maxResults = 1;
}
if ($maxResults > 10) {
    $maxResults = 10;
}

function respondError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if ($mode === 'channelLatest') {
    if ($channelId === '') {
        respondError('Missing channelId');
    }
    $url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&order=date&type=video'
        . '&channelId=' . urlencode($channelId)
        . '&maxResults=' . $maxResults
        . '&key=' . urlencode($apiKey);
} elseif ($mode === 'playlistItems') {
    if ($playlistId === '') {
        respondError('Missing playlistId');
    }
    $url = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=' . $maxResults
        . '&playlistId=' . urlencode($playlistId)
        . '&key=' . urlencode($apiKey);
} else {
    respondError('Invalid mode');
}

$response = @file_get_contents($url);
if ($response === false) {
    respondError('Failed to reach YouTube API', 502);
}

echo $response;
