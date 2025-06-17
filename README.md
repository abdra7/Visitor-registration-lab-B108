# Visitor-registration-lab-B108

A web-based attendance management system designed to register and track student visits to Lab B108 at the Technical College. The system supports two types of users—**Super Admin** and **Admin**—with role-based access control. Built to improve lab monitoring and align with the digital transformation vision of modern educational institutions.

---

## 📌 Features

- **Admin Login System**
- **Student Check-in / Check-out**
- **Real-Time Attendance Records**
- **Role-Based Permissions**
  - Super Admin: Manage Admins + Attendance
  - Admin: Manage Attendance only
- **Admin Account Management**
  - Add / Edit / Delete Admins
- **UI/UX Enhancements**
  - Responsive design
  - Confirmation prompts
  - Success/Error notifications

---

## 💽 Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP (assumed based on context)
- **Database:** MySQL
- **Others:** TVTC Branding integration

---

## 🧩 Database Structure

### `users` Table

```sql
CREATE TABLE users (
  id INT(11) NOT NULL,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','superadmin') DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp()
);
````

### `visits` Table

```sql
CREATE TABLE visits (
  id INT(11) NOT NULL,
  student_name VARCHAR(100) DEFAULT NULL,
  academic_number VARCHAR(20) DEFAULT NULL,
  specialization VARCHAR(100) NOT NULL,
  check_in DATETIME DEFAULT current_timestamp(),
  check_out DATETIME DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  checked_in_by INT(11) DEFAULT NULL
);
```

---

## 🧪 User Stories

* **Admin Login:** Secure access to the dashboard
* **Student Check-in/Check-out:** Track each student's visit
* **Admin Management:** Super Admins can create, edit, and delete Admin accounts

---

## ✅ Completed Tasks

* Login Page and Authentication
* Admin Dashboard for Check-ins
* Check-out Functionality
* Role-based Access (Admin vs Super Admin)
* Admin Account Management (Add, Edit, Delete)
* UI Branding (TVTC)
* UX Improvements (messages, confirmations)

---

## 👨‍💻 Team Members

* Hussain Maash
* Abdulaziz Alharbi
* Abdulrahim Alharbi
* Faris Alsulami
* Abdulrahman Altayyar
* Sattam Alsulami
* Talal Alotaibi

---

## 📷 Screenshots

![صورة واتساب بتاريخ 1446-12-20 في 06 47 30_6284f45f](https://github.com/user-attachments/assets/234d5cb3-9300-450a-bdf6-14e5126dfa92)
![صورة واتساب بتاريخ 1446-12-20 في 06 47 53_0741250a](https://github.com/user-attachments/assets/38bb20a4-7e07-41dd-ba4d-23fb704bb3d4)
![صورة واتساب بتاريخ 1446-12-20 في 06 48 06_69b2d98b](https://github.com/user-attachments/assets/212e4dd9-079f-4009-8bcb-351f0b21905d)
![صورة واتساب بتاريخ 1446-12-20 في 06 48 42_2e3f45cc](https://github.com/user-attachments/assets/4a1957f2-a60e-41f1-9181-2dbbba28b03e)
![صورة واتساب بتاريخ 1446-12-20 في 06 48 56_e4e4d70f](https://github.com/user-attachments/assets/3fa6a5b3-40a5-4c09-9211-c16076fdb836)





---

## 🏁 Conclusion

This system improves the administrative efficiency of lab attendance tracking, with future-ready features and a clean, intuitive UI. It's a scalable solution that reflects the values of digital transformation in educational institutions.

---

## 📄 License

This project is for educational use and can be implemented in practice.
