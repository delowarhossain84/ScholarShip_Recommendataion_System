Scholarship Recommendations - FINAL XAMPP VERSION

Requirements: XAMPP with Apache + MySQL, PHP 8+.

INSTALL:
1. Copy this folder into C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/scholarship_recommendation_system/
4. config.php automatically creates the database/tables and demo data on a fresh installation.
5. If you already have an scholarship_recommendation_system database and want the exact demo data, import database.sql in phpMyAdmin.

DEMO LOGIN:
Admin: admin@scholarship.com / admin123
Student: student@scholarship.com / student123
Provider: provider@scholarship.com / provider123

FEATURES:
- Student registration/login
- Scholarship search
- Smart recommendation with percentage match
- Eligibility checking before application
- Duplicate application prevention
- Application tracking
- Pending / Approved / Rejected status
- Provider scholarship publishing
- Provider scholarship management/deletion
- Provider student application review
- Approve/reject applications
- Admin panel and reports
- Prepared statements for important write operations
- CSRF token on application/profile-like POST actions where applicable
