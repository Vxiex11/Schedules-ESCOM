# Schedules ESCOM 🏫 Resendiz :)

This is a web-based schedule management system developed as a school project. School project where I made a school page with a different perspective, providing information about the school, careers and so on. Also a login with several cruds, for teachers, subjects, groups, schedules, to be able to have an organization of where to find each teacher where there is also a section for students and to be able to create a user to log in. It allows you to manage:

- Professors 👨‍🏫
- Subjects 📚
- Groups 👥
- Classrooms 🏫
- Class schedules 📅
- Office hours ⏰
- User accounts (admin and student) 🔐

---

## 📽️ Project Demo

[![Watch the demo](https://img.youtube.com/vi/YOUR_VIDEO_ID/0.jpg)](https://www.youtube.com/watch?v=YOUR_VIDEO_ID)

> Click the image above to watch the video demo on YouTube.

---

## 📋 Instructions

### 1. Clone the repository

```bash
git clone https://github.com/Vxiex11/Schedules-ESCOM.git
```
2. Set up with XAMPP (is required that you have already installed xampp)

    Move the project folder into your XAMPP htdocs directory:

    C:\xampp\htdocs\Schedules-ESCOM

    Start Apache and MySQL in the XAMPP Control Panel.

3. Import the database

    Open phpMyAdmin:
    http://localhost/phpmyadmin

    Create a new database named: escom_schedule

    Import your SQL schema (e.g. schema.sql) to create tables.

    Run the Python script to populate fake data:

    python data.py

    Make sure you have the required Python packages installed (mysql-connector-python, bcrypt, faker).

4. Open the project in your browser

Visit:

http://localhost/Schedules-ESCOM

⚙️ Technologies Used

- Frontend: HTML, CSS, Bootstrap (optional)
- Backend: PHP
- Database: MySQL
- Data Generation: Python (Faker, bcrypt)

👨‍💻 Author
Diego Emiliano Reséndiz Ríos
GitHub Profile

📄 License
This project was developed for educational purposes and is not intended for commercial use.

