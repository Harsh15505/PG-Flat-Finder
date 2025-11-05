<?php
/**
 * Listings Handler
 * Handles CRUD operations for property listings
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$action = getParam('action');

switch ($action) {
    case 'create':
        handleCreate();
        break;
    case 'search':
        handleSearch();
        break;
    case 'detail':
        handleDetail();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'my-listings':
        handleMyListings();
        break;
    case 'latest':
        handleLatest();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Create new listing
 */
function handleCreate() {
    requireRole('landlord');
    
    $title = sanitize(getParam('title'));
    $description = sanitize(getParam('description'));
    $rent = sanitize(getParam('rent'));
    $address = sanitize(getParam('address'));
    $city = sanitize(getParam('city'));
    $gender = sanitize(getParam('gender', 'any'));
    $furnished = getParam('furnished', 0) ? 1 : 0;
    $amenities = sanitize(getParam('amenities', ''));
    $availableFrom = sanitize(getParam('available_from'));
    $images = getParam('images'); // JSON array of uploaded image paths
    
    // Validate required fields
    $missing = validateRequired(['title', 'rent', 'address', 'city'], [
        'title' => $title,
        'rent' => $rent,
        'address' => $address,
        'city' => $city
    ]);
    
    if (!empty($missing)) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    // Validate rent
    if (!is_numeric($rent) || $rent <= 0) {
        sendResponse(false, 'Invalid rent amount');
    }
    
    // Validate gender
    if (!in_array($gender, ['male', 'female', 'any'])) {
        $gender = 'any';
    }
    
    // Validate date
    if (!empty($availableFrom)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $availableFrom);
        if (!$dateObj) {
            sendResponse(false, 'Invalid date format. Use YYYY-MM-DD');
        }
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        // Insert listing
        $stmt = $db->prepare("
            INSERT INTO listings (user_id, title, description, rent, address, city, gender, furnished, amenities, available_from)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $title,
            $description,
            $rent,
            $address,
            $city,
            $gender,
            $furnished,
            $amenities,
            $availableFrom
        ]);
        
        $listingId = $db->lastInsertId();
        
        // Insert images if provided
        if (!empty($images)) {
            $imageArray = json_decode($images, true);
            if (is_array($imageArray)) {
                $stmt = $db->prepare("
                    INSERT INTO listing_images (listing_id, image_path, is_primary, display_order)
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($imageArray as $index => $imagePath) {
                    $isPrimary = ($index === 0) ? 1 : 0;
                    $stmt->execute([$listingId, $imagePath, $isPrimary, $index + 1]);
                }
            }
        }
        
        sendResponse(true, 'Listing created successfully', ['listing_id' => $listingId]);
        
    } catch (PDOException $e) {
        logError('Create Listing Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to create listing');
    }
}

/**
 * Search listings with filters
 */
function handleSearch() {
    $city = sanitize(getParam('city', ''));
    $minRent = getParam('min', '');
    $maxRent = getParam('max', '');
    $gender = sanitize(getParam('gender', ''));
    $furnished = getParam('furnished');
    $search = sanitize(getParam('search', ''));
    $page = max(1, intval(getParam('page', 1)));
    $limit = LISTINGS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    try {
        $db = getDB();
        
        // Build query
        $where = ["l.is_active = 1"];
        $params = [];
        
        if (!empty($city)) {
            $where[] = "l.city LIKE ?";
            $params[] = "%$city%";
        }
        
        if (!empty($minRent) && is_numeric($minRent) && $minRent > 0) {
            $where[] = "l.rent >= ?";
            $params[] = $minRent;
        }
        
        if (!empty($maxRent) && is_numeric($maxRent) && $maxRent > 0) {
            $where[] = "l.rent <= ?";
            $params[] = $maxRent;
        }
        
        if (!empty($gender) && in_array($gender, ['male', 'female', 'any'])) {
            $where[] = "(l.gender = ? OR l.gender = 'any')";
            $params[] = $gender;
        }
        
        if ($furnished !== null) {
            $where[] = "l.furnished = ?";
            $params[] = $furnished ? 1 : 0;
        }
        
        if (!empty($search)) {
            $where[] = "(l.title LIKE ? OR l.description LIKE ? OR l.address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Count total results
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM listings l WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Get listings
        $query = "
            SELECT 
                l.id, l.title, l.rent, l.city, l.address, l.gender, l.furnished, l.amenities,
                l.available_from, l.created_at,
                u.name as landlord_name, u.phone as landlord_phone,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM listings l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE $whereClause
            ORDER BY l.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $listings = $stmt->fetchAll();
        
        sendResponse(true, 'Listings retrieved', [
            'listings' => $listings,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        
    } catch (PDOException $e) {
        logError('Search Error: ' . $e->getMessage());
        sendResponse(false, 'Search failed');
    }
}

/**
 * Get listing detail
 */
function handleDetail() {
    $id = intval(getParam('id'));
    
    if ($id <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        
        // Get listing details
        $stmt = $db->prepare("
            SELECT 
                l.*,
                u.name as landlord_name, u.email as landlord_email, u.phone as landlord_phone
            FROM listings l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.id = ? AND l.is_active = 1
        ");
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        
        if (!$listing) {
            sendResponse(false, 'Listing not found');
        }
        
        // Get images
        $stmt = $db->prepare("SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY display_order");
        $stmt->execute([$id]);
        $listing['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Increment view count
        $stmt = $db->prepare("UPDATE listings SET views = views + 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Check if favorited by current user
        $listing['is_favorite'] = false;
        if (isAuthenticated()) {
            $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
            $stmt->execute([getCurrentUserId(), $id]);
            $listing['is_favorite'] = $stmt->fetch() ? true : false;
        }
        
        sendResponse(true, 'Listing details retrieved', $listing);
        
    } catch (PDOException $e) {
        logError('Detail Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve listing');
    }
}

/**
 * Update listing
 */
function handleUpdate() {
    requireRole('landlord');
    
    $id = intval(getParam('id'));
    $title = sanitize(getParam('title'));
    $description = sanitize(getParam('description'));
    $rent = sanitize(getParam('rent'));
    $address = sanitize(getParam('address'));
    $city = sanitize(getParam('city'));
    $gender = sanitize(getParam('gender', 'any'));
    $furnished = getParam('furnished', 0) ? 1 : 0;
    $amenities = sanitize(getParam('amenities', ''));
    $availableFrom = sanitize(getParam('available_from'));
    
    if ($id <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        // Check ownership
        $stmt = $db->prepare("SELECT user_id FROM listings WHERE id = ?");
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        
        if (!$listing || $listing['user_id'] != $userId) {
            sendResponse(false, 'Unauthorized or listing not found');
        }
        
        // Update listing
        $stmt = $db->prepare("
            UPDATE listings 
            SET title = ?, description = ?, rent = ?, address = ?, city = ?, 
                gender = ?, furnished = ?, amenities = ?, available_from = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title, $description, $rent, $address, $city,
            $gender, $furnished, $amenities, $availableFrom, $id
        ]);
        
        sendResponse(true, 'Listing updated successfully');
        
    } catch (PDOException $e) {
        logError('Update Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to update listing');
    }
}

/**
 * Delete listing
 */
function handleDelete() {
    requireRole('landlord');
    
    $id = intval(getParam('id'));
    
    if ($id <= 0) {
        sendResponse(false, 'Invalid listing ID');
    }
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        // Check ownership
        $stmt = $db->prepare("SELECT user_id FROM listings WHERE id = ?");
        $stmt->execute([$id]);
        $listing = $stmt->fetch();
        
        if (!$listing || $listing['user_id'] != $userId) {
            sendResponse(false, 'Unauthorized or listing not found');
        }
        
        // Soft delete (set is_active = 0)
        $stmt = $db->prepare("UPDATE listings SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, 'Listing deleted successfully');
        
    } catch (PDOException $e) {
        logError('Delete Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to delete listing');
    }
}

/**
 * Get my listings (landlord)
 */
function handleMyListings() {
    requireRole('landlord');
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $stmt = $db->prepare("
            SELECT 
                l.id, l.title, l.rent, l.city, l.is_active, l.views, l.created_at,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail,
                (SELECT COUNT(*) FROM inquiries WHERE listing_id = l.id) as inquiry_count
            FROM listings l
            WHERE l.user_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$userId]);
        $listings = $stmt->fetchAll();
        
        sendResponse(true, 'Listings retrieved', $listings);
        
    } catch (PDOException $e) {
        logError('My Listings Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve listings');
    }
}

/**
 * Get latest listings
 */
function handleLatest() {
    $limit = min(12, intval(getParam('limit', 6)));
    
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                l.id, l.title, l.rent, l.city, l.gender, l.furnished,
                (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM listings l
            WHERE l.is_active = 1
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $listings = $stmt->fetchAll();
        
        sendResponse(true, 'Latest listings retrieved', $listings);
        
    } catch (PDOException $e) {
        logError('Latest Listings Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve listings');
    }
}
