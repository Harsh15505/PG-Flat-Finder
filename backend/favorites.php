<?php
/**
 * Favorites Handler
 * Handles adding/removing favorites
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

requireAuth();

$action = getParam('action');

switch ($action) {
    case 'toggle':
        handleToggle();
        break;
    case 'list':
        handleList();
        break;
    case 'check':
        handleCheck();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Toggle favorite (add/remove)
 */
function handleToggle() {
    $listingId = intval(getParam('listing_id'));
    
    if ($listingId <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        // Check if listing exists
        $stmt = $db->prepare("SELECT id FROM listings WHERE id = ? AND is_active = 1");
        $stmt->execute([$listingId]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Listing not found');
        }
        
        // Check if already favorited
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$userId, $listingId]);
        $favorite = $stmt->fetch();
        
        if ($favorite) {
            // Remove from favorites
            $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?");
            $stmt->execute([$userId, $listingId]);
            sendResponse(true, 'Removed from favorites', ['is_favorite' => false]);
        } else {
            // Add to favorites
            $stmt = $db->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)");
            $stmt->execute([$userId, $listingId]);
            sendResponse(true, 'Added to favorites', ['is_favorite' => true]);
        }
        
    } catch (PDOException $e) {
        logError('Toggle Favorite Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update favorites');
    }
}

/**
 * Get user's favorites list
 */
function handleList() {
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $stmt = $db->prepare("
            SELECT 
                l.id, l.title, l.rent, l.city, l.address, l.gender, l.furnished,
                f.created_at as favorited_at,
                u.name as landlord_name, u.phone as landlord_phone,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM favorites f
            JOIN listings l ON f.listing_id = l.id
            LEFT JOIN users u ON l.user_id = u.id
            WHERE f.user_id = ? AND l.is_active = 1
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        $favorites = $stmt->fetchAll();
        
        sendResponse(true, 'Favorites retrieved', $favorites);
        
    } catch (PDOException $e) {
        logError('List Favorites Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve favorites');
    }
}

/**
 * Check if listing is favorited
 */
function handleCheck() {
    $listingId = intval(getParam('listing_id'));
    
    if ($listingId <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $stmt->execute([$userId, $listingId]);
        $isFavorite = $stmt->fetch() ? true : false;
        
        sendResponse(true, '', ['is_favorite' => $isFavorite]);
        
    } catch (PDOException $e) {
        logError('Check Favorite Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to check favorite status');
    }
}
