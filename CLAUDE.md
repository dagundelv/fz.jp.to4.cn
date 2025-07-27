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

### Core Architecture

**Configuration Layer**
- `config/config.php` - Main configuration with database, upload, and security settings
- `includes/init.php` - Bootstrap file that initializes all core components

**Data Layer** 
- `includes/Database.php` - PDO-based singleton database class with CRUD operations
- `includes/cache.php` - CacheManager supporting file and Redis caching
- `database.sql` - Complete MySQL schema

**Service Layer**
- `includes/functions.php` - Common utility functions and business logic
- `includes/performance.php` - PerformanceOptimizer with compression, lazy loading
- `includes/seo.php` - SEO optimization utilities
- `includes/EmailService.php` - Email functionality

**Presentation Layer**
- Template system using `templates/header.php` and `templates/footer.php`
- Modular page structure (index.php, search.php, etc.)
- API endpoints in `/api/` directory

### Database Schema
- `database.sql` - Complete database schema with tables for users, hospitals, doctors, diseases, articles, Q&A, etc.
- **Key Tables**: users, hospitals, doctors, diseases, articles, questions, answers, categories, favorites, appointments

### Frontend Structure
- `index.php` - Main homepage with modern redesigned layout
- `search.php` - Search functionality with intelligent suggestions
- `templates/header.php` - Common header template with enhanced navigation and user dropdown
- `templates/footer.php` - Common footer template
- `assets/css/style.css` - Main stylesheet with comprehensive styling
- `assets/css/homepage-new.css` - New homepage design styles with modern cards and animations
- `assets/css/dropdown-fix.css` - Navigation dropdown menu fix for z-index issues
- `assets/css/responsive.css` - Responsive design rules
- `assets/js/main.js` - Core JavaScript with enhanced carousel and animations
- `assets/js/search.js` - Advanced search features

### API Endpoints
- `api/search_suggestions.php` - Real-time search suggestions
- `api/record_search.php` - Search keyword tracking
- `api/search_stats.php` - Search statistics recording
- `api/favorites.php` - User favorites management  
- `api/likes.php` - Like/unlike functionality

## Development Commands

### Local Development Setup
```bash
# Start PHP built-in server for development
php -S localhost:8000

# Or serve from the document root
php -S localhost:8000 -t /www/wwwroot/fz.jp.to4.cn
```

### Database Operations
```bash
# Import the database schema
mysql -h 127.0.0.1 -u fz_jp_to4_cn -p fz_jp_to4_cn < database.sql

# Backup database
mysqldump -h 127.0.0.1 -u fz_jp_to4_cn -p fz_jp_to4_cn > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Cache Management
```bash
# Clear file cache manually
rm -rf cache/*.cache

# Or use the admin interface at /admin/clear-cache.php
```

### File Permissions
```bash
# Ensure directories are writable
chmod 755 uploads/ assets/ cache/
chmod 644 config/config.php
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
1. **Bootstrap**: Always include `includes/init.php` first - it initializes database, cache, performance optimization
2. **Database**: Use singleton pattern `$db = Database::getInstance()` with prepared statements
3. **Templates**: Follow header/footer pattern: `include 'templates/header.php'` and `include 'templates/footer.php'`
4. **Caching**: Use helper functions `cache_remember($key, $callback, $ttl)` for expensive operations
5. **Performance**: Use `PerformanceOptimizer::lazyImage()` for images, `asset_url()` for versioned assets

### Core Development Patterns

**Database Operations**
```php
// Query with caching
$results = cache_remember('doctors_list', function() use ($db) {
    return $db->fetchAll("SELECT * FROM doctors WHERE status = 'active'");
}, 1800);

// CRUD operations
$id = $db->insert('users', $userData);
$db->update('users', $updateData, 'id = ?', [$userId]);
```

**Performance Optimization**
```php
// Lazy loading images
echo PerformanceOptimizer::lazyImage('/uploads/doctor.jpg', 'Doctor Name');

// Deferred scripts
echo PerformanceOptimizer::deferScript('/assets/js/main.js');
```

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