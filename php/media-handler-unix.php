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
    '' => 'HarvestMissions!',
     'Pascualshannenjael@gmail.com' =. 'shannenlovesJesus!',
    'thedixonmissions@gmail.com' => 'DixonMissions2026!'
);

// Base path for media library
$mediaBasePath = dirname(__FILE__) . '/../data/media-library';

// Maximum file size (50MB)
$maxFileSize = 50 * 1024 * 1024;

// Allowed file extensions
$allowedExtensions = array(
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff','heic',
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
    case 'delete':
        handleDelete();
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
?>



