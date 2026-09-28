# City Care Hospital System

A comprehensive hospital management system built with PHP, featuring patient and doctor portals, appointment scheduling, and medical record management.

## Features

- **Patient Portal**
  - Book appointments with doctors
  - View medical history
  - Manage personal profile
  - Track appointment status

- **Doctor Portal**
  - Manage schedule and availability
  - View and manage patient appointments
  - Create and update medical records
  - Upload prescriptions and reports

- **Admin Panel**
  - User management
  - System configuration

- **Authentication**
  - Secure login/logout system
  - Role-based access control

## Installation

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your database credentials
3. Import the database schema (see `database/` directory)
4. Ensure the `uploads/` directory exists and is writable
5. Configure your web server (Apache/XAMPP) to point to the project directory

## Environment Configuration

Create a `.env` file in the project root:

```env
DB_HOST=localhost
DB_NAME=city_hospital
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

APP_NAME=City Care Hospital System
APP_ENV=development
```

## Directory Structure

- `admin/` - Admin panel files
- `api/` - API endpoints
- `assets/` - CSS, JavaScript, and static assets
- `config/` - Configuration files
- `database/` - Database schema and migrations
- `doctor/` - Doctor portal files
- `includes/` - Shared PHP files (headers, footers, functions)
- `patient/` - Patient portal files
- `uploads/` - User uploaded files (excluded from git)

## Security Notes

- Database credentials are loaded from environment variables
- Uses PDO with prepared statements to prevent SQL injection
- Session-based authentication
- File upload restrictions (PDF, JPEG, PNG only, max 5MB)

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- PDO MySQL extension

## License

This project is for educational purposes.
