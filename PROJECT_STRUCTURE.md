# Facebook Ads Analytics - Project Structure

```
facebook_ads_analytics/
├── README.md                           # Setup and usage instructions
├── PROJECT_STRUCTURE.md               # This file
├── index.php                          # Main dashboard page
├── import_csv.php                     # Console script for CSV import
│
├── includes/
│   └── config.php                     # Database configuration and helper functions
│
├── sql/
│   └── setup.sql                      # Database schema and setup script
│
├── assets/
│   ├── css/
│   │   └── main.css                   # Main stylesheet with responsive design
│   └── js/
│       └── main.js                    # JavaScript for charts and interactions
│
├── pages/
│   ├── facebook_pages.php             # Facebook Page ID statistics listing
│   ├── facebook_page_detail.php       # Detailed view for individual Facebook Page
│   ├── target_urls.php                # Target URL statistics listing
│   ├── target_url_detail.php          # Detailed view for individual Target URL
│   ├── media_hashes.php               # Ad Media Hash statistics and distribution
│   ├── media_hash_detail.php          # Comprehensive Media Hash details and analysis
│   ├── page_url_chart.php             # Dedicated page for Page + URL analysis
│   ├── url_page_chart.php             # Dedicated page for URL + Page analysis
│   ├── page_media_chart.php           # Dedicated page for Page + Media Hash analysis
│   └── ajax/
│       ├── page_url_chart.php         # AJAX endpoint for Page + URL charts (legacy)
│       ├── media_hash_details.php     # AJAX endpoint for Media Hash details (legacy)
│       └── page_media_chart.php       # AJAX endpoint for Page + Media Hash charts (legacy)
│
└── uploads/                           # Directory for CSV files to be imported
    └── example.csv                    # Sample CSV file for testing
```

## Key Components

### Database Layer (`includes/config.php`)
- PDO-based database connection
- Helper functions for safe query execution
- Error handling and connection management

### Import System (`import_csv.php`)
- Console script for processing CSV files
- Tab-separated value parsing
- Resource ID parsing (Facebook Page ID/Ad ID extraction)
- Target URL base extraction
- Duplicate file prevention
- Progress reporting

### Web Interface
- **Dashboard** (`index.php`): Overview statistics and navigation
- **Facebook Pages** (`pages/facebook_pages.php`): Page listing with usage counts
- **Page Details** (`pages/facebook_page_detail.php`): Individual page analysis
- **Target URLs** (`pages/target_urls.php`): URL listing with ad counts
- **URL Details** (`pages/target_url_detail.php`): Individual URL analysis
- **Media Hashes** (`pages/media_hashes.php`): Distribution analysis and statistics

### Interactive Features
- Dynamic charts using Chart.js
- Dedicated pages for detailed analysis (no more modals)
- Direct linking to specific chart combinations
- Responsive design for mobile compatibility
- Breadcrumb navigation between related analyses

### Styling and Assets
- Modern CSS with gradient backgrounds
- Responsive grid layouts
- Interactive hover effects
- Chart containers and modal styling
- Mobile-first responsive design

## Data Flow

1. **CSV Import**: Files placed in `uploads/` → `import_csv.php` processes → Data stored in MySQL
2. **Web Access**: User visits pages → PHP queries database → Data rendered with charts
3. **Interactions**: User clicks elements → AJAX requests → Dynamic chart updates

## Database Schema

### Tables
- `uploaded_files`: Tracks imported CSV files
- `facebook_ads`: Main data table with extracted and processed ad information

### Key Indexes
- Facebook Page ID, Ad ID, Media Hash, Target URL Base
- Date fields for time-based queries
- Composite indexes for common query patterns

## Security Features
- Prepared statements for all database queries
- Input sanitization and validation
- XSS protection with htmlspecialchars()
- CSRF protection considerations
- File upload restrictions

