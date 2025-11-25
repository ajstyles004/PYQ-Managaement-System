Question Bank PHP Project (XAMPP)

How to use:
1. Copy the extracted 'question_bank' folder into your XAMPP htdocs directory.
   - Windows: C:\xampp\htdocs\
   - Linux: /opt/lampp/htdocs/

2. Start Apache and MySQL from XAMPP Control Panel.

3. Import schema.sql via phpMyAdmin (http://localhost/phpmyadmin) OR run SQL manually to create DB and tables.

4. Create at least one subject row in 'subject' table (subject_id is required for uploads).
   Example:
   INSERT INTO subject (subject_code, subject_name, department_id, semester) VALUES
   ('CS201','Database Management Systems', (SELECT department_id FROM department WHERE department_code='CSE'), 4);

5. Ensure 'uploads' folder is writable. Create it if missing.

6. Open http://localhost/question_bank/index.php in browser.

Files included:
- db.php
- index.php
- upload.php
- list.php
- download.php
- schema.sql
- uploads/ (empty folder)
- README.txt

If you want subject dropdown in upload form or admin login, ask and I'll add them.