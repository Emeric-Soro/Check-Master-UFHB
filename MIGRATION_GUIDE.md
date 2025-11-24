# CheckMaster UFHB - Migration Documentation

## Overview
CheckMaster is a thesis defense management system for Université Félix Houphouët-Boigny (UFHB). This document describes the migration from Docker-based development to shared hosting deployment.

## Migration Summary

### What Changed
- ✅ Removed Docker dependencies
- ✅ Restructured project for shared hosting (cPanel/LWS compatible)
- ✅ Migrated from Gotenberg to mPDF for PDF generation
- ✅ Implemented AltoRouter for clean URLs
- ✅ Migrated database configuration to use phpdotenv
- ✅ Updated all asset paths to absolute URLs
- ✅ Added security layers (.htaccess, Hashids for ID obfuscation)

### Project Structure (New)
```
Check-Master-UFHB/
├── app/
│   ├── config/
│   │   ├── database.php (uses phpdotenv)
│   │   └── email.php
│   ├── controllers/
│   ├── models/
│   └── utils/
│       ├── DocumentGeneratorService.php (mPDF wrapper)
│       ├── EmailService.php
│       ├── HashidsHelper.php
│       └── ReceiptUtils.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── public/
│   ├── layout.php (main app layout)
│   ├── page_connexion.php (login page)
│   └── ...
├── ressources/
│   ├── routes/
│   ├── uploads/
│   └── views/
│       └── pdf/ (HTML templates for PDFs)
├── vendor/ (Composer dependencies)
├── .env (environment configuration)
├── .htaccess (Apache rewrite rules & security)
├── index.php (main application router)
├── landing.php (public landing page)
└── composer.json
```

## Installation Instructions

### Requirements
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled
- Composer

### Step 1: Clone Repository
```bash
git clone https://github.com/Emeric-Soro/Check-Master-UFHB.git
cd Check-Master-UFHB
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Configure Environment
Copy and edit the `.env` file:
```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=soutenance_manager
DB_USER=your_db_user
DB_PASS=your_db_password
DB_CHARSET=utf8

# Application Configuration
APP_ENV=production
APP_DEBUG=false

# Hashids Configuration (change to a random secret)
HASHIDS_SALT=your-secret-random-salt-change-this
```

### Step 4: Import Database
Import the SQL schema:
```bash
mysql -u your_db_user -p soutenance_manager < soutenance_manager.sql
```

### Step 5: Set Permissions
Ensure the uploads directory is writable:
```bash
chmod 755 ressources/uploads
```

### Step 6: Configure Apache
The `.htaccess` file is already configured. Ensure `AllowOverride All` is set in your Apache configuration.

For Apache virtual host:
```apache
<VirtualHost *:80>
    ServerName checkmaster.local
    DocumentRoot /path/to/Check-Master-UFHB
    
    <Directory /path/to/Check-Master-UFHB>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 7: Access Application
- Landing page: `http://your-domain.com/`
- Login page: `http://your-domain.com/login`
- Main app: `http://your-domain.com/layout` (requires authentication)

## Key Features

### 1. Clean URL Routing
Uses AltoRouter for SEO-friendly URLs:
- `/` - Landing page
- `/login` - Login page (GET: show form, POST: authenticate)
- `/logout` - Logout
- `/dashboard` - Dashboard (protected)
- `/layout` - Main application layout (backward compatibility)

### 2. PDF Generation
Pure PHP solution using mPDF:
- Payment receipts (`recu_paiement.php`)
- Grade reports (`releve_notes.php`)
- Defense reports (`pv_soutenance.php`)

Example usage:
```php
require_once 'app/utils/DocumentGeneratorService.php';

$data = [
    'nomEtudiant' => 'KOUAME Jean',
    'numeroEtudiant' => 'ETU20240001',
    'montantPaye' => 350000,
    // ... more data
];

DocumentGeneratorService::generateReceiptPdf($data, 'recu.pdf', 'D');
```

### 3. Secure ID Handling
HashidsHelper obfuscates database IDs in URLs:
```php
require_once 'app/utils/HashidsHelper.php';

// Encode
$hash = HashidsHelper::encode(123); // Returns: "Xk9z2Qa1"

// Decode
$id = HashidsHelper::decode($hash); // Returns: 123
```

### 4. Security Features
- **Access Control**: `.htaccess` blocks direct access to sensitive directories
- **Environment Variables**: Database credentials in `.env` (not in VCS)
- **CSRF Protection**: Token validation on forms
- **SQL Injection Protection**: PDO with prepared statements
- **XSS Protection**: `htmlspecialchars()` on all user output
- **Session Security**: Secure session handling with httponly cookies

## Environment-Specific Configuration

### Development
```env
APP_ENV=development
APP_DEBUG=true
```
- Error display: ON
- Detailed logging
- Hot reload support

### Production
```env
APP_ENV=production
APP_DEBUG=false
```
- Error display: OFF
- Production logging
- Optimized for performance

## Troubleshooting

### PDF Generation Issues
If PDFs fail to generate:
1. Check PHP memory limit: `memory_limit = 256M` in php.ini
2. Verify mPDF is installed: `composer show mpdf/mpdf`
3. Check write permissions on temp directory

### Database Connection Issues
1. Verify `.env` file exists and is readable
2. Check database credentials
3. Ensure MySQL service is running
4. Test connection: `php -r "require 'app/config/database.php'; Database::getConnection();"`

### Routing Issues (404 errors)
1. Verify `.htaccess` is in the project root
2. Check Apache mod_rewrite is enabled: `a2enmod rewrite`
3. Verify `AllowOverride All` in Apache config
4. Restart Apache: `sudo service apache2 restart`

### Asset Loading Issues
1. Clear browser cache
2. Check browser console for 404 errors
3. Verify assets exist in `/assets/` directory
4. Check .htaccess allows access to `/assets/`

## Maintenance

### Updating Dependencies
```bash
composer update
```

### Database Backup
```bash
mysqldump -u root -p soutenance_manager > backup_$(date +%Y%m%d).sql
```

### Clearing Cache
```bash
# Clear PHP opcache (if enabled)
php -r "opcache_reset();"

# Clear session files
rm -rf /tmp/sess_*
```

## Migration Checklist

For deployment to shared hosting:

- [ ] Upload all files except `.git/`, `node_modules/`, `test_*.pdf`
- [ ] Create and configure `.env` file
- [ ] Import database schema
- [ ] Set permissions on `ressources/uploads/`
- [ ] Verify `.htaccess` is uploaded
- [ ] Test database connection
- [ ] Test login functionality
- [ ] Test PDF generation
- [ ] Configure email settings (if using PHPMailer)
- [ ] Set up cron jobs (if needed)
- [ ] Enable HTTPS/SSL
- [ ] Test all routes

## Support

For issues or questions:
- GitHub Issues: https://github.com/Emeric-Soro/Check-Master-UFHB/issues
- Documentation: See `/docs/` directory
- Email: support@checkmaster.ufhb.edu.ci

## License

Copyright © 2024 Université Félix Houphouët-Boigny
All rights reserved.
