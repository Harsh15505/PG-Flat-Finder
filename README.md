# 🏠 PG/Flat Finder System

A modern, full-stack web application to help students and working professionals find PG accommodations and rental flats. Built with vanilla JavaScript, PHP, and MySQL.

![License](https://img.shields.io/badge/license-MIT-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

## ✨ Features

### 🔐 User Management
- **Three User Roles**: Tenant, Landlord, Admin
- Secure authentication with password hashing (BCrypt)
- Session-based login system
- Role-based access control

### 🏘️ Listing Management
- Create, read, update, delete (CRUD) operations
- Multiple image uploads per listing (max 5)
- Rich property details (rent, location, amenities, gender preference)
- Active/inactive status control
- View counter tracking

### 🔍 Advanced Search
- City-based search
- Price range filters (min/max rent)
- Gender preference filtering
- Furnished/unfurnished toggle
- Full-text search in titles and descriptions
- Pagination support

### ❤️ Favorites System
- Save favorite listings
- Quick access from dashboard
- One-click toggle

### 📧 Inquiry System
- Direct landlord contact form
- Status tracking (pending/responded/closed)
- Tenant inquiry history
- Landlord inquiry management

### 👨‍💼 Admin Panel
- User management (activate/deactivate)
- Listing oversight
- System statistics dashboard
- Complete platform monitoring

### ⚡ Performance Features
- Lazy loading images
- Connection speed detection
- Hardware-accelerated rendering
- Optimized image URLs
- Loading placeholders with animations

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend**: Core PHP with PDO
- **Database**: MySQL/MariaDB
- **Server**: Apache (XAMPP)
- **Security**: BCrypt, Prepared Statements, Input Sanitization

## 📋 Prerequisites

- XAMPP (or similar stack) with:
  - Apache Web Server
  - MySQL/MariaDB 5.7+
  - PHP 7.4+
- Modern web browser (Chrome, Firefox, Edge)
- 100MB free disk space

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <your-repo-url>
cd pg-finder
```

### 2. Setup XAMPP
- Copy the `pg-finder` folder to `C:\xampp\htdocs\`
- Start Apache and MySQL from XAMPP Control Panel

### 3. Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create new database: `pg_finder`
3. Import SQL file: `database_enhanced.sql`

### 4. Configure Database (if needed)
Edit `backend/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pg_finder');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password
```

### 5. Launch Application
Open browser and navigate to:
```
http://localhost/pg-finder/frontend/index.html
```

## 🔑 Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@pgfinder.com | admin123 |
| Landlord | landlord@test.com | pass123 |
| Tenant | tenant@test.com | pass123 |

## 📁 Project Structure

```
pg-finder/
├── frontend/              # Client-side code
│   ├── index.html        # Homepage
│   ├── login.html        # Login page
│   ├── register.html     # Registration
│   ├── search.html       # Search listings
│   ├── listing.html      # Listing details
│   ├── dashboard.html    # User dashboard
│   ├── landlord.html     # Landlord panel
│   ├── admin.html        # Admin panel
│   ├── css/
│   │   └── style.css     # All styles
│   └── js/
│       ├── main.js       # Core application logic
│       └── image-preloader.js  # Performance optimization
├── backend/              # Server-side APIs
│   ├── config.php        # Configuration
│   ├── db.php           # Database connection
│   ├── utils.php        # Helper functions
│   ├── auth.php         # Authentication
│   ├── listings.php     # Listings CRUD
│   ├── favorites.php    # Favorites management
│   ├── inquiries.php    # Inquiry system
│   ├── admin.php        # Admin operations
│   └── upload.php       # File uploads
├── uploads/             # User uploaded images
└── database_enhanced.sql # Database schema + sample data
```

## 🔒 Security Features

- ✅ Password hashing using BCrypt
- ✅ SQL injection prevention with PDO prepared statements
- ✅ XSS protection through input sanitization
- ✅ CSRF protection via session validation
- ✅ File upload validation (type, size, MIME)
- ✅ Role-based access control
- ✅ Server-side and client-side validation

## 🎨 Key Highlights

- **Clean Architecture**: Separation of concerns with modular code
- **No Frameworks**: Pure vanilla JavaScript and PHP
- **Responsive Design**: Mobile-first approach
- **Modern UI**: Clean, intuitive interface with smooth animations
- **Performance Optimized**: Lazy loading, caching, hardware acceleration
- **Well Documented**: Comprehensive inline comments
- **Production Ready**: Error handling, logging, validation

## 🧪 Testing Checklist

- [x] User registration (tenant/landlord)
- [x] User login/logout
- [x] Landlord adds listing with images
- [x] Tenant searches with filters
- [x] Tenant favorites/unfavorites listings
- [x] Tenant sends inquiries
- [x] Landlord views inquiries
- [x] Admin manages users/listings
- [x] Authorization checks (role-based)
- [x] Input validation (client & server)
- [x] Image upload validation
- [x] Responsive design (mobile/tablet/desktop)

## 📱 Browser Compatibility

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 77+ | ✅ Fully Supported |
| Firefox | 75+ | ✅ Fully Supported |
| Edge | 79+ | ✅ Fully Supported |
| Safari | 13.4+ | ✅ Fully Supported |
| Opera | 64+ | ✅ Fully Supported |

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check credentials in `backend/config.php`
- Ensure database exists

### Images Not Loading
- Check internet connection (for Unsplash URLs)
- Verify `uploads/` folder has write permissions
- Clear browser cache

### Session/Login Issues
- Enable cookies in browser
- Clear browser cache and cookies
- Verify PHP sessions are enabled

---

**⭐ Star this repo if you find it helpful!**
