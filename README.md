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

### Role-Based Access Control
- **Student Account** - Register/login to view and download papers (read-only access)
- **Admin Account** - Manage all content and system administration
- **Session Management** - Secure session handling with password hashing
- **Access Protection** - APIs and pages require proper authentication

### Student Interface (student_dashboard.php)
- **Search & Filter Papers**
  - Filter by department, year, and title keywords
  - View available papers in a responsive table
  - Download/open papers in a new tab
- **Student Profile** - View account details and activity
- **Responsive Design** - Built with Bootstrap 5.3
- **Clean User Experience** - Minimal clutter, focus on finding papers

### Authentication Pages
- **choose_role.php** - Landing page to select Student or Admin role
- **student_login.php** - Student login with redirect support
- **student_register.php** - New student registration
- **student_profile.php** - View/manage student profile
- **admin_login.php** - Admin login with redirect support
- **admin_logout.php** - Logout endpoint
- **student_logout.php** - Logout endpoint

### Admin Panel (manage.php)
- **Department Management**
  - Add, edit, and delete departments
  - Pre-populated with JIS University departments
- **Subject Management**
  - Add, edit, and delete subjects
  - Link subjects to departments
  - Organize by semester
- **Paper Management**
  - Upload new papers with metadata (title, year, semester, exam type)
  - Edit existing paper metadata or replace PDF files
  - Delete papers (removes files from server)
  - Filter papers by department/subject
- **Drag-and-Drop Upload** - Simple file upload with progress tracking
- **Quick Subject Creation** - Add new subjects on-the-fly during paper upload
- **Protected Access** - Admin-only pages with authentication checks

### Backend APIs (Protected)
- **paper_api.php** - CRUD operations for papers (requires auth)
- **subject_api.php** - CRUD operations for subjects (requires auth)
- **dept_api.php** - CRUD operations for departments (requires auth)
- **upload_ajax.php** - Handles paper file uploads with progress tracking
- **api_list.php** - Search/filter papers (public or authenticated)
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
├── student_login.php      # Student login page
├── student_register.php   # Student registration page
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
   - (Optional) Enter a **Year** (e.g., 2022)
   - (Optional) Search by **Title** keyword (e.g., "DBMS")
   - Click **Go** to filter results

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
   - **Add:** Click **+ Add Subject**, select department, enter details
   - **Filter:** Use the department dropdown to filter subjects
   - **Edit/Delete:** Use action buttons in the table

5. **Upload Papers:**
   - Click **+ Upload New Paper** button
   - Fill in form:
     - **Title** (e.g., "DBMS - End Sem 2020")
     - **Department** (required)
     - **Subject** (required, loads based on selected department)
     - **Year** (required)
     - **Semester** (optional)
     - **Exam Type** (optional, e.g., "Endsemester")
     - **PDF File** (drag & drop or click to browse)
   - Click **Upload Paper**
   - Progress bar shows upload status

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
1. User selects role (Student/Admin) on `choose_role.php`
2. Redirects to login page (`student_login.php` or `admin_login.php`)
3. Credentials verified against database
4. Session established with regenerated ID
5. User redirected to dashboard/panel or original requested page
6. Protected pages check session before allowing access

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

---

## Credits

Developed for question paper repository management. Built with PHP, MySQL, and Bootstrap.

---

**Last Updated:** November 2025
