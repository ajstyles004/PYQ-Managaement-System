<?php
/**
 * QUICK SETUP REFERENCE - Email + OTP Login System
 * 
 * This is your quick reference. See EMAIL_OTP_SETUP.md for detailed documentation.
 */

// STEP 1: Run this to add database columns
// URL: http://localhost/question_bank/migrate_to_email_otp.php
// Action: Click "Run Migration" button

// STEP 2: Edit otp_config.php
// Change these lines to your email service settings:

/*
GMAIL SETUP:
============
define('ENABLE_EMAIL_OTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-char-app-password-from-google');
define('SENDER_EMAIL', 'your-email@gmail.com');

To get Gmail App Password:
1. Go to myaccount.google.com/apppasswords
2. Select Mail → Windows (or your device)
3. Copy the 16-character password
4. Paste it as SMTP_PASSWORD above

SENDGRID SETUP:
===============
define('ENABLE_EMAIL_OTP', true);
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'SG.your-actual-sendgrid-api-key');
define('SENDER_EMAIL', 'noreply@yourdomain.com');

TESTING MODE (No real email):
==============================
define('ENABLE_EMAIL_OTP', false);
// OTPs will be logged to otp_log.txt instead
// Use this for development before setting up real email
*/

// STEP 3: Test the system
/*
REGISTRATION TEST:
1. Open http://localhost/question_bank/student_register.php
2. Fill form with email and password
3. Check your email (or otp_log.txt if testing) for the 6-digit code
4. Enter the code to verify
5. Success! Account is verified

LOGIN TEST:
1. Open http://localhost/question_bank/student_login.php
2. Enter the email and password from registration
3. Check email for OTP
4. Enter OTP code
5. You're logged in!
*/

// CURRENT DATABASE SCHEMA
/*
student_user table columns:
├── student_id          INT (auto-increment, primary key)
├── username            VARCHAR(100) - OPTIONAL now
├── email               VARCHAR(150) - PRIMARY LOGIN FIELD - UNIQUE
├── full_name           VARCHAR(150) - optional
├── password_hash       VARCHAR(255) - bcrypt hashed
├── otp_code            VARCHAR(10) - temporary during verification
├── otp_expires_at      DATETIME - OTP expiry timestamp
├── is_verified         TINYINT(1) - 0=unverified, 1=verified
└── created_at          TIMESTAMP - account creation time
*/

// IF SOMETHING GOES WRONG
/*
Problem: "Email sending failed"
Solution: 
- Check otp_config.php SMTP settings
- If Gmail: verify you're using App Password (not regular password)
- Check server can connect to SMTP port (587, 465, or 25)

Problem: "OTP has expired"
Solution:
- Increase OTP_EXPIRY_MINUTES in otp_config.php (default 10 minutes)

Problem: Database error on first login
Solution:
- Run migration tool again: http://localhost/question_bank/migrate_to_email_otp.php

Problem: OTP not received in email
Solution:
- Check spam folder
- If testing mode (ENABLE_EMAIL_OTP=false): check otp_log.txt
- Check SENDER_EMAIL is set to your real email address
*/

// FILES CREATED/MODIFIED
/*
NEW FILES:
✓ otp_config.php           - Email configuration & OTP functions
✓ migrate_to_email_otp.php - Database migration tool (delete after use)
✓ EMAIL_OTP_SETUP.md       - Full documentation

MODIFIED FILES:
✓ student_login.php        - Now uses email + OTP
✓ student_register.php     - Now uses email + OTP verification
✓ schema.sql               - Updated student_user table schema

KEEP IN MIND:
- Delete migrate_to_email_otp.php after running it once (security)
- Update otp_config.php with your SMTP settings
- Backup database before running migration
*/

// NEXT STEPS
/*
1. ✓ Created: otp_config.php, migrate_to_email_otp.php, EMAIL_OTP_SETUP.md
2. [ ] Edit otp_config.php - add your SMTP settings
3. [ ] Run migration: http://localhost/question_bank/migrate_to_email_otp.php
4. [ ] Test registration at student_register.php
5. [ ] Test login at student_login.php
6. [ ] Delete migrate_to_email_otp.php file
7. [ ] Read full docs: EMAIL_OTP_SETUP.md
*/

?>
