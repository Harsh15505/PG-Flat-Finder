# PG/Flat Finder System - Setup Guide

## 📋 Prerequisites

Before setting up the project, ensure you have:

1. **XAMPP** (or similar) with:
   - Apache Web Server
   - MySQL/MariaDB (version 5.7+)
   - PHP 7.4 or higher

2. **Web Browser** (Chrome, Firefox, Edge recommended)

3. **Text Editor** (VS Code, Sublime Text, etc.)

---

## 🚀 Installation Steps

### Step 1: Extract Project Files

1. Extract the `pg-finder` folder to your XAMPP directory:
   - **Windows**: `C:\xampp\htdocs\`
   - **Linux**: `/opt/lampp/htdocs/`
   - **Mac**: `/Applications/XAMPP/htdocs/`

Your directory structure should be:
```
htdocs/
└── pg-finder/
    ├── frontend/
    ├── backend/
    ├── uploads/
    ├── database.sql
    └── README.md
```

### Step 2: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** server
3. Start **MySQL** database
4. Verify both are running (green indicators)

### Step 3: Create Database

**Method 1: Using phpMyAdmin (Recommended)**

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click on "New" in the left sidebar
3. Create database named: `pg_finder`
4. Select the database
5. Click on "Import" tab
6. Click "Choose File" and select `pg-finder/database.sql`
7. Click "Go" button
8. Wait for success message

**Method 2: Using MySQL Command Line**

```bash
# Open MySQL command line
mysql -u root -p

# Create database
CREATE DATABASE pg_finder;

# Use the database
USE pg_finder;

# Import SQL file
SOURCE C:/xampp/htdocs/pg-finder/database.sql;
```

### Step 4: Configure Database Connection

1. Open `backend/config.php` file
2. Update database credentials if needed:

```php
define('DB_HOST', 'localhost');      // Usually localhost
define('DB_NAME', 'pg_finder');      // Database name
define('DB_USER', 'root');           // Default XAMPP user
define('DB_PASS', '');               // Default XAMPP password is empty
```

### Step 5: Set Upload Directory Permissions

**Windows:**
- Right-click on `uploads` folder
- Properties → Security → Edit
- Give "Full Control" to Users

**Linux/Mac:**
```bash
chmod 755 uploads/
```

### Step 6: Access the Application

Open your browser and navigate to:
```
http://localhost/pg-finder/frontend/index.html
```

---

## 👤 Default Login Credentials

### Admin Account
- **Email**: admin@pgfinder.com
- **Password**: admin123

### Landlord Account
- **Email**: landlord@test.com
- **Password**: pass123

### Tenant Account
- **Email**: tenant@test.com
- **Password**: pass123

---

## 🧪 Testing the Application

### 1. Test User Registration
- Go to Register page
- Fill all required fields
- Submit form
- Should redirect to dashboard

### 2. Test Login
- Go to Login page
- Use demo credentials above
- Submit form
- Should redirect to dashboard

### 3. Test Search
- Go to Search page
- Apply filters (city, rent, gender)
- Click Search button
- View results

### 4. Test Listing Creation (Landlord)
- Login as landlord
- Go to "My Listings"
- Fill new listing form
- Upload images (optional)
- Submit form

### 5. Test Inquiry System
- Open any listing detail page
- Fill contact form
- Submit inquiry
- Check in landlord's dashboard

### 6. Test Favorites (Tenant)
- Login as tenant
- View any listing
- Click "Add to Favorites"
- Check dashboard for saved listings

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"
**Solution:**
- Verify MySQL is running in XAMPP
- Check database credentials in `config.php`
- Ensure database `pg_finder` exists

### Issue: "Page not found" or 404 errors
**Solution:**
- Verify Apache is running
- Check file paths are correct
- Clear browser cache

### Issue: Images not uploading
**Solution:**
- Check `uploads/` folder has write permissions
- Verify upload_max_filesize in php.ini (should be >= 5MB)
- Check file size is under 5MB

### Issue: Session/Login not working
**Solution:**
- Verify session_start() is enabled in PHP
- Check browser allows cookies
- Clear browser cookies and cache

### Issue: "Access denied" on admin panel
**Solution:**
- Ensure you're logged in with admin account
- Use: admin@pgfinder.com / admin123

---

## 📁 Project Structure Explained

```
pg-finder/
│
├── frontend/                 # All HTML/CSS/JS files
│   ├── index.html           # Homepage
│   ├── login.html           # Login page
│   ├── register.html        # Registration page
│   ├── search.html          # Search listings page
│   ├── listing.html         # Single listing detail
│   ├── dashboard.html       # User dashboard
│   ├── landlord.html        # Landlord panel
│   ├── admin.html           # Admin panel
│   ├── css/
│   │   └── style.css        # All styles
│   └── js/
│       └── main.js          # All JavaScript
│
├── backend/                  # All PHP API files
│   ├── config.php           # Configuration
│   ├── db.php               # Database connection
│   ├── utils.php            # Helper functions
│   ├── auth.php             # Authentication API
│   ├── listings.php         # Listings CRUD API
│   ├── favorites.php        # Favorites API
│   ├── inquiries.php        # Inquiries API
│   ├── admin.php            # Admin API
│   └── upload.php           # File upload handler
│
├── uploads/                  # User uploaded images
│
├── database.sql             # Database schema & sample data
│
└── README.md                # Documentation
```

---

## 🎯 Features Checklist

- ✅ User Registration & Login
- ✅ Role-based Access (Tenant/Landlord/Admin)
- ✅ Search with Filters
- ✅ Listing CRUD Operations
- ✅ Multiple Image Upload
- ✅ Favorites System
- ✅ Inquiry/Contact System
- ✅ Landlord Dashboard
- ✅ Tenant Dashboard
- ✅ Admin Panel
- ✅ Responsive Design
- ✅ Form Validation (Client & Server)
- ✅ Secure Password Hashing
- ✅ SQL Injection Prevention
- ✅ XSS Protection

---

## 📱 Browser Compatibility

Tested and working on:
- ✅ Google Chrome (Latest)
- ✅ Mozilla Firefox (Latest)
- ✅ Microsoft Edge (Latest)
- ✅ Safari (Latest)
- ✅ Mobile browsers

---

## 🔒 Security Features

1. **Password Security**: BCrypt hashing
2. **SQL Injection Prevention**: PDO prepared statements
3. **XSS Protection**: Input sanitization
4. **File Upload Security**: MIME type validation
5. **Session Management**: Secure session handling
6. **Input Validation**: Both client and server-side

---

## 📞 Support & Issues

If you encounter any issues:

1. Check error logs: `xampp/apache/logs/error.log`
2. Enable PHP errors in `config.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
3. Check browser console for JavaScript errors
4. Verify all files are in correct locations

---

## 🎓 For College Presentation

### Key Points to Highlight:

1. **Technology Stack**: Pure PHP, No frameworks
2. **Security**: Password hashing, prepared statements
3. **Architecture**: MVC-like separation
4. **Features**: Complete CRUD, file uploads, search
5. **Responsive Design**: Works on mobile devices
6. **User Roles**: Three different user types
7. **Real-world Application**: Solves actual problem

### Demo Flow:

1. Show homepage and search
2. Register new user
3. Login as landlord
4. Create new listing with images
5. Login as tenant
6. Search and view listings
7. Send inquiry
8. Add to favorites
9. Show admin panel

---

## 📊 Database Information

**Total Tables**: 5
1. `users` - User accounts
2. `listings` - Property listings
3. `listing_images` - Property images
4. `favorites` - User favorites
5. `inquiries` - Contact requests

**Sample Data Included**:
- 3 Users (admin, landlord, tenant)
- 3 Sample Listings
- 4 Sample Images

---

## ✅ Pre-Deployment Checklist

Before presenting:

- [ ] XAMPP is running
- [ ] Database is imported
- [ ] All files are in correct location
- [ ] Can access homepage
- [ ] Can login with demo accounts
- [ ] Can create new listing
- [ ] Can upload images
- [ ] Search is working
- [ ] Favorites working
- [ ] Inquiries working
- [ ] Admin panel accessible

---

## 🎉 You're All Set!

Your PG/Flat Finder system is ready to use. Good luck with your presentation!

For any questions, refer back to this guide.
