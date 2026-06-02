# AI-Powered ERP System

AI-Powered Enterprise Resource Planning System (ERP System)  

An intelligent and extensible ERP system designed for Small and Medium Enterprises (SMEs).

The system integrates Human Resource Management (HRM), Inventory Management, AI-based Demand Forecasting, Resume Screening, and AI-powered HR assistance into a centralized platform.

This repository contains the complete source code, configuration files, AI modules, and setup instructions required to run and develop the ERP application.


---

## Table of Contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Quickstart](#quickstart)
- [Configuration](#configuration)
- [Database](#database)
- [Running the app](#running-the-app)
- [Testing](#testing)
- [API / UI usage](#api--ui-usage)
- [Development notes & folder structure](#development-notes--folder-structure)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## Features

- Employee Management
- Department Management
- Branch Management
- Designation Management
- Inventory Management
- Warehouse Management
- AI Demand Forecasting
- Forecast Analytics Dashboard
- Resume Screening System
- AI HR Chatbot
- Role-Based Access
- REST API Integration
- Laravel + FastAPI Integration
- Role-based access control (admins, managers, employees)
- Exportable reports (CSV/PDF)
- Notifications (email / in-app) — optional

Add or remove features based on the repository implementation.

---

## Tech stack

Replace these with the actual stack used by this repo.

- Backend: (e.g., Django, Flask, Express.js, Laravel, Spring Boot)
- Frontend: (e.g., React, Vue, Angular, server-rendered templates)
- Database: (e.g., PostgreSQL, MySQL, SQLite)
- Optional: Docker, Redis, Celery / Sidekiq, Nginx

---

## Requirements

- Git
- A supported runtime for the backend (Python >= 3.8 / Node >= 14 / PHP >= 8.0, etc.)
- Database server (Postgres / MySQL / SQLite)
- (Optional) Docker & Docker Compose

---

## Quickstart

1. Clone the repo:
   ```bash
   git clone https://github.com/Junaid-Shiekh/HRM_System.git
   cd HRM_System
   ```

2. Follow the installation for your stack below.

### Python (Django/Flask) example
- Create and activate a virtual environment:
  ```bash
  python -m venv venv
  source venv/bin/activate  # macOS/Linux
  venv\Scripts\activate     # Windows
  ```
- Install dependencies:
  ```bash
  pip install -r requirements.txt
  ```
- Configure environment variables (see [Configuration](#configuration))
- Run migrations:
  ```bash
  python manage.py migrate
  ```
- Create a superuser/admin:
  ```bash
  python manage.py createsuperuser
  ```
- Run the development server:
  ```bash
  python manage.py runserver
  ```

### Node (Express / Next / Nest) example
- Install dependencies:
  ```bash
  npm install
  # or
  yarn
  ```
- Configure environment variables (see [Configuration](#configuration))
- Run migrations (if using an ORM like Sequelize/TypeORM):
  ```bash
  npx sequelize db:migrate
  # or framework-specific migration command
  ```
- Start the dev server:
  ```bash
  npm run dev
  ```

### PHP (Laravel) example
- Install dependencies:
  ```bash
  composer install
  ```
- Copy `.env.example` to `.env` and set values
- Generate app key:
  ```bash
  php artisan key:generate
  ```
- Run migrations:
  ```bash
  php artisan migrate
  ```
- Serve:
  ```bash
  php artisan serve
  ```

---

# AI Demand Forecasting Module

## Overview
The AI Demand Forecasting module is integrated into the Inventory Management section of the ERP System.

This module predicts future inventory demand using Machine Learning and provides intelligent stock recommendations.

### Features
- AI-based demand prediction
- Recommended stock calculation
- Suggested reorder quantity
- Inventory status analysis
- Forecast dashboard integration
- FastAPI + Laravel integration

---

# Technologies Used
- Laravel
- Vue.js + Inertia.js
- FastAPI
- Python
- Scikit-learn
- Pandas

---

# AI Demand Forecasting Setup Guide

## 1. Navigate to AI Folder

```bash
cd AI_Demand_Forecasting
```

---

## 2. Create Python Virtual Environment

```bash
python -m venv venv
```

---

## 3. Activate Virtual Environment

### Windows PowerShell

```bash
venv\Scripts\activate
```

---

## 4. Install Required Python Packages

```bash
pip install fastapi uvicorn pandas scikit-learn joblib numpy
```

---

## 5. Run FastAPI Server

```bash
uvicorn api:app --reload --port 8001
```

---

## 6. Open API Documentation

```text
http://127.0.0.1:8001/docs
```

---

# Laravel Setup

## 1. Install Composer Dependencies

```bash
composer install
```

---

## 2. Install Node Modules

```bash
npm install
```

---

## 3. Run Laravel Server

```bash
php artisan serve
```

---

## 4. Run Vite Development Server

```bash
npm run dev
```

---

# Laravel Environment Variable

Add this inside the Laravel `.env` file:

```env
PYTHON_AI_URL=http://127.0.0.1:8001/predict
```

---

# Important Note About Dataset Files

The AI dataset files are NOT included in the GitHub repository because the files exceed GitHub upload size limits.

You must manually place the dataset CSV files inside:

```text
AI_Demand_Forecasting/data/
```

Required dataset files:
- sales_train_validation.csv
- sell_prices.csv

These files should be downloaded or shared separately through Google Drive / OneDrive.

---

# Running the Complete System

## Terminal 1 — Laravel Server

```bash
php artisan serve
```

## Terminal 2 — Vite Frontend

```bash
npm run dev
```

## Terminal 3 — FastAPI AI Server

```bash
cd AI_Demand_Forecasting
venv\Scripts\activate
uvicorn api:app --reload --port 8001
```

---

# Inventory Forecast Module Access

Open:

```text
http://127.0.0.1:8000/inventory/forecast
```

The Laravel ERP system will automatically communicate with the FastAPI AI forecasting service.

## Configuration

Create a `.env` file from the example file if present (e.g., `.env.example`), and set the necessary environment variables. Example variables you will typically need:

- APP_NAME=
- APP_ENV=development
- APP_DEBUG=true
- SECRET_KEY or APP_KEY=
- DATABASE_URL or DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT
- EMAIL_HOST, EMAIL_PORT, EMAIL_USER, EMAIL_PASS
- REDIS_URL (if used)

Adjust according to your chosen framework.

---

## Database

- Use the framework's migration tool to create schema and seed data (if seeds exist).
- For local development, SQLite can often be used for convenience; for production, use PostgreSQL or MySQL.
- Example migration commands are shown in the Quickstart section.

---

## Running with Docker

If a Dockerfile / docker-compose.yml exists:

```bash
docker-compose up --build
```

This will build images and start the application, database, and any linked services. Check service names and ports in the compose file.

---

## Testing

Run tests using the framework's test runner. Examples:

- Python (pytest / Django):
  ```bash
  pytest
  # or
  python manage.py test
  ```

- Node:
  ```bash
  npm test
  ```

- PHP (Laravel / PHPUnit):
  ```bash
  php artisan test
  ```

Add CI instructions (GitHub Actions, etc.) if configured.

---

## API / UI usage

If the project exposes an API, provide sample requests here (replace with real endpoints):

- List employees:
  ```
  GET /api/employees
  Authorization: Bearer <token>
  ```

- Create an employee:
  ```
  POST /api/employees
  {
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "position": "Software Engineer"
  }
  ```

If the project includes a frontend, open the UI at http://localhost:8000 (or the port specified by your framework).

---

## Development notes & folder structure

Update the structure below to match your repository layout.

- /backend — backend application code
- /frontend — frontend application code
- /docs — documentation
- /migrations — database migrations (if not colocated)
- /tests — automated tests
- .env.example — environment variable example
- docker-compose.yml — optional docker compose file

---

## Contributing

Contributions are welcome. Suggested workflow:

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-feature`
3. Commit changes: `git commit -m "Add my feature"`
4. Push to your branch: `git push origin feat/my-feature`
5. Open a Pull Request with a clear description of the change

Please add tests for new features and follow existing code style.

---

## License

This project does not contain a license file. Add a license (for example, MIT) to clarify how others may use the code:

```text
MIT License
```

Replace with the correct license for your project.

---

## Contact

Maintainer: Junaid-Shiekh  
GitHub: https://github.com/Junaid-Shiekh

---

Thank you for building HRM_System! If you'd like, I can:
- update this README to reflect the exact stack and commands found in the repository (I can inspect the repo files and tailor commands),
- create a `.env.example` template based on config usage in the code,
- or generate a CONTRIBUTING.md and ISSUE_TEMPLATE/PR template.

Tell me which of those you'd like next and I'll proceed.
```
