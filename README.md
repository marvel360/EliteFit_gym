# EliteFit Gym Management System

This is a school project to make a website for a gym called EliteFit. The system provides a platform for gym members, trainers, equipment managers, and admins to manage gym activities, schedules, equipment, and analytics.

---

## Features

- **User Authentication:** Registration, login, password reset, OTP verification.
- **Role-Based Dashboards:** Separate dashboards for members, trainers, equipment managers, and admins.
- **Workout Plans:** Trainers can create and manage workout plans; members can view and follow assigned plans.
- **Session Scheduling:** Members can schedule sessions with trainers; trainers can manage their schedules.
- **Equipment Management:** Equipment managers can add, update, bulk upload, and report on gym equipment.
- **Analytics & Reports:** Admins and equipment managers can download analytics and equipment reports in PDF, XLSX, and CSV formats.
- **Notifications:** System notifications for important events (e.g., plan approval, session reminders).
- **Security:** Session management, role checks, and secure redirection practices.

---

## Technologies Used

- **Backend:** PHP (with PDO for database access)
- **Frontend:** HTML, CSS (Bootstrap), JavaScript (jQuery, custom scripts)
- **Database:** MySQL
- **PDF/XLSX/CSV Generation:** [mPDF](https://mpdf.github.io/), [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/), [PHPWord](https://phpword.readthedocs.io/)
- **Email:** PHPMailer
- **Other Libraries:** Composer for dependency management

---

## Setup Instructions

1. **Clone the Repository**

   ```sh
   git clone https://github.com/yourusername/elitefit_gym.git
   cd elitefit_gym
   ```

2. **Install Dependencies**

   ```sh
   composer install
   ```

3. **Configure Environment**

   - Copy `.env.example` to `.env` and set your database and email credentials.
   - Make sure `.env` is in your `.gitignore`.

4. **Database Setup**

   - Import the provided SQL schema into your MySQL server.
   - Update `includes/config.php` with your database credentials if not using `.env`.

5. **Set Up Web Server**

   - Place the project in your web server's root directory (e.g., `c:/wamp64/www/elitefit_gym`).
   - Access the site via `http://localhost/elitefit_gym`.

6. **Security Precautions**
   - Never commit `.env` or any file containing secrets.
   - Change all default passwords and API keys.
   - Use HTTPS in production.

---

## Usage

- **Members:** Register, log in, view workout plans, schedule sessions.
- **Trainers:** Log in, manage workout plans, view and manage session schedules.
- **Equipment Managers:** Log in, add/update equipment, bulk upload via CSV/XLSX, generate equipment reports.
- **Admins:** Log in, view analytics, manage users, download reports.

---



