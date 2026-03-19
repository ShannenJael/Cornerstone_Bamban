<?php
/**
 * Missions Letter Handler
 * Cornerstone Baptist Church
 *
 * This script handles authentication and missionary letter management.
 *
 * IMPORTANT: Change the username and password below before deploying to production!
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// CONFIGURATION - CHANGE THESE CREDENTIALS!
// ============================================
$validUsers = array(
    '' => 'HarvestMissions!',
    'Pascualshannenjael@gmail.com' =. 'shannenlovesJesus!'

);

// Data file path
$dataFile = dirname(__FILE__) . '/../data/missionary-letter.json';
$archiveFile = dirname(__FILE__) . '/../data/missionary-letters.json';

// ============================================
// MAIN LOGIC
// ============================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'save':
        handleSave();
        break;
    // Archive Management Actions
    case 'archive_add':
        handleArchiveAdd();
        break;
    case 'archive_delete':
        handleArchiveDelete();
        break;
    // Visitation Card Actions
    case 'visitation_save':
        handleVisitationSave();
        break;
    case 'visitation_list':
        handleVisitationList();
        break;
    default:
        echo json_encode(array('success' => false, 'message' => 'Invalid action'));
}

// ============================================
// FUNCTIONS
// ============================================

function handleLogin() {
    global $validUsers;

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Check credentials
    if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Login successful',
            'username' => $username
        ));
    } else {
        // Log failed attempt (optional - for security monitoring)
        error_log('Failed login attempt for user: ' . $username . ' from IP: ' . $_SERVER['REMOTE_ADDR']);

        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid credentials'
        ));
    }
}

function handleSave() {
    global $dataFile;

    $data = isset($_POST['data']) ? $_POST['data'] : '';

    if (empty($data)) {
        echo json_encode(array('success' => false, 'message' => 'No data provided'));
        return;
    }

    // Validate JSON
    $letterData = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(array('success' => false, 'message' => 'Invalid data format'));
        return;
    }

    // Validate required fields (content optional if PDF is uploaded)
    $requiredFields = array('missionaryName', 'location', 'date');
    foreach ($requiredFields as $field) {
        if (empty($letterData[$field])) {
            echo json_encode(array('success' => false, 'message' => 'Missing required field: ' . $field));
            return;
        }
    }

    // Sanitize content (basic XSS prevention)
    $letterData['missionaryName'] = htmlspecialchars($letterData['missionaryName'], ENT_QUOTES, 'UTF-8');
    $letterData['location'] = htmlspecialchars($letterData['location'], ENT_QUOTES, 'UTF-8');
    $letterData['date'] = htmlspecialchars($letterData['date'], ENT_QUOTES, 'UTF-8');
    // Allow line breaks in content but escape HTML
    $letterData['content'] = htmlspecialchars($letterData['content'], ENT_QUOTES, 'UTF-8');

    // Add metadata
    $letterData['lastUpdated'] = date('Y-m-d H:i:s');
    $letterData['updatedBy'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

    // Ensure data directory exists
    $dataDir = dirname($dataFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    // Create backup of existing file
    if (file_exists($dataFile)) {
        $backupFile = $dataDir . '/missionary-letter-backup-' . date('Y-m-d-His') . '.json';
        copy($dataFile, $backupFile);

        // Keep only last 5 backups
        cleanupBackups($dataDir);
    }

    // Handle optional PDF/DOC/DOCX upload
    // Log upload attempt for debugging
    if (isset($_FILES['letterPdf'])) {
        error_log('File upload attempt: ' . print_r($_FILES['letterPdf'], true));
    }

    if (isset($_FILES['letterPdf']) && $_FILES['letterPdf']['error'] === UPLOAD_ERR_OK) {
        $uploadName = $_FILES['letterPdf']['name'];
        $tmpPath = $_FILES['letterPdf']['tmp_name'];
        $ext = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

        $allowed = array('pdf', 'doc', 'docx');
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(array('success' => false, 'message' => 'Only PDF or Word files are allowed.'));
            return;
        }

        $dataDir = dirname($dataFile);
        $safeName = 'missionary-letter-' . date('Ymd-His') . '.' . $ext;
        $destPath = $dataDir . '/' . $safeName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            echo json_encode(array('success' => false, 'message' => 'Failed to upload file. Check file permissions.'));
            return;
        }

        $letterData['pdfFile'] = $safeName;
    }

    // Require content if no PDF was uploaded
    if (empty($letterData['content']) && empty($letterData['pdfFile'])) {
        echo json_encode(array('success' => false, 'message' => 'Please provide letter content or upload a PDF.'));
        return;
    }

    // Save the letter
    $result = file_put_contents($dataFile, json_encode($letterData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($result !== false) {
        updateArchive($letterData);
        // Return the saved data so the UI can update immediately without a second fetch
        echo json_encode(array(
            'success' => true,
            'message' => 'Letter saved successfully',
            'data' => $letterData
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => 'Failed to save letter. Check file permissions.'
        ));
    }
}

function updateArchive($letterData) {
    global $archiveFile;

    $dataDir = dirname($archiveFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $archive = array('letters' => array());
    if (file_exists($archiveFile)) {
        $existing = json_decode(file_get_contents($archiveFile), true);
        if ($existing && isset($existing['letters'])) {
            $archive = $existing;
        }
    }

    $entry = array(
        'missionaryName' => $letterData['missionaryName'] ?? '',
        'location' => $letterData['location'] ?? '',
        'date' => $letterData['date'] ?? '',
        'lastUpdated' => $letterData['lastUpdated'] ?? '',
        'pdfFile' => $letterData['pdfFile'] ?? '',
        'content' => $letterData['content'] ?? ''
    );

    array_unshift($archive['letters'], $entry);
    $archive['letters'] = array_slice($archive['letters'], 0, 50);

    file_put_contents($archiveFile, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function cleanupBackups($dataDir) {
    $backups = glob($dataDir . '/missionary-letter-backup-*.json');
    if (count($backups) > 5) {
        // Sort by modification time (oldest first)
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Remove oldest backups, keeping only 5
        $toDelete = array_slice($backups, 0, count($backups) - 5);
        foreach ($toDelete as $file) {
            unlink($file);
        }
    }
}

// ============================================
// ARCHIVE MANAGEMENT FUNCTIONS
// ============================================

function handleArchiveAdd() {
    global $archiveFile;

    $data = isset($_POST['data']) ? $_POST['data'] : '';

    if (empty($data)) {
        echo json_encode(array('success' => false, 'message' => 'No data provided'));
        return;
    }

    // Validate JSON
    $letterData = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(array('success' => false, 'message' => 'Invalid data format'));
        return;
    }

    // Validate required fields
    if (empty($letterData['missionaryName']) || empty($letterData['date'])) {
        echo json_encode(array('success' => false, 'message' => 'Missionary name and date are required.'));
        return;
    }

    // Sanitize content
    $letterData['missionaryName'] = htmlspecialchars($letterData['missionaryName'], ENT_QUOTES, 'UTF-8');
    $letterData['location'] = htmlspecialchars($letterData['location'] ?? '', ENT_QUOTES, 'UTF-8');
    $letterData['date'] = htmlspecialchars($letterData['date'], ENT_QUOTES, 'UTF-8');
    $letterData['content'] = htmlspecialchars($letterData['content'] ?? '', ENT_QUOTES, 'UTF-8');

    // Handle optional PDF/DOC/DOCX upload
    if (isset($_FILES['letterPdf']) && $_FILES['letterPdf']['error'] === UPLOAD_ERR_OK) {
        $uploadName = $_FILES['letterPdf']['name'];
        $tmpPath = $_FILES['letterPdf']['tmp_name'];
        $ext = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));

        $allowed = array('pdf', 'doc', 'docx');
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(array('success' => false, 'message' => 'Only PDF or Word files are allowed.'));
            return;
        }

        $dataDir = dirname($archiveFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $safeName = 'archive-letter-' . date('Ymd-His') . '.' . $ext;
        $destPath = $dataDir . '/' . $safeName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            echo json_encode(array('success' => false, 'message' => 'Failed to upload file.'));
            return;
        }

        $letterData['pdfFile'] = $safeName;
    }

    // Require content or PDF
    if (empty($letterData['content']) && empty($letterData['pdfFile'])) {
        echo json_encode(array('success' => false, 'message' => 'Please provide letter content or upload a PDF.'));
        return;
    }

    // Load existing archive
    $dataDir = dirname($archiveFile);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $archive = array('letters' => array());
    if (file_exists($archiveFile)) {
        $existing = json_decode(file_get_contents($archiveFile), true);
        if ($existing && isset($existing['letters'])) {
            $archive = $existing;
        }
    }

    // Add new entry
    $entry = array(
        'missionaryName' => $letterData['missionaryName'],
        'location' => $letterData['location'],
        'date' => $letterData['date'],
        'lastUpdated' => date('Y-m-d H:i:s'),
        'pdfFile' => $letterData['pdfFile'] ?? '',
        'content' => $letterData['content']
    );

    array_unshift($archive['letters'], $entry);
    $archive['letters'] = array_slice($archive['letters'], 0, 100); // Keep max 100 entries

    if (file_put_contents($archiveFile, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(array('success' => true, 'message' => 'Letter added to archive.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to save archive.'));
    }
}

function handleArchiveDelete() {
    global $archiveFile;

    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;

    if ($index < 0) {
        echo json_encode(array('success' => false, 'message' => 'Invalid index'));
        return;
    }

    if (!file_exists($archiveFile)) {
        echo json_encode(array('success' => false, 'message' => 'Archive not found'));
        return;
    }

    $archive = json_decode(file_get_contents($archiveFile), true);
    if (!$archive || !isset($archive['letters'])) {
        echo json_encode(array('success' => false, 'message' => 'Invalid archive data'));
        return;
    }

    if ($index >= count($archive['letters'])) {
        echo json_encode(array('success' => false, 'message' => 'Letter not found'));
        return;
    }

    // Remove the letter at the specified index
    array_splice($archive['letters'], $index, 1);

    if (file_put_contents($archiveFile, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode(array('success' => true, 'message' => 'Letter removed from archive.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update archive.'));
    }
}

// ============================================
// VISITATION CARD FUNCTIONS
// ============================================

function handleVisitationSave() {
    $visitationFile = dirname(__FILE__) . '/../data/visitation-cards.json';

    // Ensure data file exists
    if (!file_exists($visitationFile)) {
        file_put_contents($visitationFile, json_encode(array('cards' => array())));
    }

    $card = array(
        'id' => uniqid('vc_'),
        'personName' => sanitizeVisitationInput($_POST['personName'] ?? ''),
        'address' => sanitizeVisitationInput($_POST['address'] ?? ''),
        'distance' => sanitizeVisitationInput($_POST['distance'] ?? ''),
        'q1Where' => sanitizeVisitationInput($_POST['q1Where'] ?? ''),
        'q2SavedBaptized' => sanitizeVisitationInput($_POST['q2SavedBaptized'] ?? ''),
        'q3NeedRide' => sanitizeVisitationInput($_POST['q3NeedRide'] ?? ''),
        'q4FollowUp' => sanitizeVisitationInput($_POST['q4FollowUp'] ?? ''),
        'status' => json_decode($_POST['status'] ?? '[]', true),
        'age' => sanitizeVisitationInput($_POST['age'] ?? ''),
        'children' => sanitizeVisitationInput($_POST['children'] ?? ''),
        'comments' => sanitizeVisitationInput($_POST['comments'] ?? ''),
        'visitorName' => sanitizeVisitationInput($_POST['visitorName'] ?? ''),
        'timestamp' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR']
    );

    if (empty($card['personName']) || empty($card['address']) || empty($card['visitorName'])) {
        echo json_encode(array('success' => false, 'message' => 'Name, address, and your name are required.'));
        return;
    }

    $data = json_decode(file_get_contents($visitationFile), true);
    if (!$data) { $data = array('cards' => array()); }
    $data['cards'][] = $card;

    if (file_put_contents($visitationFile, json_encode($data, JSON_PRETTY_PRINT))) {
        echo json_encode(array('success' => true, 'message' => 'Visitation card saved successfully.', 'id' => $card['id']));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Error saving card.'));
    }
}

function handleVisitationList() {
    $visitationFile = dirname(__FILE__) . '/../data/visitation-cards.json';

    if (!file_exists($visitationFile)) {
        echo json_encode(array('success' => true, 'cards' => array()));
        return;
    }

    $period = isset($_POST['period']) ? $_POST['period'] : 'all';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';

    $data = json_decode(file_get_contents($visitationFile), true);
    if (!$data || !isset($data['cards'])) {
        echo json_encode(array('success' => true, 'cards' => array()));
        return;
    }

    $cards = $data['cards'];
    $now = new DateTime();
    $filteredCards = array();

    foreach ($cards as $card) {
        $cardDate = new DateTime($card['timestamp']);
        switch ($period) {
            case 'week':
                $weekStart = (clone $now)->modify('monday this week');
                if ($cardDate >= $weekStart) { $filteredCards[] = $card; }
                break;
            case 'month':
                $monthStart = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                if ($cardDate >= $monthStart) { $filteredCards[] = $card; }
                break;
            case 'year':
                $yearStart = (clone $now)->modify('first day of january this year')->setTime(0, 0, 0);
                if ($cardDate >= $yearStart) { $filteredCards[] = $card; }
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $start = new DateTime($startDate);
                    $end = (new DateTime($endDate))->setTime(23, 59, 59);
                    if ($cardDate >= $start && $cardDate <= $end) { $filteredCards[] = $card; }
                } else { $filteredCards[] = $card; }
                break;
            default: $filteredCards[] = $card;
        }
    }

    usort($filteredCards, function($a, $b) { return strtotime($b['timestamp']) - strtotime($a['timestamp']); });
    echo json_encode(array('success' => true, 'cards' => $filteredCards, 'total' => count($filteredCards)));
}

function sanitizeVisitationInput($input) {
    if (is_array($input)) { return array_map('sanitizeVisitationInput', $input); }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>



