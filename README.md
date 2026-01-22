# School-Connect2School-Connect is a platform that connects students with lecturers and courses. Our mission is to provide a vibrant, academically challenging, and encouraging environment where manifold viewpoints are prized and celebrated. 
We believe in fostering a community of learners who are passionate about their education and personal growth.

# School Connect - Student Management System

## Overview
School Connect is a web-based Student Management System (SMS) designed to automate the management of student and lecturer information in educational institutions. It addresses challenges in traditional manual systems, such as data redundancy, inconsistency, and security issues. This project was developed as a bachelor's dissertation at Rockview University by Jeremiah Ng’ambi (Student Number: 20220027) in November 2025.

The system provides secure authentication, role-based access, and basic CRUD operations for student and lecturer records. It is built for small to medium-sized institutions and serves as a foundation for future expansions.

## Features
- **Secure Login and Role-Based Access**:
  - Common login page with username/password authentication.
  - Roles: Administrator, Student, Lecturer.
  - Passwords hashed using bcrypt.
  - Automatic redirection to role-specific dashboards.

- **Administrator Features**:
  - Full CRUD (Create, Read, Update, Delete) for student records.
  - Full CRUD for lecturer records (including name, description, image).
  - View lists and basic reports of students and lecturers.

- **Student Features**:
  - View and update personal profile (e.g., email, phone).
  - Access personal dashboard.

- **Lecturer Features:
  - View and update personal profile.
  - (Extendable for future academic functions).

- **Security**:
  - Parameterized queries to prevent SQL injection.
  - Session management.
  - Role restrictions to prevent unauthorized access.

- **User Interface**:
  - Responsive design with HTML5 and CSS3.
  - Simple and intuitive dashboards.

## Technologies Used
- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: HTML5, CSS3 (basic JavaScript optional)
- **Server**: Apache (tested with XAMPP)
- **Development Tools**: Visual Studio Code, phpMyAdmin, Draw.io for diagrams

## Architecture
- Three-tier: Presentation (Frontend), Application (Backend), Data (MySQL).
- Loosely follows MVC pattern.
- Database Schema:
  - `user`: id, username, password, email, phone, usertype
  - `lecturer`: id, name, description, image
  - `profile`: (optional) extended details
  - `admission`: (optional) enrollment status

## Installation
1. Install XAMPP or a similar LAMP/WAMP stack.
2. Clone the repository into the htdocs folder (or your web server root).
3. Create the MySQL database and import the schema (tables: user, lecturer, etc.).
4. Configure the database connection (e.g., in a config file).
5. Access the system via browser: `http://localhost/your-project-folder`

## Usage
- Default login page: `index.php`
- Admin dashboard: `adminhome.php`
- Add sample data or create an admin user manually in the database for initial access.

## Screenshots
check folder of screenshots

## Future Enhancements
- Grading system (  done )
- Fee payment integration
- Notifications (email/SMS) - (done)
- Mobile application
- Advanced reporting and analytics - (done)

## License
This is an academic project. Feel free to use or modify it (consider adding an open-source license like MIT if sharing publicly).

## Author
Jeremiah Ng’ambi  
Rockview University, Department of Information and Communication Technology  
Supervisor: Mr. Chimbwinja  
Date: November 2025
