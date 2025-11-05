<?php
/**
 * Authentication Handler
 * Handles user registration, login, and logout
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$action = getParam('action');

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheckAuth();
        break;
    case 'profile':
        handleGetProfile();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

/**
 * Handle user registration
 */
function handleRegister() {
    $name = sanitize(getParam('name'));
    $email = sanitize(getParam('email'));
    $phone = sanitize(getParam('phone'));
    $password = getParam('password');
    $role = sanitize(getParam('role', 'tenant'));
    
    // Validate required fields
    $missing = validateRequired(['name', 'email', 'phone', 'password'], [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $password
    ]);
    
    if (!empty($missing)) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    // Validate email
    if (!validateEmail($email)) {
        sendResponse(false, 'Invalid email format');
    }
    
    // Validate phone
    if (!validatePhone($phone)) {
        sendResponse(false, 'Invalid phone number. Must be 10 digits starting with 6-9');
    }
    
    // Validate password length
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        sendResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
    }
    
    // Validate role
    if (!in_array($role, ['tenant', 'landlord'])) {
        $role = 'tenant';
    }
    
    try {
        $db = getDB();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(false, 'Email already registered');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword, $role]);
        
        $userId = $db->lastInsertId();
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        
        sendResponse(true, 'Registration successful', [
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]);
        
    } catch (PDOException $e) {
        logError('Registration Error: ' . $e->getMessage());
        sendResponse(false, 'Registration failed. Please try again.');
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    $email = sanitize(getParam('email'));
    $password = getParam('password');
    
    // Validate required fields
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email and password are required');
    }
    
    // Validate email format
    if (!validateEmail($email)) {
        sendResponse(false, 'Invalid email format');
    }
    
    try {
        $db = getDB();
        
        // Get user by email
        $stmt = $db->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse(false, 'Invalid email or password');
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            sendResponse(false, 'Account is deactivated. Please contact admin.');
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            sendResponse(false, 'Invalid email or password');
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        sendResponse(true, 'Login successful', [
            'user_id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
        
    } catch (PDOException $e) {
        logError('Login Error: ' . $e->getMessage());
        sendResponse(false, 'Login failed. Please try again.');
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    session_destroy();
    sendResponse(true, 'Logged out successfully');
}

/**
 * Check if user is authenticated
 */
function handleCheckAuth() {
    if (isAuthenticated()) {
        sendResponse(true, 'Authenticated', [
            'user_id' => getCurrentUserId(),
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => getCurrentUserRole()
        ]);
    } else {
        sendResponse(false, 'Not authenticated');
    }
}

/**
 * Get user profile
 */
function handleGetProfile() {
    requireAuth();
    
    try {
        $db = getDB();
        $userId = getCurrentUserId();
        
        $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            sendResponse(true, 'Profile retrieved', $user);
        } else {
            sendResponse(false, 'User not found');
        }
        
    } catch (PDOException $e) {
        logError('Profile Error: ' . $e->getMessage());
        sendResponse(false, 'Failed to retrieve profile');
    }
}
