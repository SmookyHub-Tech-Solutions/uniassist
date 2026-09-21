<?php
// =====================================================================
// database/setup.php — FIRST-RUN installer. Visit it ONCE in the browser
// (or `php database/setup.php`) to create all tables and seed demo data:
// 3 logins (admin/support/student, all password Password123), the topic
// menu, starter knowledge answers and one student's demo results.
// Afterwards DELETE this file or block it — anyone who can open it can
// see it, and re-running is refused once users exist (delete the .sqlite
// file to start over).
// Run once: http://localhost/uniassist/database/setup.php  (delete or protect afterwards)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php'; // For grade_points() below.
$pdo = db();

// ---- 1. Create every table (IF NOT EXISTS = safe to re-open) -----------
$pdo->exec("
CREATE TABLE IF NOT EXISTS users(
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, matric_number TEXT UNIQUE, email TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'student' CHECK(role IN ('student','support','admin')),
  status TEXT NOT NULL DEFAULT 'active', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS categories(
  id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE NOT NULL, name TEXT NOT NULL, description TEXT, icon TEXT,
  status TEXT DEFAULT 'active', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS issues(
  id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL REFERENCES categories(id), code TEXT,
  name TEXT NOT NULL, description TEXT, status TEXT DEFAULT 'active', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS knowledge_base(
  id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL REFERENCES categories(id),
  issue_id INTEGER REFERENCES issues(id), question TEXT NOT NULL, answer TEXT NOT NULL, keywords TEXT,
  status TEXT DEFAULT 'active', created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS conversations(
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id), category_id INTEGER,
  status TEXT DEFAULT 'active', created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS messages(
  id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER NOT NULL REFERENCES conversations(id),
  sender TEXT NOT NULL, message TEXT NOT NULL, intent TEXT, confidence REAL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS academic_records(
  id INTEGER PRIMARY KEY AUTOINCREMENT, student_id INTEGER NOT NULL REFERENCES users(id), course_code TEXT NOT NULL,
  course_title TEXT, credit_unit INTEGER NOT NULL, grade TEXT, grade_point REAL, semester TEXT NOT NULL,
  session TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS support_tickets(
  id INTEGER PRIMARY KEY AUTOINCREMENT, ticket_number TEXT UNIQUE, student_id INTEGER NOT NULL REFERENCES users(id),
  conversation_id INTEGER, category_id INTEGER, subject TEXT NOT NULL, description TEXT, priority TEXT DEFAULT 'normal',
  status TEXT DEFAULT 'OPEN' CHECK(status IN ('OPEN','IN PROGRESS','SOLVED','CLOSED')), assigned_to INTEGER,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP, resolved_at TEXT);
CREATE TABLE IF NOT EXISTS ticket_messages(
  id INTEGER PRIMARY KEY AUTOINCREMENT, ticket_id INTEGER NOT NULL REFERENCES support_tickets(id),
  sender_id INTEGER NOT NULL, sender_type TEXT NOT NULL, message TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS feedback(
  id INTEGER PRIMARY KEY AUTOINCREMENT, conversation_id INTEGER, message_id INTEGER, student_id INTEGER,
  rating INTEGER NOT NULL, comment TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS audit_log(
  id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER REFERENCES users(id),
  actor_name TEXT, actor_role TEXT, action TEXT NOT NULL, entity TEXT, entity_id TEXT,
  details TEXT, ip TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS unanswered_questions(
  id INTEGER PRIMARY KEY AUTOINCREMENT, student_id INTEGER, conversation_id INTEGER, question TEXT NOT NULL,
  frequency INTEGER DEFAULT 1, status TEXT DEFAULT 'new', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
");

if ((int)q('SELECT COUNT(*) c FROM users')->fetch()['c'] > 0) { exit('Already set up. Delete database/uniassist.sqlite to reset.'); }

// ---- Users (demo password for all: Password123) ----
// ---- 2. Seed the three demo logins (same password for all) -------------
// admin@ / support@ / student@maaun.edu.ng — see the printed note at the
// end of this file. The student row's id is kept for the demo results.
$pw = password_hash('Password123', PASSWORD_DEFAULT);
q('INSERT INTO users(name,matric_number,email,password,role) VALUES(?,?,?,?,?)', ['System Admin', null, 'admin@maaun.edu.ng', $pw, 'admin']);
q('INSERT INTO users(name,matric_number,email,password,role) VALUES(?,?,?,?,?)', ['Support Officer', null, 'support@maaun.edu.ng', $pw, 'support']);
q('INSERT INTO users(name,matric_number,email,password,role) VALUES(?,?,?,?,?)', ['Demo Student', 'MAAUN/23/CSC/049', 'student@maaun.edu.ng', $pw, 'student']);
$sid = (int)db()->lastInsertId();

// ---- Categories & issues ----
// ---- 3. Seed the chatbot's topic menu (categories + issues) -------------
// These codes are what the AI is allowed to answer with — plain words
// here, e.g. "view_result", become button labels for students.
$cats = [
 ['results','Results & GPA','fa-solid fa-chart-column',[['view_result','View Result'],['missing_result','Missing Result'],['incorrect_result','Incorrect Result'],['gpa','GPA'],['cgpa','CGPA']]],
 ['registration','Course Registration','fa-solid fa-book-open',[['cannot_register','Unable to Register'],['registration_deadline','Registration Deadline'],['add_drop','Add/Drop Courses']]],
 ['exams','Examinations','fa-solid fa-file-pen',[['exam_procedure','Exam Procedure'],['exam_clearance','Exam Clearance']]],
 ['graduation','Graduation','fa-solid fa-graduation-cap',[['grad_requirements','Graduation Requirements']]],
 ['siwes','SIWES','fa-solid fa-industry',[['siwes_requirements','SIWES Requirements']]],
 ['calendar','Academic Calendar','fa-solid fa-calendar-days',[['calendar_info','Calendar Information']]],
 ['fees','Fees & Payments','fa-solid fa-money-bill-wave',[['fees_info','Fees Information']]],
 ['transcript','Transcript','fa-solid fa-file-lines',[['transcript_request','Request a Transcript']]],
 ['profile','Student Profile','fa-solid fa-user',[['profile_update','Update Profile']]],
 ['student_id','Student ID','fa-solid fa-id-card',[['id_card','ID Card Issues']]],
];
$catId = [];
foreach ($cats as [$code,$name,$icon,$issues]) {
    q('INSERT INTO categories(code,name,icon) VALUES(?,?,?)', [$code,$name,$icon]);
    $cid = (int)db()->lastInsertId(); $catId[$code] = $cid;
    foreach ($issues as [$ic,$in]) q('INSERT INTO issues(category_id,code,name) VALUES(?,?,?)', [$cid,$ic,$in]);
}
// ---- Knowledge base (PLACEHOLDER content: replace with real MAAUN information via admin panel) ----
// ---- 4. Seed starter knowledge answers (PLACEHOLDERS) ------------------
// Generic, safe wording to be replaced with real MAAUN information via
// the admin panel. Keywords power the no-AI fallback matcher.
$kb = [
 ['registration','Unable to Register','How do I register my courses?','Ensure your registration clearance (fees and departmental approval) is complete, then log in to the student portal and select your courses for the semester. If registration is still unavailable, contact Student Support.','course registration register courses cannot register registration unavailable'],
 ['registration','Registration Deadline','When does course registration close?','Registration deadlines are published on the academic calendar each semester. Late registration may attract a penalty. Please confirm the current dates with the Registry.','registration deadline close closing late registration'],
 ['registration','Add/Drop Courses','How do I add or drop a course?','Add/drop is done within the period stated in the academic calendar, with approval from your academic adviser.','add drop change course adviser'],
 ['exams','Exam Procedure','What do I need for examinations?','Bring your student ID card and examination slip. Ensure your course registration and fee clearance are complete before the exam period.','exam examination exams slip requirements clearance'],
 ['graduation','Graduation Requirements','What are the graduation requirements?','Students must complete all required credit units, meet the minimum CGPA, complete SIWES and the final-year project, and obtain clearance from all relevant offices.','graduation graduate requirements clearance final year'],
 ['siwes','SIWES Requirements','What do I need for SIWES?','Students must register for SIWES through the department, secure a placement, submit the required forms and complete the logbook and report.','siwes industrial training placement logbook'],
 ['calendar','Calendar Information','Where can I find the academic calendar?','The academic calendar is published by the Registry at the start of each session. Contact Student Support if you cannot find it.','academic calendar dates semester resumption'],
 ['fees','Fees Information','How do I pay school fees?','Fees are paid through the approved university payment channels. Keep your receipt and confirm your payment status with the Bursary.','fees fee pay payment school fees bursary receipt'],
 ['transcript','Request a Transcript','How do I request my transcript?','Transcript requests are submitted to the Registry. Official transcripts are processed by university staff and cannot be generated by this system.','transcript request official transcript'],
 ['profile','Update Profile','How do I update my profile details?','Contact Student Support with the correct details and supporting documents to update official records.','profile update details name correction'],
 ['student_id','ID Card Issues','My student ID card is lost or damaged','Report the loss to Student Support and follow the replacement procedure, which may involve a fee.','student id card lost damaged replacement'],
 ['exams','Exam Clearance','Why am I not cleared for exams?','Exam clearance normally depends on fee payment, course registration and attendance requirements. Contact Student Support if you believe there is an error.','not cleared clearance exam eligibility'],
];
foreach ($kb as [$cc,$issueName,$qn,$ans,$kw]) {
    $iid = q('SELECT id FROM issues WHERE category_id=? AND name=?', [$catId[$cc],$issueName])->fetch()['id'] ?? null;
    q('INSERT INTO knowledge_base(category_id,issue_id,question,answer,keywords) VALUES(?,?,?,?,?)', [$catId[$cc],$iid,$qn,$ans,$kw]);
}
// ---- Demo academic records ----
// ---- 5. Seed demo results for the demo student (drives CGPA/GPA) -------
$recs = [
 ['CSC 301','Operating Systems',3,'B','First Semester','2024/2025'],
 ['CSC 303','Database Systems',3,'A','First Semester','2024/2025'],
 ['MTH 301','Numerical Analysis',2,'C','First Semester','2024/2025'],
 ['CSC 302','Software Engineering',3,'A','Second Semester','2024/2025'],
 ['CSC 304','Computer Networks',3,'B','Second Semester','2024/2025'],
 ['CSC 401','Artificial Intelligence',3,'B','First Semester','2025/2026'],
 ['CSC 403','Compiler Construction',3,'A','First Semester','2025/2026'],
];
foreach ($recs as [$c,$t,$u,$g,$s,$se]) q('INSERT INTO academic_records(student_id,course_code,course_title,credit_unit,grade,grade_point,semester,session) VALUES(?,?,?,?,?,?,?,?)', [$sid,$c,$t,$u,$g,grade_points($g),$s,$se]);

echo '<pre>Setup complete.

Logins (password: Password123)
  student@maaun.edu.ng
  support@maaun.edu.ng
  admin@maaun.edu.ng

Delete or restrict this file now.</pre>';
