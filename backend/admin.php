<?php
/**
 * Admin Panel Handler
 * Administrative operations for managing users and listings
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

requireRole('admin');

$action = getParam('action');

switch ($action) {
    case 'users':
        handleGetUsers();
        break;
    case 'listings':
        handleGetAllListings();
        break;
    case 'toggle-user':
        handleToggleUser();
        break;
    case 'toggle-listing':
        handleToggleListing();
        break;
    case 'stats':
        handleGetStats();
        break;
    case 'delete-user':
        handleDeleteUser();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Get all users
 */
function handleGetUsers() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                u.id, u.name, u.email, u.phone, u.role, u.is_active, u.created_at,
                (SELECT COUNT(*) FROM listings WHERE user_id = u.id) as listing_count,
                (SELECT COUNT(*) FROM inquiries WHERE user_id = u.id) as inquiry_count
            FROM users u
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        sendResponse(true, 'Users retrieved', $users);
        
    } catch (PDOException $e) {
        logError('Admin Get Users Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve users');
    }
}

/**
 * Get all listings
 */
function handleGetAllListings() {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                l.id, l.title, l.rent, l.city, l.is_active, l.views, l.created_at,
                u.name as landlord_name, u.email as landlord_email,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail,
                (SELECT COUNT(*) FROM inquiries WHERE listing_id = l.id) as inquiry_count,
                (SELECT COUNT(*) FROM favorites WHERE listing_id = l.id) as favorite_count
            FROM listings l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
        ");
        $stmt->execute();
        $listings = $stmt->fetchAll();
        
        sendResponse(true, 'Listings retrieved', $listings);
        
    } catch (PDOException $e) {
        logError('Admin Get Listings Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve listings');
    }
}

/**
 * Toggle user active status
 */
function handleToggleUser() {
    $userId = intval(getParam('user_id'));
    
    if ($userId <= 0) {
        sendResponse(false, 'Invalid user ID');
    }
    
    // Prevent admin from deactivating themselves
    if ($userId == getCurrentUserId()) {
        sendResponse(false, 'Cannot deactivate your own account');
    }
    
    try {
        $db = getDB();
        
        // Get current status
        $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse(false, 'User not found');
        }
        
        // Toggle status
        $newStatus = $user['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        
        $message = $newStatus ? 'User activated' : 'User deactivated';
        sendResponse(true, $message, ['is_active' => $newStatus]);
        
    } catch (PDOException $e) {
        logError('Toggle User Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update user status');
    }
}

/**
 * Toggle listing active status
 */
function handleToggleListing() {
    $listingId = intval(getParam('listing_id'));
    
    if ($listingId <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        
        // Get current status
        $stmt = $db->prepare("SELECT is_active FROM listings WHERE id = ?");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch();
        
        if (!$listing) {
            sendResponse(false, 'Listing not found');
        }
        
        // Toggle status
        $newStatus = $listing['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE listings SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $listingId]);
        
        $message = $newStatus ? 'Listing activated' : 'Listing deactivated';
        sendResponse(true, $message, ['is_active' => $newStatus]);
        
    } catch (PDOException $e) {
        logError('Toggle Listing Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update listing status');
    }
}

/**
 * Get dashboard statistics
 */
function handleGetStats() {
    try {
        $db = getDB();
        
        // Total users by role
        $stmt = $db->query("
            SELECT role, COUNT(*) as count 
            FROM users 
            GROUP BY role
        ");
        $usersByRole = $stmt->fetchAll();
        
        // Total listings
        $stmt = $db->query("SELECT COUNT(*) as total FROM listings WHERE is_active = 1");
        $totalListings = $stmt->fetch()['total'];
        
        // Total inquiries
        $stmt = $db->query("SELECT COUNT(*) as total FROM inquiries");
        $totalInquiries = $stmt->fetch()['total'];
        
        // Total favorites
        $stmt = $db->query("SELECT COUNT(*) as total FROM favorites");
        $totalFavorites = $stmt->fetch()['total'];
        
        // Recent listings
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM listings 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $recentListings = $stmt->fetch()['count'];
        
        // Recent users
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $recentUsers = $stmt->fetch()['count'];
        
        sendResponse(true, 'Statistics retrieved', [
            'users_by_role' => $usersByRole,
            'total_listings' => $totalListings,
            'total_inquiries' => $totalInquiries,
            'total_favorites' => $totalFavorites,
            'recent_listings' => $recentListings,
            'recent_users' => $recentUsers
        ]);
        
    } catch (PDOException $e) {
        logError('Admin Stats Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve statistics');
    }
}

/**
 * Delete user permanently
 */
function handleDeleteUser() {
    $userId = intval(getParam('user_id'));
    
    if ($userId <= 0) {
        sendResponse(false, 'Invalid user ID');
    }
    
    // Prevent admin from deleting themselves
    if ($userId == getCurrentUserId()) {
        sendResponse(false, 'Cannot delete your own account');
    }
    
    try {
        $db = getDB();
        
        // Delete user (cascades to related records)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        sendResponse(true, 'User deleted successfully');
        
    } catch (PDOException $e) {
        logError('Delete User Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to delete user');
    }
}
