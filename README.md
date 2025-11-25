# Question Bank - Previous 5-Year Question Paper Repository

A web-based question bank system built with PHP and MySQL to organize, manage, and search previous years' examination question papers for a university or educational institution.

## Overview

This project provides a centralized repository for storing and retrieving previous question papers organized by:
- **Department/Faculty**
- **Subject/Course**
- **Academic Year**
- **Semester**
- **Exam Type** (Mid-term, End-semester, etc.)

The system features both a **student interface** and an **admin management panel** for uploading and organizing papers, with role-based authentication and access control.

---

## Features

### Email + OTP Authentication System (NEW)
- **Two-Factor Authentication:** Secure login with email verification via OTP (One-Time Password)
- **Email-Based Login:** Students login using email address instead of username
- **OTP Verification:** 6-digit codes sent to registered email, valid for 10 minutes
- **Account Verification:** New registrations require email verification before access
- **Real Email Sending:** Integrated with Gmail SMTP for reliable OTP delivery
- **Session Security:** Sessions regenerated after login to prevent fixation attacks
- **Password Hashing:** Bcrypt hashing (PASSWORD_DEFAULT) for all stored passwords

### Role-Based Access Control
- **Student Account** - Register/login to view and download papers (read-only access)
- **Admin Account** - Manage all content and system administration
- **Session Management** - Secure session handling with password hashing
- **Access Protection** - APIs and pages require proper authentication

### Student Interface (student_dashboard.php)
- **Search & Filter Papers**
  - Filter by department, study year (1st–4th), and semester (1–8)
  - Search by subject code (partial match, e.g., CS101)
  - View available papers with subject codes in a responsive table
  - Download/open papers in a new tab
- **Student Profile** - View account details and activity
- **Responsive Design** - Built with Bootstrap 5.3
- **Clean User Experience** - Minimal clutter, focus on finding papers

### Authentication Pages
- **choose_role.php** - Landing page to select Student or Admin role
- **student_login.php** - Student login with email + password + OTP verification
- **student_register.php** - New student registration with email verification
- **student_profile.php** - View/manage student profile
- **admin_login.php** - Admin login with email + password (simplified UI)
- **admin_logout.php** - Logout endpoint
- **student_logout.php** - Logout endpoint

### Admin Panel (manage.php)
- **Department Management**
  - Add, edit, and delete departments
  - Pre-populated with JIS University departments
- **Subject Management**
  - Add, edit, and delete subjects with subject codes
  - Link subjects to departments and semesters
  - Filter subjects by department and/or study year (1st–4th)
  - Display computed study year (based on semester)
- **Paper Management**
  - Upload new papers with metadata (title, year, semester, exam type)
  - Edit existing paper metadata or replace PDF files
  - Delete papers (removes files from server)
  - **Advanced Filters:** Department, subject, study year, semester, and subject code search
  - Display subject codes in papers table for easy identification
- **Drag-and-Drop Upload** - Simple file upload with progress tracking
- **Quick Subject Creation** - Add new subjects on-the-fly during paper upload
- **Protected Access** - Admin-only pages with authentication checks

### Backend APIs (Protected)
- **paper_api.php** - CRUD operations for papers (requires auth)
  - Supports filtering by department, subject, study_year, semester, and subject_code search
  - Returns subject_code in JSON response
- **subject_api.php** - CRUD operations for subjects (requires auth)
  - Supports filtering by department and study_year (maps to semester ranges)
- **dept_api.php** - CRUD operations for departments (requires auth)
- **upload_ajax.php** - Handles paper file uploads with progress tracking
- **api_list.php** - Search/filter papers (public or authenticated)
  - Study year and semester filters (maps 1st–4th to semester ranges)
  - Subject code search instead of title
  - Returns subject_code in response
- **get_subjects.php** - Fetch subjects by department
- **download.php** - Secure paper download endpoint

---

## Installation & Setup

### Prerequisites
- XAMPP (or any AMP stack with PHP 7.4+, MySQL 5.7+)
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Prepare Files
1. Extract the `question_bank` folder to your XAMPP htdocs directory:
   - **Windows:** `C:\xampp\htdocs\question_bank\`
   - **Linux:** `/opt/lampp/htdocs/question_bank/`

2. Ensure the `uploads/` folder exists and is writable:
   ```
   chmod 755 uploads/  (Linux/Mac)
   ```

### Step 2: Start Services
1. Start **Apache** and **MySQL** from XAMPP Control Panel (Windows) or terminal (Linux/Mac)

### Step 3: Create Database

#### Option A: Using phpMyAdmin (GUI)
1. Open `http://localhost/phpmyadmin` in your browser
2. Click **Import** tab
3. Choose `schema.sql` file from the question_bank folder
4. Click **Import**

#### Option B: Using MySQL Command Line
```bash
mysql -u root -p < schema.sql
```
(If root has no password, omit `-p`)

### Step 4: Configure Database Connection (Optional)
Edit `db.php` if your database credentials differ:
```php
$DB_HOST = '127.0.0.1';      // Database host
$DB_NAME = 'question_bank';  // Database name
$DB_USER = 'root';           // MySQL username
$DB_PASS = '';               // MySQL password (empty if none)
```

### Step 4b: Configure Email + OTP (NEW)
To enable real email delivery for OTP verification:

1. Edit `otp_config.php`:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');  // Use Gmail App Password, not regular password
define('SENDER_EMAIL', 'your-email@gmail.com');
define('SENDER_NAME', 'Question Bank');
define('ENABLE_EMAIL_OTP', true);
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_LENGTH', 6);
```

2. **For Gmail:**
   - Enable 2-Step Verification on your Google Account
   - Go to https://myaccount.google.com/apppasswords
   - Generate an "App Password" for Mail
   - Use this app password in `SMTP_PASSWORD` (not your regular Gmail password)

3. **Alternative - Using SendGrid:**
   - Sign up for free account at https://sendgrid.com/
   - Replace SMTP_HOST with 'smtp.sendgrid.net' and SMTP_USERNAME with 'apikey'
   - Use your API key as SMTP_PASSWORD

4. **Test Email Sending:**
   - Go to `http://localhost/question_bank/student_register.php`
   - Register a test account with your email
   - You should receive an OTP email within 10 seconds
   - Complete registration by entering the OTP

For detailed setup instructions, see `EMAIL_OTP_SETUP.md`

### Step 5: Launch Application
Open your browser and go to:
```
http://localhost/question_bank/choose_role.php
```
This will take you to the role selection page where you can choose to login as a Student or Admin.

---

## File Structure

```
question_bank/
├── choose_role.php        # Role selection landing page
├── student_login.php      # Student login page (email + password + OTP)
├── student_register.php   # Student registration with email verification
├── student_dashboard.php  # Student search & download interface
├── student_profile.php    # Student profile page
├── student_auth.php       # Student session helpers
├── student_logout.php     # Student logout endpoint
├── admin_login.php        # Admin login page
├── admin_auth.php         # Admin session helpers
├── admin_logout.php       # Admin logout endpoint
├── create_admin.php       # Admin account creation utility
├── create_user.php        # User account creation utility
├── manage.php             # Admin panel (departments, subjects, papers)
├── index.php              # Public search interface (legacy)
├── db.php                 # Database connection configuration
├── otp_config.php         # Email and OTP configuration (NEW)
├── migrate_to_email_otp.php # Database migration tool for email login (NEW)
├── api_list.php           # API for searching papers
├── paper_api.php          # API for paper CRUD operations
├── subject_api.php        # API for subject CRUD operations
├── dept_api.php           # API for department CRUD operations
├── get_subjects.php       # API to fetch subjects by department
├── upload_ajax.php        # API for handling file uploads
├── download.php           # Secure download endpoint
├── upload.php             # Legacy upload page
├── list.php               # Legacy papers listing
├── schema.sql             # Database schema & initial data
├── css/
│   └── style.css          # Custom styles
├── uploads/               # Uploaded PDF files (must be writable)
├── EMAIL_OTP_SETUP.md     # Email + OTP setup guide (NEW)
└── README.md              # This file
```

---

## Database Schema

### Tables

#### `department`
Stores university departments/faculties.
- **Fields:** department_id (PK), department_code, department_name
- **Example:** CSE (Computer Science & Engineering)

#### `subject`
Stores subjects/courses linked to departments.
- **Fields:** subject_id (PK), subject_code, subject_name, department_id (FK), semester

#### `paper`
Stores question paper metadata and file references.
- **Fields:** paper_id (PK), title, year, semester, exam_type, filename, filepath, department_id (FK), subject_id (FK), uploaded_on, etc.

#### `uploader`
Stores information about who uploaded papers (optional for tracking).
- **Fields:** uploader_id (PK), name, email

#### `course`
Stores course information (currently optional).
- **Fields:** course_id (PK), course_name, duration

#### `admin_user` (NEW)
Stores admin account credentials for site administration.
- **Fields:** admin_id (PK), username (UNIQUE), password_hash, created_at
- **Note:** Use `create_admin.php` to create admin accounts securely

#### `student_user` (NEW)
Stores student account credentials for read-only access.
- **Fields:** student_id (PK), username (UNIQUE), full_name, password_hash, created_at
- **Note:** Students can self-register or use `create_user.php` to create accounts

### Pre-populated Data
The schema includes 13 JIS University departments:
- Faculty of Engineering & Technology
- Computer Science & Engineering
- Faculty of Science
- Faculty of Pharmacy
- Faculty of Law
- Faculty of Management (BBA/MBA)
- And more...

---

## Usage Guide

### For Students (Public Users)

1. **Register or Login:**
   - Go to `http://localhost/question_bank/choose_role.php`
   - Click **I am a Student**
   - New users: Click **Register** to create a student account
   - Existing users: Login with username and password

2. **Search Papers (Student Dashboard):**
   - After login, you're on the student dashboard
   - Select a **Department** from the dropdown
   - (Optional) Select a **Year of Study** (1st, 2nd, 3rd, or 4th)
   - (Optional) Select a **Semester** (1–8)
   - (Optional) Search by **Subject Code** (e.g., "CS101" or "YMT4001")
   - Click **Go** to filter results
   - Results show subject codes for easy identification

3. **Download Papers:**
   - Click the **Open** button in the File column
   - Paper opens in a new tab
   - Use browser download functionality if needed

4. **View Profile:**
   - Click **Profile** (if available in student dashboard)
   - View account details and registration date

### For Administrators (Admin Panel)

1. **Access Admin Panel:**
   - Go to `http://localhost/question_bank/choose_role.php`
   - Click **I am an Admin**
   - Login with admin credentials
   - Or go directly to `http://localhost/question_bank/manage.php`

2. **Create First Admin Account:**
   - If no admin exists yet, use `create_admin.php`:
   ```
   http://localhost/question_bank/create_admin.php
   ```
   - Enter username and password
   - This creates the initial admin account

3. **Manage Departments:**
   - **Add:** Click **+ Add Department**
   - **Edit:** Click **Edit** next to a department
   - **Delete:** Click **Delete** (cascades to subjects and papers)

4. **Manage Subjects:**
   - **Add:** Click **+ Add Subject**, select department, enter subject code and details
   - **Filter:** Use the department dropdown and/or study year filter to filter subjects
   - **View Year:** Subjects table shows computed study year (based on semester)
   - **Edit/Delete:** Use action buttons in the table

5. **Upload Papers:**
   - Click **+ Upload New Paper** button
   - Fill in form:
     - **Title** (e.g., "DBMS - End Sem 2020")
     - **Department** (required)
     - **Subject** (required, loads based on selected department)
     - **Year** (required; exam year, e.g., 2024)
     - **Semester** (optional; 1–8 based on curriculum)
     - **Exam Type** (optional, e.g., "Endsemester")
     - **PDF File** (drag & drop or click to browse)
   - Click **Upload Paper**
   - Progress bar shows upload status
   - Subject dropdown now shows subject codes (e.g., "Operating Systems (YCS4001)")

6. **Edit Papers:**
   - Click **Edit** next to a paper in the Papers table
   - Modify metadata (title, year, subject, etc.)
   - Optionally upload a replacement PDF
   - Click **Save Paper**

7. **Delete Papers:**
   - Click **Delete** next to a paper
   - Confirm deletion
   - Paper and associated file are removed

8. **Logout:**
   - Click **Logout** to end admin session

### Quick Subject Creation During Upload
- While uploading a paper, if the needed subject doesn't exist:
  - Click the **+** button next to the Subject dropdown
  - Modal opens to create new subject
  - Subject is automatically selected after creation

---

## API Reference

### Paper API (`paper_api.php`)

#### List Papers
```
GET /paper_api.php?action=list[&subject=ID&department=ID]
Response: JSON array of papers
```

#### Get Single Paper
```
GET /paper_api.php?action=get&id=PAPER_ID
Response: JSON object with paper details
```

#### Create Paper (requires file upload)
```
POST /paper_api.php
Parameters: title, department_id, subject_id, year, semester, exam_type, paper_file (PDF)
Response: { success: true/false, error?: string }
```

#### Update Paper
```
POST /paper_api.php
Parameters: action=update, paper_id, title, year, semester, exam_type, department_id, subject_id, [paper_file]
Response: { success: true/false, error?: string }
```

#### Delete Paper
```
POST /paper_api.php
Parameters: action=delete, paper_id
Response: { success: true/false, error?: string }
```

### Subject API (`subject_api.php`)

#### List Subjects
```
GET /subject_api.php?action=list[&department=DEPT_ID]
Response: JSON array of subjects
```

### Department API (`dept_api.php`)

#### List Departments
```
GET /dept_api.php?action=list
Response: JSON array of departments
```

---

## Security Features

- **Password Hashing:** Admin and student passwords hashed using PHP `password_hash()` (bcrypt)
- **Session Management:** Secure session handling with session regeneration on login
- **Input Validation:** All user inputs are validated (integers, strings trimmed)
- **SQL Injection Prevention:** Prepared statements with parameterized queries
- **File Type Checking:** MIME type validation for PDF uploads
- **Random File Naming:** Uploaded files renamed with timestamp + random bytes
- **HTML Escaping:** XSS prevention in frontend JavaScript
- **Authentication Required:** Admin APIs and pages require valid login
- **Logout Security:** Proper session destruction on logout
- **Access Control:** Students can only view papers, cannot modify content

### Authentication Flow

**Student Login (with Email + OTP):**
1. User selects "I am a Student" on `choose_role.php`
2. Redirected to `student_login.php`
3. New user: Click **Register** on `student_register.php`
   - Enter email and password
   - Account created as unverified
   - 6-digit OTP sent to registered email
   - Enter OTP within 10 minutes to verify account
   - Redirected to student_login.php for login
4. Existing user: Enter email + password on login page
   - Password verified against bcrypt hash
   - 6-digit OTP generated and sent via Gmail SMTP
   - Enter OTP to complete authentication
5. Session established with regenerated ID (session_regenerate_id(true))
6. User redirected to student_dashboard.php

**Admin Login:**
1. User selects "I am an Admin" on `choose_role.php`
2. Redirected to `admin_login.php`
3. Credentials verified against admin_user table
4. Session established with regenerated ID
5. User redirected to manage.php or requested page
6. Protected pages check admin session before allowing access

**OTP Security Details:**
- OTP Format: 6 random digits (zero-padded)
- Expiry: 10 minutes (configurable in otp_config.php)
- Transport: Sent via Gmail SMTP with TLS encryption
- Storage: Stored in student_user.otp_code with expiration timestamp
- Verification: Checked against database, compared with expiry time

### Production Recommendations

For production deployment, consider:
1. **HTTPS Only** - Use SSL/TLS encryption
2. **Rate Limiting** - Prevent brute-force login attempts
3. **Move uploads folder** - Store uploads outside webroot for better security
4. **File Size Limits** - Set appropriate max upload sizes in PHP config
5. **IP Whitelisting** - Restrict admin panel access by IP if possible
6. **Backup Strategy** - Regular database and file backups
7. **Monitoring** - Log admin actions and suspicious activities
8. **CSRF Protection** - Add CSRF tokens to sensitive forms
9. **Update PHP/MySQL** - Keep all software versions current

---

## Troubleshooting

### Common Issues

#### 1. **Database Connection Error**
- **Error:** `DB Connect Error (1045)`
- **Solution:** Check credentials in `db.php`. Verify MySQL is running.

#### 2. **Uploads Folder Permission Error**
- **Error:** `Failed to save uploaded file`
- **Solution:** Make sure `uploads/` folder exists and is writable:
  ```bash
  chmod 755 uploads/  # Linux/Mac
  # Windows: Right-click > Properties > Security > Full Control
  ```

#### 3. **No Papers Showing in Search**
- **Error:** "No papers found" always displayed
- **Solution:** 
  - Verify papers exist in the database
  - Check that `api_list.php` returns data (test in browser)
  - Ensure database tables are populated via `schema.sql`

#### 4. **Subject Dropdown Empty on Upload**
- **Error:** Subject dropdown shows "(no subjects)"
- **Solution:**
  - Ensure subjects are created in the Subject section of admin panel
  - Verify subject is linked to the selected department

#### 5. **Files Not Downloading**
- **Error:** Download link doesn't work or returns 404
- **Solution:**
  - Verify file exists in `uploads/` folder
  - Check `download.php` file permissions
  - Ensure PDF is valid (not corrupted during upload)

#### 6. **OTP Email Not Received (NEW)**
- **Error:** "Failed to send OTP email" message or OTP takes too long to arrive
- **Solution:**
  - Verify `otp_config.php` has correct Gmail credentials
  - Ensure Gmail App Password is used (not regular password)
  - Check Gmail account has 2-Step Verification enabled
  - Verify `ENABLE_EMAIL_OTP` is set to `true` in `otp_config.php`
  - Check spam/junk folder in email
  - Wait at least 10 seconds after clicking "Send OTP"
  - Check Apache error log: `C:\xampp\apache\logs\error.log` (Windows)

#### 7. **OTP Code Invalid or Expired (NEW)**
- **Error:** "Invalid OTP code" or "OTP has expired"
- **Solution:**
  - Ensure you enter the full 6-digit code (leading zeros matter)
  - OTP is valid for 10 minutes from generation - don't wait too long
  - Click **Resend OTP** if expired to get a new code
  - Try logging out and logging back in to reset OTP

#### 8. **Student Registration Fails (NEW)**
- **Error:** "Failed to create account" or redirect loop on registration
- **Solution:**
  - Verify email doesn't already exist in database
  - Check password is at least 6 characters
  - Ensure `migrate_to_email_otp.php` has been run to add email columns
  - Check MySQL error log for database issues

---

## Development & Customization

### Adding More Departments
1. Go to Admin Panel
2. Click **+ Add Department**
3. Enter Department Code (e.g., "CSE") and Name
4. Save

Or add directly to database:
```sql
INSERT INTO department (department_code, department_name) VALUES ('NEW', 'New Department Name');
```

### Changing Styling
- Edit `css/style.css` for global styling
- Modify inline styles in `.php` files for component-specific changes
- Built with Bootstrap 5.3 utility classes

### Extending Functionality
- **Add Upload User Tracking:** Populate `uploader` table and modify APIs
- **Add Paper Review/Rating:** Create new `review` table and UI components
- **Add Full-Text Search:** Implement MySQL FULLTEXT indexes
- **Generate Statistics:** Add charts showing papers by department/year

---

## Technical Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Server:** Apache (XAMPP)

---

## License

This project is provided as-is for educational and institutional use.

---

## Support & Questions

For issues or feature requests:
1. Check the **Troubleshooting** section above
2. Review API documentation in code comments
3. Verify database schema matches `schema.sql`
4. Check XAMPP logs for server errors

---

## Version History

- **v1.0** - Initial release with basic paper upload/download, department/subject management
- **v1.1** - Added role-based authentication system with student and admin accounts, session management, and access control
- **v1.2** - Implemented Email + OTP two-factor authentication system for enhanced security
  - Email-based student login with 6-digit OTP verification
  - Gmail SMTP integration for reliable email delivery
  - Email verification on student registration
  - Secure password hashing with bcrypt (PASSWORD_DEFAULT)
  - Session regeneration after authentication
  - Resend OTP functionality
  - OTP expiry tracking with configurable timeout (default 10 minutes)
- **v1.3** - Enhanced search filters and admin dashboard (Current)
  - Study year (1st–4th) and semester (1–8) filters replacing numeric year input
  - Subject code search (partial match) instead of title/keyword search
  - Subject code display in papers and subjects tables
  - Admin papers and subjects filtering by study year and semester
  - Computed study year display based on semester (Sem 1–2 → 1st, etc.)
  - Admin login simplified: email + password only (removed "Full name" field)
  - "Register here" link below login form instead of "Create Admin" button
  - Subject dropdown options now include subject codes for clarity
  - Subject code search in both student and admin interfaces

---

## Credits

Developed for question paper repository management. Built with PHP, MySQL, and Bootstrap.

---

**Last Updated:** November 2025
