<?php
/**
 * File Upload Handler
 * Handles secure image uploads with validation
 */

require_once 'config.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

requireAuth();

// Check if files were uploaded
if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    sendResponse(false, 'No files uploaded');
}

$files = $_FILES['images'];
$uploadedFiles = [];
$errors = [];

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Check number of files
$fileCount = count($files['name']);
if ($fileCount > MAX_IMAGES_PER_LISTING) {
    sendResponse(false, 'Maximum ' . MAX_IMAGES_PER_LISTING . ' images allowed');
}

// Process each file
for ($i = 0; $i < $fileCount; $i++) {
    // Skip if no file
    if (empty($files['name'][$i])) {
        continue;
    }
    
    $file = [
        'name' => $files['name'][$i],
        'type' => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error' => $files['error'][$i],
        'size' => $files['size'][$i]
    ];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading {$file['name']}";
        continue;
    }
    
    // Validate file
    $validationErrors = validateImageFile($file);
    if (!empty($validationErrors)) {
        $errors[] = "{$file['name']}: " . implode(', ', $validationErrors);
        continue;
    }
    
    // Generate unique filename
    $newFilename = generateUniqueFilename($file['name']);
    $destination = UPLOAD_DIR . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $uploadedFiles[] = [
            'original_name' => $file['name'],
            'filename' => $newFilename,
            'path' => 'uploads/' . $newFilename,
            'url' => UPLOAD_URL . $newFilename
        ];
    } else {
        $errors[] = "Failed to save {$file['name']}";
    }
}

// Send response
if (!empty($uploadedFiles)) {
    sendResponse(true, 'Files uploaded successfully', [
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);
} else {
    sendResponse(false, 'No files uploaded successfully', ['errors' => $errors]);
}
