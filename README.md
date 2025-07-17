# Facebook Ads Analytics Website

A comprehensive PHP website for analyzing Facebook advertising campaign data from CSV files.

## Features

- **CSV Data Import**: Console script to import tab-separated CSV files with Facebook ads data
- **Facebook Page Analysis**: Statistics and growth charts for each Facebook Page ID
- **Target URL Analysis**: Performance metrics and usage patterns for target URLs
- **Media Hash Analysis**: Distribution analysis and usage statistics for ad media hashes
- **Interactive Charts**: Dynamic charts and popups for detailed data exploration
- **Responsive Design**: Mobile-friendly interface with modern styling

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE facebook_ads_analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p facebook_ads_analytics < sql/setup.sql
```

3. Update database credentials in `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'facebook_ads_analytics');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 2. File Permissions

Ensure the web server has read/write access to the project directory:
```bash
chmod -R 755 facebook_ads_analytics/
chmod -R 777 facebook_ads_analytics/uploads/
```

### 3. Web Server Configuration

Point your web server document root to the project directory or create a virtual host.

Example Apache virtual host:
```apache
<VirtualHost *:80>
    ServerName facebook-ads-analytics.local
    DocumentRoot /path/to/facebook_ads_analytics
    <Directory /path/to/facebook_ads_analytics>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Usage

### Importing CSV Data

1. Place your CSV files in the `uploads/` directory
2. Ensure CSV files are tab-separated with the following columns:
   - Advertiser
   - Resource ID (format: Facebook Page ID/Ad ID)
   - Region
   - Campaign
   - Ad Title
   - Ad Description
   - Ad Media Type
   - Ad Media Hash
   - Target URL
   - First Shown At
   - Last Shown At

3. Run the import script:
```bash
php import_csv.php
```

The script will:
- Process all CSV files in the uploads directory
- Extract Facebook Page ID and Ad ID from Resource ID
- Extract base URL from full Target URL
- Avoid importing duplicate files
- Display import progress and statistics

### Website Navigation

- **Dashboard**: Overview statistics and recent import activity
- **Facebook Pages**: List of all Facebook Page IDs with usage statistics
- **Target URLs**: List of all target URLs with ad counts
- **Ad Media Hashes**: Distribution analysis and usage patterns

### Interactive Features

- Click on any Facebook Page ID to view detailed statistics and growth charts
- Click on any Target URL to view performance metrics and associated Facebook Pages
- Click on media hashes to view distribution charts and Facebook Page usage
- Use popup charts to explore specific combinations (Page + URL, Page + Media Hash)

## CSV File Format

Your CSV files should be tab-separated with these columns:

```
#	Advertiser	Resource ID	Region	Campaign	Ad Title	Ad Description	Ad Media Type	Ad Media Hash	Target URL	First Shown At	Last Shown At
1	example.com	123456789/987654321	US	Campaign Name	Ad Title	Ad Description	Image	abc123hash	https://example.com/page?params	2025-01-01 00:00:00	2025-01-31 23:59:59
```

## Database Schema

### uploaded_files
- `id`: Auto-increment primary key
- `filename`: CSV filename
- `row_count`: Number of rows imported
- `upload_date`: Import timestamp

### facebook_ads
- `id`: Auto-increment primary key
- `advertiser`: Advertiser name
- `facebook_page_id`: Extracted from Resource ID
- `ad_id`: Extracted from Resource ID
- `region`: Geographic region
- `campaign`: Campaign name
- `ad_title`: Advertisement title
- `ad_description`: Advertisement description
- `ad_media_type`: Type of media (Image, Video, etc.)
- `ad_media_hash`: Unique media identifier
- `target_url_base`: Base URL without parameters
- `target_url_full`: Complete URL with parameters
- `first_shown_at`: First appearance date
- `last_shown_at`: Last appearance date
- `created_at`: Record creation timestamp

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/config.php`
   - Ensure MySQL service is running
   - Verify database exists and user has proper permissions

2. **Import Script Fails**
   - Check file permissions on uploads directory
   - Ensure CSV files are properly formatted with tab separators
   - Check PHP error logs for detailed error messages

3. **Charts Not Loading**
   - Ensure JavaScript is enabled in browser
   - Check browser console for JavaScript errors
   - Verify Chart.js library is loading properly

4. **Responsive Issues**
   - Clear browser cache
   - Check CSS file is loading properly
   - Test on different devices/screen sizes

### Performance Optimization

For large datasets:
- Add database indexes on frequently queried columns
- Consider pagination for large result sets
- Implement caching for expensive queries
- Optimize CSV import process for very large files

## Security Considerations

- Change default database credentials
- Restrict file upload directory permissions
- Implement input validation and sanitization
- Use prepared statements for all database queries
- Consider adding authentication for production use

## License

This project is provided as-is for educational and analytical purposes.

