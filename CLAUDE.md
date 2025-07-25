# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is a comprehensive health/medical website built with PHP 7.4 and MySQL, designed to provide medical information, hospital/doctor directories, health news, and Q&A services.

## Database Configuration

- **Host**: 127.0.0.1
- **Database**: fz_jp_to4_cn  
- **User**: fz_jp_to4_cn
- **Password**: thWAXAri4AsjznTG
- **Type**: MySQL with PHP PDO connection

## Architecture and File Structure

### Core Configuration Files
- `config/config.php` - Main configuration including database settings and site constants
- `includes/init.php` - Initialization file that should be included in all PHP pages
- `includes/Database.php` - Database connection class using singleton pattern
- `includes/functions.php` - Common utility functions

### Database Schema
- `database.sql` - Complete database schema with tables for users, hospitals, doctors, diseases, articles, Q&A, etc.
- **Key Tables**: users, hospitals, doctors, diseases, articles, questions, answers, categories, favorites, appointments

### Frontend Structure
- `index.php` - Main homepage (converted from HTML to PHP)
- `search.php` - Search functionality with intelligent suggestions
- `templates/header.php` - Common header template with navigation
- `templates/footer.php` - Common footer template
- `assets/css/style.css` - Main stylesheet with comprehensive styling
- `assets/css/responsive.css` - Responsive design rules
- `assets/js/main.js` - Core JavaScript functionality
- `assets/js/search.js` - Advanced search features

### API Endpoints
- `api/search_suggestions.php` - Real-time search suggestions
- `api/record_search.php` - Search keyword tracking
- `api/search_stats.php` - Search statistics recording
- `api/favorites.php` - User favorites management  
- `api/likes.php` - Like/unlike functionality

## Development Commands

### Database Setup
```bash
# Import the database schema
mysql -h 127.0.0.1 -u fz_jp_to4_cn -p fz_jp_to4_cn < database.sql
```

### File Permissions
```bash
# Ensure upload directory is writable
chmod 755 uploads/
chmod 755 assets/
```

## Key Features Implemented

1. **Homepage** - Modern medical website layout with hero section, quick navigation, department categories, featured doctors, health news, and Q&A sections

2. **Search System** - Intelligent search with:
   - Real-time suggestions for hospitals, doctors, diseases, articles
   - Search history tracking
   - Smart keyword highlighting
   - Advanced filtering options

3. **Database Architecture** - Comprehensive schema supporting:
   - Multi-level category system for medical specialties
   - Hospital and doctor management with ratings
   - Disease encyclopedia with detailed information
   - Article/news system with categorization
   - Q&A system with expert answers
   - User favorites and appointment booking

4. **Responsive Design** - Mobile-first approach with:
   - Adaptive navigation for mobile devices
   - Touch-friendly interfaces
   - Optimized layouts for different screen sizes

## Working with this Repository

### Adding New Features
1. Include `includes/init.php` at the top of new PHP files
2. Use the Database singleton: `$db = Database::getInstance()`
3. Follow the established template pattern with header/footer includes
4. Use utility functions from `includes/functions.php`

### Database Operations
- Use prepared statements via the Database class methods
- Examples: `$db->fetchAll()`, `$db->insert()`, `$db->update()`
- Always validate and sanitize user input

### Frontend Development
- Follow the existing CSS class naming conventions
- Use responsive design patterns from `responsive.css`
- Implement AJAX calls using the patterns in `main.js`

### Security Considerations
- User authentication checks with `isLoggedIn()` function
- XSS protection with `h()` escaping function
- CSRF protection should be implemented for forms
- Input validation for all user data

## Important Notes

- The site is designed for Chinese audiences (content in Chinese)
- Medical disclaimers are included in templates
- Search functionality tracks popular keywords for analytics
- Responsive design supports mobile medical consultations
- Database includes appointment booking system for real medical services