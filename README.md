# 🏋️ Gym App Backend API

A fully-featured **Gym Management REST API** built with **Laravel 13**, containerized with **Docker**. Designed to handle all gym operations including member management, subscriptions, attendance tracking, payments, trainer assignments, and detailed analytics.

---

## 🚀 Tech Stack

| Technology | Version                      |
|---|------------------------------|
| PHP | 8.4                          |
| Laravel | 13.x                         |
| PostgreSQL | 15                           |
| Redis | Latest                       |
| Docker | Latest                       |
| Nginx | Latest                       |
| Laravel Sanctum | API Authentication           |
| Spatie Permission | Role & Permission Management |

---

## ✨ Features

### 🔐 Authentication & Authorization
- Register / Login / Logout (single & all devices)
- Token-based authentication via Laravel Sanctum
- Role-based access control (RBAC) with Spatie Permission
- Auto-resolved permissions via custom middleware
- Password change with token revocation

### 👥 User & Role Management
- 5 built-in roles: `super-admin`, `admin`, `receptionist`, `trainer`, `member`
- Dynamic permission assignment per role
- Filter users by role
- Assign / revoke roles

### 🏃 Member Management
- Create member (auto-creates user account)
- Auto-generated membership number (e.g. `GYM-26-000001`)
- Member profile with full history
- Soft delete with account deactivation
- Assign / remove trainers
- Gender & status filtering

### 📋 Subscription Management
- Assign membership plans to members
- Auto-calculate end date based on plan duration
- Freeze / Unfreeze subscriptions
- Renew subscriptions
- Auto-generated subscription number (e.g. `SUB-26-000001`)

### 💳 Payment Management
- Record payments with multiple methods (cash, card, bank transfer, online)
- Auto-generated invoice number (e.g. `INV-26-000001`)
- Payment stats & revenue tracking
- Link payments to subscriptions

### 📅 Attendance Tracking
- Check-in / Check-out system
- Prevent duplicate check-ins
- Auto-calculate session duration
- Today's attendance overview
- Member attendance history

### 🗓️ Plan Management
- Create & manage membership plans
- Multiple plan types (monthly, quarterly, half-yearly, yearly, custom)
- Features list per plan
- Freeze days configuration
- Prevent deletion of plans with active subscriptions

### 🏋️ Trainer Management
- Trainers are users with `trainer` role
- Assign members to trainers
- Trainer views their assigned members
- No separate trainer table — clean role-based design

### 📊 Dashboard & Analytics
- Overview stats (members, subscriptions, revenue, attendance)
- Monthly revenue chart with growth %
- Member growth chart by month
- Attendance heatmap & peak hours
- Subscription breakdown & popular plans
- Expiring subscriptions alert

### 📈 Reports
- Expiring subscriptions (by date range)
- Expired subscriptions (by date range)
- Inactive members (no visit in X days)
- Revenue report (by date range)
- Attendance report (by date range)

---

## 🐳 Docker Services

| Container | Description | Port |
|---|---|---|
| `gym-app-backend` | PHP 8.4 FPM App | — |
| `gym-app-backend-nginx` | Nginx Web Server | `8000` |
| `gym-app-backend-db` | PostgreSQL 15 | `5439` |
| `gym-app-backend-redis` | Redis Cache & Queue | `6379` |
| `gym-app-backend-queue` | Laravel Queue Worker | — |

---

## ⚡ Quick Start

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/gym-app-backend.git
cd gym-app-backend
```

### 2. Start Docker containers
```bash
docker compose up -d --build
```

## 👤 Default Users

| Role | Email | Password |
|---|---|---|
| Super Admin | `superadmin@gym.com` | `password123` |
| Admin | `admin@gym.com` | `password123` |
| Receptionist | `receptionist@gym.com` | `password123` |
| Trainer | `trainer@gym.com` | `password123` |
| Member | `member@gym.com` | `password123` |

---

## 📡 API Endpoints

https://documenter.getpostman.com/view/8481695/2sBXinFpeQ

---

## 🗂️ Project Structure
```
gym-app-backend/
├── app/
│   ├── Enums/              # GenderEnum, MemberStatusEnum, PlanTypeEnum...
│   ├── Http/
│   │   ├── Controllers/Api/ # All API controllers
│   │   ├── Middleware/      # RolePermissionMiddleware
│   │   ├── Requests/        # Form request validation
│   │   └── Resources/       # API resources
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic (ReportService...)
│   └── Traits/              # ApiResponseTrait, GenerateSequenceNumberTrait
├── database/
│   ├── migrations/
│   └── seeders/             # RolePermissionSeeder
├── docker/
│   ├── nginx/
│   └── php/
├── routes/
│   └── api.php
├── docker-compose.yml
└── Dockerfile
```

---

## 📝 License

MIT License — feel free to use this project for personal or commercial purposes.
