# NeuroNet Tests Admin Panel

This is the admin panel for managing NeuroNet Temporal Processing Tests.

## Setup Instructions

1. **Initialize the database**:
   ```bash
   mkdir -p var/data
   php var/private/admin/init_db.php
   ```

2. **Set up the web server**:
   - Point your web server's document root to the `var/www` directory
   - Ensure the web server has write permissions to the `var/data` directory

3. **Access the admin panel**:
   - URL: `http://your-domain.com/admin/`
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`

4. **Change the default password** (recommended):
   ```bash
   php var/private/admin/change_password.php -u admin -p your-new-password
   ```

## Features

- **Test Management**:
  - Create, read, update, and delete tests
  - Configure test assets (images and sounds)
  - Set test parameters

- **User Management**:
  - Manage admin users
  - Change passwords

## File Structure

- `/var/www/admin/` - Admin panel web files
  - `index.php` - Main admin dashboard
  - `login.php` - Admin login page
  - `test_edit.php` - Test editor
  - `logout.php` - Logout handler

- `/var/private/` - Private PHP files
  - `/admin/` - Admin-specific PHP files
    - `init_db.php` - Database initialization script
    - `change_password.php` - Password change utility
  - `/includes/` - Shared PHP includes
    - `db.php` - Database connection and helpers
    - `auth.php` - Authentication functions

- `/var/data/` - Database and uploaded files
  - `test_results.db` - SQLite database file

## Security Notes

- Always change the default admin password after installation
- Keep the `var/private` directory outside the web root
- Regularly back up the database file
- Use HTTPS in production

## Requirements

- PHP 7.4 or higher
- SQLite3 extension for PHP
- Web server (Apache, Nginx, etc.)

## License

[Your License Here]
