# eMuse - Museum Management System

A comprehensive web-based museum management system built with PHP and MySQL.

## Features

### Exhibit Management
- Create and manage exhibits with classifications
- Track artworks and artifacts with locations
- Schedule exhibit dates and manage status

### Ticketing & QR Entry
- Online ticket purchasing system
- QR code scanning for entry validation
- Visitor count monitoring
- Multiple ticket types (Adult, Child, Senior, Student, Group)

### Guided Tour Scheduling
- Create and schedule guided tours
- Assign tour guides with specializations
- Manage tour capacity and bookings
- Track tour status

## Installation

1. **Prerequisites**
   - XAMPP (Apache + MySQL + PHP)
   - Web browser

2. **Setup Steps**

   a. Copy files to XAMPP directory:
   ```
   Copy the eMuse folder to: C:\xampp\htdocs\
   ```

   b. Import database:
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a new database named `eMuse_db`
   - Import the SQL file: `database/museum_db.sql`

   c. Start XAMPP:
   - Start Apache and MySQL services

3. **Access the System**
   - Admin Panel: http://localhost/eMuse/admin/
   - Default Login:
     - Username: `admin`
     - Password: `admin123`

## System Structure

```
eMuse/
├── admin/              # Admin panel pages
│   ├── includes/       # Header and footer templates
│   ├── index.php       # Dashboard
│   ├── login.php       # Login page
│   ├── exhibits.php    # Exhibit management
│   ├── artworks.php    # Artwork management
│   ├── tickets.php     # Ticket management
│   ├── tours.php       # Tour management
│   └── ...
├── assets/
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── config/            # Configuration files
│   ├── config.php     # Main configuration
│   └── database.php   # Database connection
└── database/          # SQL files
    └── museum_db.sql  # Database structure
```

## Default Admin Account

After importing the database, you can login with:
- **Username:** admin
- **Password:** admin123

⚠️ **Important:** Change the default password after first login!

## Features Overview

### Dashboard
- Active exhibits count
- Today's visitor statistics
- Pending tickets overview
- Upcoming tours schedule

### Exhibit Management
- Add/Edit/Delete exhibits
- Assign classifications
- Set exhibit dates
- Track exhibit status

### Artwork & Artifact Tracking
- Comprehensive artwork database
- Location tracking
- Condition monitoring
- Exhibit assignment

### Ticketing System
- Create tickets manually
- Search and filter tickets
- View ticket details
- Track ticket status

### QR Scanner
- Scan ticket QR codes
- Validate entry
- Track visitor count
- Real-time status updates

### Tour Management
- Create guided tours
- Assign tour guides
- Set capacity limits
- Monitor bookings

### Visitor Statistics
- Daily visitor counts
- Revenue tracking
- Date range filtering
- Export capabilities

## Security Features

- Password hashing with bcrypt
- CSRF protection
- SQL injection prevention with prepared statements
- Input sanitization
- Session management

## Browser Compatibility

- Chrome (recommended)
- Firefox
- Safari
- Edge

## Support

For issues or questions, please refer to the documentation or contact the system administrator.

## License

This system is developed for museum management purposes.
