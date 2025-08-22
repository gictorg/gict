# GICT - Global Institute of Computer Technology

A comprehensive web application for managing computer technology training institute operations.

## Features

- **User Management**: Admin, Student, and Teacher roles
- **Course Management**: Computer courses, yoga, vocational training, etc.
- **Student Enrollment**: Track student progress and certifications
- **Payment Tracking**: Support for cash, online, and UPI payments
- **News & Events**: Manage institute announcements and events
- **Gallery**: Store and display training photos and achievements
- **Responsive Design**: Mobile-friendly interface

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

## Installation

### 1. Clone/Download the Project
```bash
git clone <repository-url>
cd gict
```

### 2. Database Setup

#### Option A: Using the Setup Script (Recommended)
```bash
# Run the database setup script
php setup_database.php
```

#### Option B: Manual Database Creation
```sql
-- Create database
CREATE DATABASE gict_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'gict_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON gict_db.* TO 'gict_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configure Database Connection

Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gict_db');
define('DB_USER', 'root');        // or your MySQL username
define('DB_PASS', '');            // your MySQL password
```

### 4. Start the Application

#### Using PHP Built-in Server
```bash
# For local development
php -S localhost:8000

# For network access (WiFi)
php -S 0.0.0.0:8000
# or specific IP
php -S 172.17.66.24:8000
```

#### Using Apache/Nginx
Place the project in your web server's document root and access via your domain.

## Default Login Credentials

After running the setup script, you can use these test accounts:

- **Admin**: `admin` / `admin123`
- **Student**: `student` / `student123`
- **Teacher**: `teacher` / `teacher123`

## Database Schema

The application includes the following tables:

- **users**: User accounts and profiles
- **courses**: Available training courses
- **enrollments**: Student course enrollments
- **certificates**: Issued certificates
- **news_events**: News and event announcements
- **gallery**: Image storage
- **payments**: Payment tracking

## File Structure

```
gict/
├── config/
│   └── database.php          # Database configuration
├── assets/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   └── images/               # Images and media
├── index.php                 # Main homepage
├── login.php                 # User authentication
├── dashboard.php             # User dashboard
├── setup_database.php        # Database setup script
├── test_db.php              # Database connection test
└── README.md                # This file
```

## Testing Database Connection

Visit `http://your-domain/test_db.php` to verify your database connection and see database status.

## Customization

### Adding New Courses
1. Insert into the `courses` table
2. Update the homepage course list in `index.php`

### User Management
- Modify user roles in the `users` table
- Update authentication logic in `auth.php`

### Payment Integration
- Extend the `payments` table for additional payment methods
- Implement payment gateway integration

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL server is running
   - Verify database credentials in `config/database.php`
   - Ensure database `gict_db` exists

2. **Tables Not Found**
   - Run `php setup_database.php` to create tables
   - Check MySQL user permissions

3. **Login Not Working**
   - Verify database connection
   - Check if users table has data
   - Clear browser cookies/sessions

### Getting Help

1. Check the database test page: `test_db.php`
2. Review PHP error logs
3. Verify MySQL server status

## Security Notes

- Change default passwords in production
- Use HTTPS in production
- Regularly backup your database
- Keep PHP and MySQL updated
- Implement proper input validation

## License

This project is for educational and training institute use.

## Support

For technical support or questions, please refer to the troubleshooting section or contact your system administrator.
