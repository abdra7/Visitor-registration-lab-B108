# Visits-Systems

A suite of web-based management systems developed by Umm Al-Qura University students during a cooperative training program with the Technical and Vocational Training Corporation (TVTC). This repository contains two completed solutions designed to streamline administrative tasks and improve operational efficiency within the college.

---

## 📂 Projects in this Repository

### 1. Lab B108 Visitor Registration System
* **Status:** ✅ Completed
* **Description:** A system designed to register and track student visits to Lab B108. It features role-based access for admins and provides real-time attendance records to improve lab monitoring.

### 2. Dean's Visit Management System
* **Status:** ✅ Completed
* **Description:** A system designed to organize and manage the Dean of the College's schedule, appointments, and visits, with a dedicated interface for the secretary role.

---

## 🚀 Project Development Sprints

### Sprint 1: Lab B108 Visitor Registration System
* **Design & Authentication:**
    * Design the Login Page
    * Implement Login Authentication
* **Core Functionality:**
    * Build the Admin Dashboard
    * Create Student Check-in Functionality
    * Create Student Check-out Functionality
* **Admin Management:**
    * Create Admin Management Page
    * Add New Admin Accounts
    * Edit Admin Account Details
    * Delete Admin Accounts
* **Permissions & UX:**
    * Differentiate Admin and Super Admin Permissions
    * Enhance User Experience (Success/Error messages)
    * Improve UI and Branding (TVTC Identity)
    * Add Deletion Confirmation Prompts
* **Final Phase:**
    * Test & Debugging

### Sprint 2: Dean's Visit Management System
* **Foundation & Design:**
    * Create Database Tables for Dean's Visits and Visitor Types
    * Design Dean's Visits Registration Page
* **Core Functionality:**
    * Implement Saving of Dean's Visits to the Database
    * Create a Display Page for Registered Visits
    * Create a Page to Manage Visitor Types
* **Admin & Role-Based Features:**
    * Add Edit and Delete Functionality for Dean's Visits
    * Implement Role-Based Navigation Bar Access (for `secretary` role)
    * Modify Post-Login User Redirection based on Role
* **Final Phase:**
    * Test & Debugging

---

## 💽 Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL (with a planned option for Microsoft SQL Server)
- **Branding:** TVTC and Umm Al-Qura University visual identity.

---

## 🧩 Database Structure

### Shared Tables

**`users` Table**
```sql
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','superadmin','secretary') DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)
);
```

### Lab Visitor System Tables

**`visits` Table**
```sql
CREATE TABLE visits (
  id INT(11) NOT NULL AUTO_INCREMENT,
  student_name VARCHAR(100) DEFAULT NULL,
  academic_number VARCHAR(20) DEFAULT NULL,
  specialization VARCHAR(100) NOT NULL,
  check_in DATETIME DEFAULT current_timestamp(),
  check_out DATETIME DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  checked_in_by INT(11) DEFAULT NULL,
  PRIMARY KEY (id)
);
```

### Dean's Visit Management System Tables

**`dean_college_visitor_types` Table**
```sql
CREATE TABLE dean_college_visitor_types (
  id INT(11) NOT NULL AUTO_INCREMENT,
  type_name_ar VARCHAR(100) NOT NULL,
  PRIMARY KEY (id)
);
```

**`dean_college_visits` Table**
```sql
CREATE TABLE dean_college_visits (
  id INT(11) NOT NULL AUTO_INCREMENT,
  visitor_name VARCHAR(255) NOT NULL,
  visitor_type_id INT(11) NOT NULL,
  visit_date DATE NOT NULL,
  visit_time TIME NOT NULL,
  visit_reason TEXT NOT NULL,
  status ENUM('scheduled','attended','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (visitor_type_id) REFERENCES dean_college_visitor_types(id)
);
```

---

## 👨‍💻 Development Team

This project was developed by a dedicated team of students from the Computer Science department at Umm Al-Qura University:
* Abdulrahim Alharbi
* Hussain Maash
* Abdulaziz Alharbi
* Faris Alsulami
* Abdulrahman Altayyar
* Talal Alotaibi

---

## 📷 Screenshots 
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/229cbe87-a200-4cfb-93bf-956807d5ac3c" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/f2c7a8ee-673f-4890-b95b-b7c56831e9d3" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/1db8776d-1645-4762-bdf5-cb8383c854d0" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/53d9c96a-62cd-4ac5-91f3-a78a8dff9e8a" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/b223c14e-3cdd-4820-a2d3-3adb68fe3cdb" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/96f7ee23-ba7a-4785-bf33-28c13b8cbcf7" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/3083c28a-ff53-48e2-86a4-5fe22846b1fa" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/d5369b2f-06b7-4346-9cab-ae6b4a3acb31" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/18526e35-5874-4247-84e9-09937d40a5f5" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/95d41654-2370-4fa9-b4d8-c81748fc2cbe" />
<img width="1920" height="1200" alt="image" src="https://github.com/user-attachments/assets/8e59e0a4-1f48-49a8-b26c-39ced7868d9f" />


---

## 📄 License & Credits

This project was developed as part of the cooperative training program requirements. It is intended for educational and practical implementation.

**Developed by:** Students of the Computer Science Department, Umm Al-Qura University.
**Under the supervision of:** The Technical and Vocational Training Corporation (TVTC).


## References:
**icons library :** https://fontawesome.com
