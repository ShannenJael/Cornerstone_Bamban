<?php
/**
 * Cornerstone Media Library Handler
 * Cornerstone Baptist Church
 *
 * Handles file uploads, folder management, and authentication for the media library.
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// CONFIGURATION
// ============================================
$validUsers = array(
    'thedixonmissions@gmail.com' => 'DixonMissions2026!',
    'christnemarie.cezar@gmail.com' => 'ChristneMissions!',
    'Pascualshannenjael@gmail.com' => 'shannenlovesJesus!'
);

// Base path for media library
$mediaBasePath = dirname(__FILE__) . '/../data/media-library';

// Maximum file size (50MB)
$maxFileSize = 50 * 1024 * 1024;

// Allowed file extensions
$allowedExtensions = array(
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'heic',
    // Videos
    'mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv',
    // Audio
    'mp3', 'wav', 'ogg', 'aac', 'm4a',
    // Documents
    'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx',
    // Project files
    'psd', 'ai', 'prproj', 'aep'
);

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
    case 'list':
        handleList();
        break;
    case 'upload':
        handleUpload();
        break;
    case 'createFolder':
        handleCreateFolder();
        break;
    case 'deleteFolder':
        handleDeleteFolder();
        break;
    case 'listAllFolders':
        handleListAllFolders();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'move':
        handleMove();
        break;
    // Visitation Card Actions
    case 'visitation_save':
        handleVisitationSave();
        break;
    case 'visitation_list':
        handleVisitationList();
        break;
    case 'visitation_pdf_save':
        handleVisitationPdfSave();
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

    if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Login successful',
            'username' => $username
        ));
    } else {
        error_log('Failed media library login attempt for user: ' . $username . ' from IP: ' . $_SERVER['REMOTE_ADDR']);
        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid credentials'
        ));
    }
}

function handleList() {
    global $mediaBasePath;

    $path = isset($_POST['path']) ? sanitizePath($_POST['path']) : '';
    $fullPath = $mediaBasePath . ($path ? '/' . $path : '');

    // Ensure base directory exists
    if (!is_dir($mediaBasePath)) {
        mkdir($mediaBasePath, 0755, true);
        createDefaultFolders($mediaBasePath);
    }

    if (!is_dir($fullPath)) {
        // If the requested path doesn't exist, create it
        mkdir($fullPath, 0755, true);
    }

    $folders = array();
    $files = array();

    $items = scandir($fullPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $itemPath = $fullPath . '/' . $item;

        if (is_dir($itemPath)) {
            $folders[] = $item;
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $relativePath = ($path ? $path . '/' : '') . $item;

            $files[] = array(
                'name' => $item,
                'size' => filesize($itemPath),
                'url' => '../data/media-library/' . $relativePath,
                'modified' => filemtime($itemPath)
            );
        }
    }

    // Sort folders and files alphabetically
    sort($folders);
    usort($files, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    echo json_encode(array(
        'success' => true,
        'folders' => $folders,
        'files' => $files,
        'currentPath' => $path
    ));
}

function handleUpload() {
    global $mediaBasePath, $maxFileSize, $allowedExtensions;

    $path = isset($_POST['path']) ? sanitizePath($_POST['path']) : '';
    $fullPath = $mediaBasePath . ($path ? '/' . $path : '');

    // Ensure directory exists
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'No file uploaded';
        if (isset($_FILES['file'])) {
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File is too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded';
                    break;
            }
        }
        echo json_encode(array('success' => false, 'message' => $errorMessage));
        return;
    }

    $file = $_FILES['file'];

    // Check file size
    if ($file['size'] > $maxFileSize) {
        echo json_encode(array('success' => false, 'message' => 'File exceeds maximum size of 50MB'));
        return;
    }

    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        echo json_encode(array('success' => false, 'message' => 'File type not allowed: ' . $ext));
        return;
    }

    // Sanitize filename
    $safeName = sanitizeFilename($file['name']);

    // Handle duplicate filenames
    $destPath = $fullPath . '/' . $safeName;
    if (file_exists($destPath)) {
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $counter = 1;
        while (file_exists($fullPath . '/' . $baseName . '_' . $counter . '.' . $ext)) {
            $counter++;
        }
        $safeName = $baseName . '_' . $counter . '.' . $ext;
        $destPath = $fullPath . '/' . $safeName;
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(array(
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $safeName
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to save file. Check permissions.'));
    }
}

function handleCreateFolder() {
    global $mediaBasePath;

    $path = isset($_POST['path']) ? sanitizePath($_POST['path']) : '';
    $name = isset($_POST['name']) ? sanitizeFolderName($_POST['name']) : '';

    if (empty($name)) {
        echo json_encode(array('success' => false, 'message' => 'Folder name is required'));
        return;
    }

    $fullPath = $mediaBasePath . ($path ? '/' . $path : '') . '/' . $name;

    if (is_dir($fullPath)) {
        echo json_encode(array('success' => false, 'message' => 'Folder already exists'));
        return;
    }

    if (mkdir($fullPath, 0755, true)) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Folder created successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to create folder. Check permissions.'));
    }
}

function handleListAllFolders() {
    global $mediaBasePath;

    $allFolders = array();
    collectFolders($mediaBasePath, '', $allFolders);
    sort($allFolders);

    echo json_encode(array('success' => true, 'folders' => $allFolders));
}

function collectFolders($basePath, $relativePath, &$result) {
    $scanPath = $relativePath ? $basePath . '/' . $relativePath : $basePath;
    if (!is_dir($scanPath)) return;

    $items = array_diff(scandir($scanPath), array('.', '..'));
    foreach ($items as $item) {
        $itemFull = $scanPath . '/' . $item;
        if (is_dir($itemFull)) {
            $itemRelative = $relativePath ? $relativePath . '/' . $item : $item;
            $result[] = $itemRelative;
            collectFolders($basePath, $itemRelative, $result);
        }
    }
}

function handleDeleteFolder() {
    global $mediaBasePath;

    $path = isset($_POST['path']) ? sanitizePath($_POST['path']) : '';

    if (empty($path)) {
        echo json_encode(array('success' => false, 'message' => 'Folder path is required'));
        return;
    }

    $fullPath = $mediaBasePath . '/' . $path;

    if (!is_dir($fullPath)) {
        echo json_encode(array('success' => false, 'message' => 'Folder not found'));
        return;
    }

    if (deleteFolderRecursive($fullPath)) {
        echo json_encode(array('success' => true, 'message' => 'Folder deleted successfully'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to delete folder'));
    }
}

function deleteFolderRecursive($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $itemPath = $dir . '/' . $item;
        if (is_dir($itemPath)) {
            deleteFolderRecursive($itemPath);
        } else {
            unlink($itemPath);
        }
    }
    return rmdir($dir);
}

function handleDelete() {
    global $mediaBasePath;

    $path = isset($_POST['path']) ? sanitizePath($_POST['path']) : '';
    $file = isset($_POST['file']) ? $_POST['file'] : '';

    if (empty($file)) {
        echo json_encode(array('success' => false, 'message' => 'File name is required'));
        return;
    }

    $fullPath = $mediaBasePath . ($path ? '/' . $path : '') . '/' . basename($file);

    if (!file_exists($fullPath)) {
        echo json_encode(array('success' => false, 'message' => 'File not found'));
        return;
    }

    if (is_dir($fullPath)) {
        // Don't allow deleting folders through this endpoint for safety
        echo json_encode(array('success' => false, 'message' => 'Cannot delete folders'));
        return;
    }

    if (unlink($fullPath)) {
        echo json_encode(array(
            'success' => true,
            'message' => 'File deleted successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to delete file'));
    }
}

function handleMove() {
    global $mediaBasePath;

    $fromPath = isset($_POST['fromPath']) ? sanitizePath($_POST['fromPath']) : '';
    $toPath = isset($_POST['toPath']) ? sanitizePath($_POST['toPath']) : '';
    $file = isset($_POST['file']) ? $_POST['file'] : '';

    if (empty($file)) {
        echo json_encode(array('success' => false, 'message' => 'File name is required'));
        return;
    }

    $safeFile = basename($file);
    $fromFull = $mediaBasePath . ($fromPath ? '/' . $fromPath : '') . '/' . $safeFile;
    $toDir = $mediaBasePath . ($toPath ? '/' . $toPath : '');

    if (!file_exists($fromFull) || is_dir($fromFull)) {
        echo json_encode(array('success' => false, 'message' => 'File not found'));
        return;
    }

    if (!is_dir($toDir)) {
        mkdir($toDir, 0755, true);
    }

    $destPath = $toDir . '/' . $safeFile;
    if (file_exists($destPath)) {
        $ext = pathinfo($safeFile, PATHINFO_EXTENSION);
        $baseName = pathinfo($safeFile, PATHINFO_FILENAME);
        $counter = 1;
        do {
            $candidate = $baseName . '_' . $counter . ($ext ? '.' . $ext : '');
            $destPath = $toDir . '/' . $candidate;
            $counter++;
        } while (file_exists($destPath));
    }

    if (rename($fromFull, $destPath)) {
        echo json_encode(array(
            'success' => true,
            'message' => 'File moved successfully'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to move file'));
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function sanitizePath($path) {
    // Remove any potentially dangerous characters
    $path = str_replace(array('..', "\0"), '', $path);
    $path = preg_replace('/[^a-zA-Z0-9\-_\/\(\)\s]/', '', $path);
    $path = trim($path, '/');
    return $path;
}

function sanitizeFilename($filename) {
    // Get extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // Remove special characters but keep some common ones
    $name = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $name);
    $name = preg_replace('/\s+/', '-', $name);
    $name = trim($name, '-');

    // Limit length
    if (strlen($name) > 100) {
        $name = substr($name, 0, 100);
    }

    // If name is empty, use timestamp
    if (empty($name)) {
        $name = 'file-' . date('Ymd-His');
    }

    return $name . '.' . $ext;
}

function sanitizeFolderName($name) {
    // Remove special characters but keep spaces and common punctuation
    $name = preg_replace('/[^a-zA-Z0-9\-_\s\(\)]/', '', $name);
    $name = trim($name);

    // Limit length
    if (strlen($name) > 50) {
        $name = substr($name, 0, 50);
    }

    return $name;
}

function createDefaultFolders($basePath) {
    $folders = array(
        'Worship Services',
        'Worship Services/Sunday Traditional',
        'Worship Services/Sunday Contemporary',
        'Worship Services/Wednesday Services',
        'Worship Services/Special Services',
        'Ministries',
        'Ministries/Children (Kids)',
        'Ministries/Youth (Students)',
        'Ministries/Adults',
        'Ministries/Missions',
        'Ministries/Seniors',
        'Special Events',
        'Special Events/Easter',
        'Special Events/Christmas',
        'Special Events/VBS',
        'Special Events/Baptisms',
        'Special Events/Community Outreach',
        'Staff & Leadership',
        'Staff & Leadership/Headshots',
        'Staff & Leadership/Team Photos',
        'Staff & Leadership/Pastor Media',
        'Facilities',
        'Facilities/Church Grounds',
        'Facilities/Sanctuary',
        'Facilities/Fellowship Hall',
        'Facilities/Classrooms',
        'Facilities/Parking & Exterior',
        'Technical',
        'Technical/High-Resolution (Print)',
        'Technical/Web-Optimized',
        'Technical/Public-Ready',
        'Technical/Working Files'
    );

    foreach ($folders as $folder) {
        $path = $basePath . '/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
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

function handleVisitationPdfSave() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array('success' => false, 'message' => 'No PDF uploaded.'));
        return;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        echo json_encode(array('success' => false, 'message' => 'Only PDF files are allowed.'));
        return;
    }

    $targetDir = dirname(__FILE__) . '/../data/visitation-pdfs';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $targetPath = $targetDir . '/' . $safeName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(array('success' => true, 'message' => 'PDF saved.', 'path' => $safeName));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to save PDF.'));
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


