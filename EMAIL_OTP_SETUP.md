# Email + OTP Login System - Setup Guide

## Overview
This system implements a secure two-factor authentication (2FA) login mechanism where users enter their email and password, then verify with a 6-digit OTP code sent to their email.

## Features
- **Email-based login**: Users login with email instead of username
- **Password-protected**: Passwords are hashed using bcrypt (PASSWORD_DEFAULT)
- **OTP verification**: 6-digit codes sent via email with 10-minute expiry
- **Account verification**: New registrations require email verification via OTP
- **Session security**: Sessions are regenerated after login to prevent fixation attacks
- **Flexible email delivery**: Supports real SMTP (Gmail, SendGrid) or file-based testing

## Files Modified/Created

### New Files
- `otp_config.php` - Email and OTP utilities
- `migrate_to_email_otp.php` - Database migration tool
- `EMAIL_OTP_SETUP.md` - This documentation

### Modified Files
- `student_login.php` - Two-step login (email+password → OTP verification)
- `student_register.php` - Registration with email + OTP verification
- `schema.sql` - Updated student_user table schema

### Configuration Files
- `otp_config.php` - Edit SMTP settings here

## Database Schema Changes

The `student_user` table now includes:
```sql
CREATE TABLE student_user (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,                    -- Now optional
  email VARCHAR(150) NOT NULL UNIQUE,              -- Primary login field
  full_name VARCHAR(150),
  password_hash VARCHAR(255) NOT NULL,
  otp_code VARCHAR(10),                            -- Temporary OTP storage
  otp_expires_at DATETIME,                         -- OTP expiry time
  is_verified TINYINT(1) DEFAULT 0,                -- Email verification status
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Setup Steps

### 1. Run Database Migration
Visit http://localhost/question_bank/migrate_to_email_otp.php and click "Run Migration" to add the new columns to existing tables.

**Backup first!** This tool is safe but always backup your database.

### 2. Configure Email Settings (Critical!)

Edit `otp_config.php` and update these constants:

#### Option A: Using Gmail SMTP
```php
define('ENABLE_EMAIL_OTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-specific-password');  // NOT your normal password!
define('SENDER_EMAIL', 'your-email@gmail.com');
define('SENDER_NAME', 'Question Bank System');
```

**To get Gmail App Password:**
1. Go to myaccount.google.com/apppasswords
2. Select Mail and Windows (or your device)
3. Copy the 16-character password generated
4. Paste it as SMTP_PASSWORD in otp_config.php

#### Option B: Using SendGrid
```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'SG.your-sendgrid-api-key');
define('SENDER_EMAIL', 'noreply@yourdomain.com');
```

#### Option C: Development/Testing Mode
```php
define('ENABLE_EMAIL_OTP', false);  // OTPs logged to otp_log.txt instead
```
Check `otp_log.txt` to see generated codes during testing.

### 3. Install PHPMailer (Optional but Recommended)

For real email sending:
```bash
composer require phpmailer/phpmailer
```

Or download from: https://github.com/PHPMailer/PHPMailer

If PHPMailer is not installed, the system will fallback to PHP's built-in `mail()` function.

### 4. Test the System

1. **Registration**: Visit student_register.php
   - Enter email and password
   - Check email (or otp_log.txt if in testing mode) for OTP
   - Enter OTP to verify account

2. **Login**: Visit student_login.php
   - Enter verified email and password
   - Check email for OTP
   - Enter OTP to complete login

## Configuration Options

### OTP Expiry Time
Edit `OTP_EXPIRY_MINUTES` constant in `otp_config.php`:
```php
define('OTP_EXPIRY_MINUTES', 10);  // Default: 10 minutes
```

### OTP Length
```php
define('OTP_LENGTH', 6);  // Default: 6 digits (supports 4-10)
```

## API Reference

### Functions in otp_config.php

#### `generate_otp()`
Generates a random OTP code.
```php
$otp = generate_otp();  // Returns "123456"
```

#### `send_otp_email($email, $otp)`
Sends OTP to email.
```php
$result = send_otp_email('user@example.com', '123456');
// Returns: ['success' => bool, 'message' => string]
```

#### `store_otp_in_db($email, $otp)`
Stores OTP in database with expiry time.
```php
store_otp_in_db('user@example.com', '123456');
```

#### `verify_otp($email, $otp)`
Verifies OTP code and marks email as verified.
```php
$result = verify_otp('user@example.com', '123456');
// Returns: ['verified' => bool, 'message' => string]
```

#### `is_email_verified($email)`
Checks if an email has been verified.
```php
if (is_email_verified('user@example.com')) {
    // Account is verified
}
```

## Login Flow Diagram

```
Student Registration:
┌─────────────────┐
│ Enter Email &   │
│ Password        │
└────────┬────────┘
         │
         v
┌─────────────────────────────┐
│ Hash password, create        │
│ unverified account, send OTP │
└────────┬────────────────────┘
         │
         v
┌─────────────────┐
│ Enter OTP from  │
│ email           │
└────────┬────────┘
         │
         v
┌──────────────────────┐
│ Verify OTP, mark     │
│ account as verified  │
└────────┬─────────────┘
         │
         v
    [Success]

Student Login:
┌──────────────────┐
│ Enter email &    │
│ password         │
└────────┬─────────┘
         │
         v
┌──────────────────────────────┐
│ Verify password, send OTP    │
│ to registered email          │
└────────┬─────────────────────┘
         │
         v
┌──────────────────┐
│ Enter OTP from   │
│ email            │
└────────┬─────────┘
         │
         v
┌────────────────────────┐
│ Verify OTP, create     │
│ session, redirect      │
└────────┬───────────────┘
         │
         v
    [Logged In]
```

## Security Considerations

### Implemented
- ✓ Passwords hashed with bcrypt (PASSWORD_DEFAULT)
- ✓ Session regeneration after login
- ✓ OTP has 10-minute expiry
- ✓ Prepared statements prevent SQL injection
- ✓ Email validation before processing
- ✓ Account unverified until OTP accepted

### Recommended Additional Measures
- [ ] Implement rate limiting on OTP requests
- [ ] Add HTTPS/SSL requirement
- [ ] Set secure session cookie flags:
  ```php
  session_set_cookie_params([
      'secure' => true,      // HTTPS only
      'httponly' => true,    // No JavaScript access
      'samesite' => 'Strict' // CSRF protection
  ]);
  ```
- [ ] Log authentication attempts
- [ ] Implement account lockout after failed attempts
- [ ] Add CSRF tokens to forms
- [ ] Monitor for unusual login patterns

## Troubleshooting

### "Email sending failed" Error
1. Check SMTP credentials in `otp_config.php`
2. If using Gmail, verify you generated an App Password (not your regular password)
3. Check server firewall allows outbound SMTP (port 25, 465, or 587)
4. Enable debug: Edit `otp_config.php` and set `$mail->SMTPDebug = 2;` in `send_otp_email()`

### "OTP has expired" Error
- Increase `OTP_EXPIRY_MINUTES` in `otp_config.php`
- Default is 10 minutes; consider 15-20 for slower networks

### OTP Not Received
1. Check spam/junk folder
2. If `ENABLE_EMAIL_OTP = false`, check `otp_log.txt` for the code
3. Verify sender email is correct in configuration
4. Test with a known working email service (Gmail recommended for testing)

### Database Error on First Login
Run the migration tool: http://localhost/question_bank/migrate_to_email_otp.php

## Reverting to Username Login (Rollback)

To revert to the original username-based login:

1. Restore your backup of `student_login.php` and `student_register.php`
2. The database changes are backward compatible (username column is optional)
3. Delete new files: `otp_config.php`, `migrate_to_email_otp.php`, `EMAIL_OTP_SETUP.md`

## File Cleanup (Important for Security!)

After setup is complete, **delete or disable**:
- `migrate_to_email_otp.php` - Migration tool (contains database modification code)

Keep `otp_config.php` but restrict access to admins only by adding at the top:
```php
<?php
// Restrict access to this configuration file
if (php_sapi_name() === 'cli') {
    // Allow CLI access only
} elseif (!isset($_SESSION['admin_logged_in'])) {
    die('Access denied');
}
// ... rest of file
```

## Testing Checklist

- [ ] Run migration tool successfully
- [ ] Updated SMTP settings in `otp_config.php`
- [ ] Test registration: create account, verify OTP
- [ ] Test login: login with verified account
- [ ] Test OTP resend functionality
- [ ] Verify expired OTP message appears after 10+ minutes
- [ ] Check database: `SELECT * FROM student_user;` shows new columns
- [ ] Test with multiple accounts
- [ ] Verify session regeneration (session ID changes after login)

## Support

For issues:
1. Check `otp_log.txt` for email logs (if in testing mode)
2. Enable PHP error logging: `error_reporting(E_ALL);`
3. Check MySQL error log
4. Review email service logs (Gmail, SendGrid, etc.)

## Version History

- **v1.0** (2024-11-22) - Initial implementation
  - Email + password login
  - OTP verification
  - Account email verification
  - Fallback email methods

