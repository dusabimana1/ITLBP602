LOL School SMS - PHP/MySQLi backend starter

Requirements:
- XAMPP with Apache, MySQL/MariaDB, and PHP 8+
- Internet access for the Bootstrap CDN, or download Bootstrap locally.

INSTALL:
1. Copy this folder to C:\xampp\htdocs\lol_school_sms_backend (or rename it to lol_sms).
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin.
4. Import database.sql.
5. If your MySQL root user has a password, edit config/database.php.
6. Open http://localhost/lol_school_sms_backend/setup_admin.php.
7. Create an administrator username and password (10+ characters).
8. DELETE setup_admin.php immediately after creating the administrator.
9. Open http://localhost/lol_school_sms_backend/ and log in.

FEATURES:
- Prepared-statement login using password_hash/password_verify
- Session-protected pages and logout
- Student registration, listing, editing, and administrator-only deletion
- Class creation and student-to-class assignment
- Attendance saved/retrieved for a selected date
- Grade entry and display (0-100)
- Administrator-published school announcements
- Bootstrap responsive interface, table search, CSRF token checks

IMPORTANT:
- This is a starter project. Test locally and back up your database.
- It creates a separate database named lol_school_sms_v2; it does not automatically use or migrate your existing database.
- If your existing login uses a different session key, this project has its own login implementation. Do not mix both authentication systems without adapting the session names.
- Use HTTPS and a least-privilege database account for production deployment.
- Student deletion cascades to attendance and grades by design.
