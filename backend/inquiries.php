<?php
/**
 * Inquiries Handler
 * Handles inquiries/contact requests
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$action = getParam('action');

switch ($action) {
    case 'send':
        handleSend();
        break;
    case 'list':
        handleList();
        break;
    case 'my-inquiries':
        handleMyInquiries();
        break;
    case 'update-status':
        handleUpdateStatus();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Send inquiry
 */
function handleSend() {
    $listingId = intval(getParam('listing_id'));
    $name = sanitize(getParam('name'));
    $email = sanitize(getParam('email'));
    $phone = sanitize(getParam('phone'));
    $message = sanitize(getParam('message'));
    
    // Validate required fields
    $missing = validateRequired(['name', 'email', 'phone'], [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ]);
    
    if (!empty($missing)) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    if ($listingId <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    // Validate email
    if (!validateEmail($email)) {
        sendResponse(false, 'Invalid email format');
    }
    
    // Validate phone
    if (!validatePhone($phone)) {
        sendResponse(false, 'Invalid phone number');
    }
    
    try {
        $db = getDB();
        
        // Check if listing exists
        $stmt = $db->prepare("SELECT id, user_id FROM listings WHERE id = ? AND is_active = 1");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch();
        
        if (!$listing) {
            sendResponse(false, 'Listing not found');
        }
        
        // Get user ID if authenticated
        $userId = isAuthenticated() ? getCurrentUserId() : null;
        
        // Insert inquiry
        $stmt = $db->prepare("
            INSERT INTO inquiries (listing_id, user_id, name, email, phone, message)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$listingId, $userId, $name, $email, $phone, $message]);
        
        sendResponse(true, 'Inquiry sent successfully. The landlord will contact you soon.');
        
    } catch (PDOException $e) {
        logError('Send Inquiry Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to send inquiry');
    }
}

/**
 * Get inquiries for landlord's listings
 */
function handleList() {
    requireRole('landlord');
    
    $listingId = intval(getParam('listing_id', 0));
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $query = "
            SELECT 
                i.id, i.name, i.email, i.phone, i.message, i.status, i.created_at,
                l.id as listing_id, l.title as listing_title
            FROM inquiries i
            JOIN listings l ON i.listing_id = l.id
            WHERE l.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($listingId > 0) {
            $query .= " AND i.listing_id = ?";
            $params[] = $listingId;
        }
        
        $query .= " ORDER BY i.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $inquiries = $stmt->fetchAll();
        
        sendResponse(true, 'Inquiries retrieved', $inquiries);
        
    } catch (PDOException $e) {
        logError('List Inquiries Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve inquiries');
    }
}

/**
 * Get user's sent inquiries
 */
function handleMyInquiries() {
    requireAuth();
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $stmt = $db->prepare("
            SELECT 
                i.id, i.message, i.status, i.created_at,
                l.id as listing_id, l.title as listing_title, l.rent, l.city,
                u.name as landlord_name, u.email as landlord_email, u.phone as landlord_phone,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM inquiries i
            JOIN listings l ON i.listing_id = l.id
            LEFT JOIN users u ON l.user_id = u.id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$userId]);
        $inquiries = $stmt->fetchAll();
        
        sendResponse(true, 'Inquiries retrieved', $inquiries);
        
    } catch (PDOException $e) {
        logError('My Inquiries Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve inquiries');
    }
}

/**
 * Update inquiry status
 */
function handleUpdateStatus() {
    requireRole('landlord');
    
    $inquiryId = intval(getParam('inquiry_id'));
    $status = sanitize(getParam('status'));
    
    if ($inquiryId <= 0) {
        sendResponse(false, 'Invalid inquiry ID');
    }
    
    if (!in_array($status, ['pending', 'responded', 'closed'])) {
        sendResponse(false, 'Invalid status');
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        // Check if inquiry belongs to landlord's listing
        $stmt = $db->prepare("
            SELECT i.id 
            FROM inquiries i
            JOIN listings l ON i.listing_id = l.id
            WHERE i.id = ? AND l.user_id = ?
        ");
        $stmt->execute([$inquiryId, $userId]);
        
        if (!$stmt->fetch()) {
            sendResponse(false, 'Unauthorized or inquiry not found');
        }
        
        // Update status
        $stmt = $db->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$status, $inquiryId]);
        
        sendResponse(true, 'Status updated successfully');
        
    } catch (PDOException $e) {
        logError('Update Status Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update status');
    }
}
