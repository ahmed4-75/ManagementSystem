# Laravel Management System

Laravel Management System is a web application built using the Laravel PHP framework that helps businesses organize and manage their daily operations, such as users, data, and tasks, through a simple and secure online dashboard.

---

## 📌 Features

- User authentication (Register / verifyEmail /verifyPhoneNumber) .
- User authentication (Login / Login no Password) .
- send Mail to verifyEmail, send SMS to verifyPhoneNumber .
- display ,update user Profile and his favicon .
- using Backblaze b2 serves to storage files .
- Create, update, delete, and display Projects .
- send notifications for New project stored in database .
- send real time notifications for new tasks and completed tasks . 
- Create, update, delete, and display user Status .
- Create, Change status, delete, and display user Tasks .
- User authorization display Users and Change role or ban or activate or destroy User.
- User authorization Roles (create / update / delete) .
- Applying integration tests .
- using coverage test reports https://rawcdn.githack.com/ahmed4-75/ManagementSystem/master/coverage-report/index.html .

---

## 🛠️ Technologies Used

- Laravel 11+
- PHP 8.2+
- MySQL
- Darkaonline L5 Swagger UI
- Laravel Phone package 
---
---
## ⚙️ Installation

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/ahmed4-75/ManagementSystem.git
cd laravel-ManagementSystem
```

### 2️⃣ Install Dependencies
```bash
composer install
```

### 3️⃣ Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```
---
---
### 🗄️ Database Configuration

### 1️⃣ Update .env file :
```bash
B_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ManagementSystem
DB_USERNAME=root
DB_PASSWORD=
```
### 2️⃣ Run migrations:
```bash
php artisan migrate
```
### 3️⃣ Run Command:
```bash
php artisan create:owner
```
Answer the questions to create your first User, and his role is "owner", and it has all Permissions


---
---
### 🚀 Run the Application
```bash
php artisan serve
```
### Open in browser:
