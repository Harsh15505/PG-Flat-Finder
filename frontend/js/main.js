/**
 * PG/Flat Finder - Main JavaScript
 * Contains all frontend logic for the application
 */

// Configuration
const API_BASE_URL = '../backend';
const UPLOADS_URL = '../uploads';

// Global state
let currentUser = null;
let isAuthenticated = false;

// ===== UTILITY FUNCTIONS =====

/**
 * Make API request
 */
async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE_URL}/${endpoint}`;
    
    try {
        const response = await fetch(url, {
            ...options,
            credentials: 'include'
        });
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        return {
            success: false,
            message: 'Network error. Please try again.'
        };
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertTypes = {
        success: 'alert-success',
        error: 'alert-error',
        info: 'alert-info',
        warning: 'alert-warning'
    };
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertTypes[type]}`;
    alert.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => {
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Show loading overlay
 */
function showLoading() {
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading"></div>';
        document.body.appendChild(overlay);
    }
    overlay.classList.add('active');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return `₹${parseFloat(amount).toLocaleString('en-IN')}`;
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Get query parameter
 */
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Validate email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validate phone
 */
function validatePhone(phone) {
    const re = /^[6-9]\d{9}$/;
    return re.test(phone.replace(/[^0-9]/g, ''));
}

/**
 * Sanitize input
 */
function sanitize(input) {
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// ===== AUTHENTICATION =====

/**
 * Check authentication status
 */
async function checkAuth() {
    const response = await apiRequest('auth.php?action=check');
    if (response.success) {
        isAuthenticated = true;
        currentUser = response.data;
        updateNavigation();
        return true;
    }
    return false;
}

/**
 * Update navigation based on auth status
 */
function updateNavigation() {
    const navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;
    
    if (isAuthenticated && currentUser) {
        // Admin gets only Admin Panel and Logout
        if (currentUser.role === 'admin') {
            navLinks.innerHTML = `
                <li><a href="admin.html">Admin Panel</a></li>
                <li><span class="text-muted">Hi, ${sanitize(currentUser.name)}</span></li>
                <li><button class="btn btn-sm btn-danger" onclick="logout()">Logout</button></li>
            `;
        } else {
            navLinks.innerHTML = `
                <li><a href="index.html">Home</a></li>
                <li><a href="search.html">Search</a></li>
                ${currentUser.role === 'landlord' ? '<li><a href="landlord.html">My Listings</a></li>' : ''}
                <li><a href="dashboard.html">Dashboard</a></li>
                <li><span class="text-muted">Hi, ${sanitize(currentUser.name)}</span></li>
                <li><button class="btn btn-sm btn-danger" onclick="logout()">Logout</button></li>
            `;
        }
    } else {
        navLinks.innerHTML = `
            <li><a href="index.html">Home</a></li>
            <li><a href="search.html">Search</a></li>
            <li><a href="login.html" class="btn btn-primary btn-sm">Login</a></li>
            <li><a href="register.html" class="btn btn-outline btn-sm">Register</a></li>
        `;
    }
}

/**
 * Handle registration
 */
async function handleRegister(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validation
    const name = formData.get('name').trim();
    const email = formData.get('email').trim();
    const phone = formData.get('phone').trim();
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    const role = formData.get('role');
    
    if (!name || !email || !phone || !password) {
        showAlert('All fields are required', 'error');
        return;
    }
    
    if (!validateEmail(email)) {
        showAlert('Invalid email format', 'error');
        return;
    }
    
    if (!validatePhone(phone)) {
        showAlert('Invalid phone number. Must be 10 digits starting with 6-9', 'error');
        return;
    }
    
    if (password.length < 6) {
        showAlert('Password must be at least 6 characters', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('Passwords do not match', 'error');
        return;
    }
    
    showLoading();
    
    const params = new URLSearchParams({
        action: 'register',
        name,
        email,
        phone,
        password,
        role
    });
    
    const response = await apiRequest(`auth.php?${params}`);
    hideLoading();
    
    if (response.success) {
        showAlert('Registration successful! Redirecting...', 'success');
        setTimeout(() => {
            // Check user role and redirect accordingly
            const user = response.data || response.user;
            if (user && user.role === 'admin') {
                window.location.href = 'admin.html';
            } else if (user && user.role === 'landlord') {
                window.location.href = 'landlord.html';
            } else {
                window.location.href = 'dashboard.html';
            }
        }, 1500);
    } else {
        showAlert(response.message, 'error');
    }
}

/**
 * Handle login
 */
async function handleLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const email = formData.get('email').trim();
    const password = formData.get('password');
    
    if (!email || !password) {
        showAlert('Email and password are required', 'error');
        return;
    }
    
    if (!validateEmail(email)) {
        showAlert('Invalid email format', 'error');
        return;
    }
    
    showLoading();
    
    const params = new URLSearchParams({
        action: 'login',
        email,
        password
    });
    
    const response = await apiRequest(`auth.php?${params}`);
    hideLoading();
    
    if (response.success) {
        showAlert('Login successful! Redirecting...', 'success');
        setTimeout(() => {
            // Check user role and redirect accordingly
            const user = response.data || response.user;
            if (user && user.role === 'admin') {
                window.location.href = 'admin.html';
            } else if (user && user.role === 'landlord') {
                window.location.href = 'landlord.html';
            } else {
                window.location.href = 'dashboard.html';
            }
        }, 1500);
    } else {
        showAlert(response.message, 'error');
    }
}

/**
 * Logout
 */
async function logout() {
    showLoading();
    const response = await apiRequest('auth.php?action=logout');
    hideLoading();
    
    if (response.success) {
        showAlert('Logged out successfully', 'success');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    }
}

/**
 * Require authentication
 */
async function requireAuth() {
    const authenticated = await checkAuth();
    if (!authenticated) {
        showAlert('Please login to access this page', 'warning');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 1500);
    }
}

// ===== LISTINGS =====

/**
 * Load latest listings
 */
async function loadLatestListings(limit = 6) {
    const response = await apiRequest(`listings.php?action=latest&limit=${limit}`);
    
    if (response.success && response.data) {
        displayListings(response.data);
    }
}

/**
 * Search listings
 */
async function searchListings(filters = {}) {
    showLoading();
    
    const params = new URLSearchParams({
        action: 'search',
        ...filters
    });
    
    const response = await apiRequest(`listings.php?${params}`);
    hideLoading();
    
    if (response.success && response.data) {
        displayListings(response.data.listings);
        displayPagination(response.data);
    } else {
        showAlert('No listings found', 'info');
        document.getElementById('listingsGrid').innerHTML = '<p class="text-center">No listings found</p>';
    }
}

/**
 * Display listings
 */
function displayListings(listings) {
    const container = document.getElementById('listingsGrid');
    if (!container) return;
    
    if (!listings || listings.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No listings available</p>';
        return;
    }
    
    container.innerHTML = listings.map(listing => {
        // Handle both web URLs and local paths
        const imageSrc = listing.thumbnail 
            ? (listing.thumbnail.startsWith('http') ? listing.thumbnail : '../' + listing.thumbnail)
            : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800';
            
        return `
        <div class="listing-card" onclick="viewListing(${listing.id})">
            <div class="listing-card-image-wrapper">
                <img src="${imageSrc}" 
                     alt="${sanitize(listing.title)}" 
                     class="listing-card-image"
                     loading="lazy"
                     width="400"
                     height="200"
                     onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=200&fit=crop'">
            </div>
            <div class="listing-card-body">
                <h3 class="listing-card-title">${sanitize(listing.title)}</h3>
                <div class="listing-card-price">${formatCurrency(listing.rent)}/month</div>
                <div class="listing-card-location">
                    <span>📍</span>
                    <span>${sanitize(listing.city)}</span>
                </div>
                <div class="listing-card-meta">
                    <span class="meta-item">${listing.gender === 'any' ? 'Any Gender' : listing.gender}</span>
                    <span class="meta-item">${listing.furnished ? '🛋 Furnished' : 'Unfurnished'}</span>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

/**
 * View listing details
 */
function viewListing(id) {
    window.location.href = `listing.html?id=${id}`;
}

/**
 * Load listing detail
 */
async function loadListingDetail(id) {
    showLoading();
    const response = await apiRequest(`listings.php?action=detail&id=${id}`);
    hideLoading();
    
    if (response.success && response.data) {
        displayListingDetail(response.data);
    } else {
        showAlert('Listing not found', 'error');
        setTimeout(() => {
            window.location.href = 'search.html';
        }, 2000);
    }
}

/**
 * Display listing detail
 */
function displayListingDetail(listing) {
    const container = document.getElementById('listingDetail');
    if (!container) return;
    
    // Handle both web URLs and local paths for images
    const processImageUrl = (img) => {
        if (!img) return 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop&q=80';
        return img.startsWith('http') ? img : '../' + img;
    };
    
    const images = listing.images && listing.images.length > 0 
        ? listing.images.map(processImageUrl)
        : ['https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop&q=80'];
    
    const amenities = listing.amenities ? listing.amenities.split(',') : [];
    
    // Check if current user is the owner of this listing
    const isOwner = isAuthenticated && currentUser && listing.user_id == currentUser.user_id;
    
    // Only tenants can send inquiries (not landlords or admins)
    const canSendInquiry = isAuthenticated && currentUser && currentUser.role === 'tenant' && !isOwner;
    const shouldShowContactForm = !isAuthenticated || canSendInquiry;
    
    container.innerHTML = `
        <div class="listing-detail">
            <div class="listing-gallery">
                <img src="${images[0]}" 
                     alt="${sanitize(listing.title)}" 
                     class="gallery-main-image" 
                     id="mainImage"
                     loading="eager"
                     onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop&q=80'">
            </div>
            ${images.length > 1 ? `
                <div class="gallery-thumbnails">
                    ${images.map((img, index) => `
                        <img src="${img}" 
                             alt="Image ${index + 1}" 
                             class="gallery-thumbnail ${index === 0 ? 'active' : ''}" 
                             onclick="changeMainImage('${img}', this)"
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop&q=80'">
                    `).join('')}
                </div>
            ` : ''}
            
            <div class="listing-content">
                <div class="listing-header">
                    <div>
                        <h1>${sanitize(listing.title)}</h1>
                        <div class="listing-card-price">${formatCurrency(listing.rent)}/month</div>
                        <div class="listing-card-location">
                            <span>📍</span>
                            <span>${sanitize(listing.address)}, ${sanitize(listing.city)}</span>
                        </div>
                    </div>
                    <div class="listing-actions">
                        ${isAuthenticated ? `
                            <button class="favorite-btn ${listing.is_favorite ? 'active' : ''}" onclick="toggleFavorite(${listing.id}, this)">
                                ❤ ${listing.is_favorite ? 'Favorited' : 'Add to Favorites'}
                            </button>
                        ` : ''}
                    </div>
                </div>
                
                <div class="listing-details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Gender Preference</span>
                        <span class="detail-value">${listing.gender === 'any' ? 'Any' : listing.gender}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Furnished</span>
                        <span class="detail-value">${listing.furnished ? 'Yes' : 'No'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Available From</span>
                        <span class="detail-value">${listing.available_from ? formatDate(listing.available_from) : 'Immediately'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Posted On</span>
                        <span class="detail-value">${formatDate(listing.created_at)}</span>
                    </div>
                </div>
                
                ${listing.description ? `
                    <div>
                        <h3>Description</h3>
                        <p class="listing-description">${sanitize(listing.description)}</p>
                    </div>
                ` : ''}
                
                ${amenities.length > 0 ? `
                    <div>
                        <h3>Amenities</h3>
                        <div class="amenities-list">
                            ${amenities.map(amenity => `<span class="amenity-badge">${sanitize(amenity.trim())}</span>`).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${shouldShowContactForm ? `
                    <div class="contact-form">
                        <h3>Contact Landlord</h3>
                        ${!isAuthenticated ? '<p class="alert alert-info">Please <a href="login.html">login</a> as a tenant to send inquiries.</p>' : ''}
                        <form onsubmit="sendInquiry(event, ${listing.id})">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" required value="${isAuthenticated ? sanitize(currentUser.name) : ''}">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required value="${isAuthenticated ? sanitize(currentUser.email) : ''}">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" required pattern="[6-9][0-9]{9}">
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="message" rows="4" placeholder="I am interested in this property..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Send Inquiry</button>
                        </form>
                    </div>
                ` : isOwner ? '<div class="alert alert-info" style="margin-top: 2rem;">This is your own listing.</div>' : '<div class="alert alert-info" style="margin-top: 2rem;">Only tenants can send inquiries to landlords.</div>'}
            </div>
        </div>
    `;
}

/**
 * Change main gallery image
 */
function changeMainImage(src, thumbnail) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = src;
    }
    
    document.querySelectorAll('.gallery-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    thumbnail.classList.add('active');
}

/**
 * Toggle favorite
 */
async function toggleFavorite(listingId, button) {
    if (!isAuthenticated) {
        showAlert('Please login to add favorites', 'warning');
        return;
    }
    
    const params = new URLSearchParams({
        action: 'toggle',
        listing_id: listingId
    });
    
    const response = await apiRequest(`favorites.php?${params}`);
    
    if (response.success) {
        button.classList.toggle('active');
        button.textContent = response.data.is_favorite ? '❤ Favorited' : '❤ Add to Favorites';
        showAlert(response.message, 'success');
    } else {
        showAlert(response.message, 'error');
    }
}

/**
 * Send inquiry
 */
async function sendInquiry(event, listingId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const name = formData.get('name').trim();
    const email = formData.get('email').trim();
    const phone = formData.get('phone').trim();
    const message = formData.get('message').trim();
    
    if (!validateEmail(email)) {
        showAlert('Invalid email format', 'error');
        return;
    }
    
    if (!validatePhone(phone)) {
        showAlert('Invalid phone number', 'error');
        return;
    }
    
    showLoading();
    
    const params = new URLSearchParams({
        action: 'send',
        listing_id: listingId,
        name,
        email,
        phone,
        message
    });
    
    const response = await apiRequest(`inquiries.php?${params}`);
    hideLoading();
    
    if (response.success) {
        showAlert(response.message, 'success');
        form.reset();
    } else {
        showAlert(response.message, 'error');
    }
}

/**
 * Handle search form
 */
function handleSearchForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const filters = {
        city: formData.get('city') || '',
        min: formData.get('min_rent') || '',
        max: formData.get('max_rent') || '',
        gender: formData.get('gender') || '',
        furnished: formData.get('furnished') === 'on' ? 1 : '',
        search: formData.get('search') || ''
    };
    
    // Validate budget
    if (filters.min && filters.max && parseInt(filters.min) > parseInt(filters.max)) {
        showAlert('Minimum rent cannot be greater than maximum rent', 'error');
        return;
    }
    
    searchListings(filters);
}

// ===== LANDLORD FUNCTIONS =====

/**
 * Handle create listing
 */
async function handleCreateListing(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate required fields
    const title = formData.get('title').trim();
    const rent = formData.get('rent');
    const address = formData.get('address').trim();
    const city = formData.get('city').trim();
    
    if (!title || !rent || !address || !city) {
        showAlert('Please fill all required fields', 'error');
        return;
    }
    
    if (rent <= 0) {
        showAlert('Please enter a valid rent amount', 'error');
        return;
    }
    
    // Get selected amenities
    const amenities = [];
    document.querySelectorAll('input[name="amenities[]"]:checked').forEach(checkbox => {
        amenities.push(checkbox.value);
    });
    
    // Upload images first
    const imageFiles = document.getElementById('images').files;
    let uploadedImages = [];
    
    if (imageFiles.length > 0) {
        showLoading();
        const imageFormData = new FormData();
        for (let i = 0; i < imageFiles.length; i++) {
            imageFormData.append('images[]', imageFiles[i]);
        }
        
        const uploadResponse = await fetch(`${API_BASE_URL}/upload.php`, {
            method: 'POST',
            body: imageFormData,
            credentials: 'include'
        });
        
        const uploadResult = await uploadResponse.json();
        hideLoading();
        
        if (uploadResult.success) {
            uploadedImages = uploadResult.data.files.map(file => file.path);
        } else {
            showAlert('Image upload failed: ' + uploadResult.message, 'error');
            return;
        }
    }
    
    // Create listing
    showLoading();
    
    const params = new URLSearchParams({
        action: 'create',
        title,
        description: formData.get('description'),
        rent,
        address,
        city,
        gender: formData.get('gender'),
        furnished: formData.get('furnished') === 'on' ? 1 : 0,
        amenities: amenities.join(','),
        available_from: formData.get('available_from'),
        images: JSON.stringify(uploadedImages)
    });
    
    const response = await apiRequest(`listings.php?${params}`);
    hideLoading();
    
    if (response.success) {
        showAlert('Listing created successfully!', 'success');
        form.reset();
        setTimeout(() => {
            window.location.href = 'landlord.html';
        }, 1500);
    } else {
        showAlert(response.message, 'error');
    }
}

/**
 * Load my listings
 */
async function loadMyListings() {
    showLoading();
    const response = await apiRequest('listings.php?action=my-listings');
    hideLoading();
    
    if (response.success && response.data) {
        displayMyListings(response.data);
    }
}

/**
 * Display my listings
 */
function displayMyListings(listings) {
    const container = document.getElementById('myListings');
    if (!container) return;
    
    if (!listings || listings.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">You have no listings yet</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Rent</th>
                        <th>City</th>
                        <th>Views</th>
                        <th>Inquiries</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${listings.map(listing => {
                        // Handle both web URLs and local paths
                        const imageSrc = listing.thumbnail 
                            ? (listing.thumbnail.startsWith('http') ? listing.thumbnail : '../' + listing.thumbnail)
                            : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400';
                        
                        return `
                        <tr>
                            <td><img src="${imageSrc}" alt="${sanitize(listing.title)}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400'"></td>
                            <td>${sanitize(listing.title)}</td>
                            <td>${formatCurrency(listing.rent)}</td>
                            <td>${sanitize(listing.city)}</td>
                            <td>${listing.views || 0}</td>
                            <td>${listing.inquiry_count || 0}</td>
                            <td><span class="badge ${listing.is_active ? 'badge-success' : 'badge-danger'}">${listing.is_active ? 'Active' : 'Inactive'}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewListing(${listing.id})">View</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteListing(${listing.id})">Delete</button>
                            </td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
}

/**
 * Delete listing
 */
async function deleteListing(id) {
    if (!confirm('Are you sure you want to delete this listing?')) {
        return;
    }
    
    showLoading();
    const response = await apiRequest(`listings.php?action=delete&id=${id}`);
    hideLoading();
    
    if (response.success) {
        showAlert('Listing deleted successfully', 'success');
        loadMyListings();
    } else {
        showAlert(response.message, 'error');
    }
}

// ===== DASHBOARD FUNCTIONS =====

/**
 * Load dashboard data
 */
async function loadDashboard() {
    if (!isAuthenticated) return;
    
    if (currentUser.role === 'tenant') {
        await loadFavorites();
        await loadMyInquiries();
    } else if (currentUser.role === 'landlord') {
        await loadMyListings();
        await loadInquiries();
    }
}

/**
 * Load favorites
 */
async function loadFavorites() {
    const response = await apiRequest('favorites.php?action=list');
    
    if (response.success && response.data) {
        displayFavorites(response.data);
    }
}

/**
 * Display favorites
 */
function displayFavorites(favorites) {
    const container = document.getElementById('favoritesList');
    if (!container) return;
    
    if (!favorites || favorites.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No favorites yet</p>';
        return;
    }
    
    container.innerHTML = favorites.map(fav => {
        // Handle both web URLs and local paths
        const imageSrc = fav.thumbnail 
            ? (fav.thumbnail.startsWith('http') ? fav.thumbnail : '../' + fav.thumbnail)
            : 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800';
            
        return `
        <div class="listing-card" onclick="viewListing(${fav.id})">
            <div class="listing-card-image-wrapper">
                <img src="${imageSrc}" 
                     alt="${sanitize(fav.title)}" 
                     class="listing-card-image"
                     loading="lazy"
                     width="400"
                     height="200"
                     onerror="this.src='https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=200&fit=crop'">
            </div>
            <div class="listing-card-body">
                <h3 class="listing-card-title">${sanitize(fav.title)}</h3>
                <div class="listing-card-price">${formatCurrency(fav.rent)}/month</div>
                <div class="listing-card-location">
                    <span>📍</span>
                    <span>${sanitize(fav.city)}</span>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

/**
 * Load my inquiries
 */
async function loadMyInquiries() {
    const response = await apiRequest('inquiries.php?action=my-inquiries');
    
    if (response.success && response.data) {
        displayMyInquiries(response.data);
    }
}

/**
 * Display my inquiries
 */
function displayMyInquiries(inquiries) {
    const container = document.getElementById('inquiriesList');
    if (!container) return;
    
    if (!inquiries || inquiries.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No inquiries sent yet</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Listing</th>
                        <th>Message</th>
                        <th>Landlord</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${inquiries.map(inq => `
                        <tr>
                            <td><a href="listing.html?id=${inq.listing_id}">${sanitize(inq.listing_title)}</a></td>
                            <td>${sanitize(inq.message || 'No message')}</td>
                            <td>${sanitize(inq.landlord_name)}<br>${inq.landlord_phone}</td>
                            <td><span class="badge badge-${inq.status === 'pending' ? 'warning' : 'success'}">${inq.status}</span></td>
                            <td>${formatDate(inq.created_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

/**
 * Load inquiries (landlord)
 */
async function loadInquiries() {
    const response = await apiRequest('inquiries.php?action=list');
    
    if (response.success && response.data) {
        displayInquiries(response.data);
    } else {
        console.error('Failed to load inquiries:', response.message);
        const container = document.getElementById('landlordInquiriesList') || document.getElementById('inquiriesList');
        if (container) {
            container.innerHTML = `<p class="text-center text-muted">${response.message || 'Failed to load inquiries'}</p>`;
        }
    }
}

/**
 * Display inquiries
 */
function displayInquiries(inquiries) {
    // Check for both landlord and regular inquiries container
    const container = document.getElementById('landlordInquiriesList') || document.getElementById('inquiriesList');
    if (!container) {
        console.error('Inquiries container not found');
        return;
    }
    
    if (!inquiries || inquiries.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">No inquiries received yet</p>';
        return;
    }
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Listing</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${inquiries.map(inq => `
                        <tr>
                            <td><a href="listing.html?id=${inq.listing_id}">${sanitize(inq.listing_title)}</a></td>
                            <td>${sanitize(inq.name)}</td>
                            <td>${sanitize(inq.email)}<br>${inq.phone}</td>
                            <td>${sanitize(inq.message || 'No message')}</td>
                            <td><span class="badge badge-${inq.status === 'pending' ? 'warning' : 'success'}">${inq.status}</span></td>
                            <td>${formatDate(inq.created_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// ===== MOBILE MENU =====

/**
 * Toggle mobile menu
 */
function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
        navLinks.classList.toggle('active');
    }
}

// ===== PAGE INITIALIZATION =====

/**
 * Initialize page
 */
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication
    await checkAuth();
    
    // Page-specific initialization
    const path = window.location.pathname;
    const page = path.substring(path.lastIndexOf('/') + 1);
    
    switch (page) {
        case 'index.html':
        case '':
            loadLatestListings(6);
            break;
        
        case 'search.html':
            searchListings();
            break;
        
        case 'listing.html':
            const listingId = getQueryParam('id');
            if (listingId) {
                loadListingDetail(listingId);
            }
            break;
        
        case 'dashboard.html':
            await requireAuth();
            // Dashboard data loading is now handled in dashboard.html after role-based UI switch
            break;
        
        case 'landlord.html':
            await requireAuth();
            if (currentUser.role !== 'landlord') {
                showAlert('Access denied. Landlord account required.', 'error');
                setTimeout(() => window.location.href = 'index.html', 2000);
            } else {
                loadMyListings();
                loadInquiries(); // Load inquiries for landlord
            }
            break;
    }
});

// Export functions to global scope
window.handleRegister = handleRegister;
window.handleLogin = handleLogin;
window.logout = logout;
window.viewListing = viewListing;
window.changeMainImage = changeMainImage;
window.toggleFavorite = toggleFavorite;
window.sendInquiry = sendInquiry;
window.handleSearchForm = handleSearchForm;
window.handleCreateListing = handleCreateListing;
window.deleteListing = deleteListing;
window.toggleMobileMenu = toggleMobileMenu;
