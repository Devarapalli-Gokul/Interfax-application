# 📠 InterFAX - Fax Management System

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-10.0-red.svg)
![React](https://img.shields.io/badge/React-19.0-blue.svg)
![Status](https://img.shields.io/badge/status-production%20ready-success.svg)

A full-stack web application for managing fax communications through the InterFAX API, built with Laravel backend and React frontend.

[Features](#-features) • [Installation](#-installation) • [Documentation](#-documentation) • [API Reference](#-api-reference)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Usage](#-usage)
- [API Reference](#-api-reference)
- [Project Structure](#-project-structure)
- [Configuration](#-configuration)
- [Development](#-development)
- [Security](#-security)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Overview

InterFAX is a comprehensive fax management system that integrates with the InterFAX REST API to provide seamless electronic faxing capabilities. The application offers a modern web interface for sending and receiving faxes with real-time status updates.

### Key Highlights

- ✅ **Real-time Integration**: Direct API calls to InterFAX for live fax data
- ✅ **Modern UI**: Beautiful React interface with Tailwind CSS
- ✅ **PDF Preview**: Inline fax preview with react-pdf
- ✅ **Secure Authentication**: Laravel Sanctum for API security
- ✅ **User Management**: Automatic user creation with InterFAX credentials
- ✅ **No Local Storage**: All fax data retrieved directly from InterFAX servers

---

## ✨ Features

### 🔐 Authentication
- **Auto-creation**: Users are automatically created on first login
- **InterFAX Integration**: Login credentials used as InterFAX credentials
- **Token-based**: Secure JWT tokens for API authentication
- **Session Management**: Automatic token refresh and logout

### 📠 Fax Management
- **Inbound Faxes**: View and download received faxes from InterFAX
- **Outbound Faxes**: Send new faxes and track status
- **File Support**: PDF, TIFF, DOC, DOCX formats
- **Real-time Data**: All operations use InterFAX API directly
- **Status Tracking**: Real-time fax status updates
- **Cancel Fax**: Ability to cancel pending faxes

### 💼 Account Management
- **Balance Check**: View account balance from InterFAX
- **User Profiles**: Manage user information and fax numbers
- **Multi-user Support**: Each user has separate InterFAX credentials

### 📄 Fax Preview
- **PDF Preview**: Inline preview for inbound faxes (PDF format)
- **TIFF Support**: Download support for outbound faxes (TIFF format)
- **Modal Display**: Clean preview experience with react-pdf

---

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 10.x
- **Language**: PHP 8.1+
- **Database**: SQLite (development) / PostgreSQL/MySQL (production)
- **Authentication**: Laravel Sanctum
- **API**: RESTful JSON API
- **InterFAX SDK**: Official PHP SDK (`interfax/interfax`)

### Frontend
- **Framework**: React 19 with Vite
- **Routing**: React Router v7
- **Styling**: Tailwind CSS + Headless UI
- **PDF Preview**: react-pdf with PDF.js
- **State Management**: React Context API
- **Icons**: Heroicons

### Additional Libraries
- **HTTP Client**: Guzzle (via InterFAX SDK)
- **Image Processing**: ImageMagick (for TIFF conversion)
- **Build Tool**: Vite 7

---

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React Frontend│    │  Laravel Backend│    │  InterFAX API   │
│                 │    │                 │    │                 │
│ • Authentication│◄──►│ • REST API      │◄──►│ • Send Faxes    │
│ • Fax Management│    │ • Database      │    │ • Receive Faxes │
│ • PDF Preview   │    │ • Auth System   │    │ • Webhooks      │
│ • Real-time UI  │    │ • User Mgmt     │    │ • Status Updates│
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Data Flow

1. **Authentication**: User logs in → Backend creates/updates user → JWT token issued
2. **Send Fax**: User uploads file → Backend sends to InterFAX → Status updates
3. **View Faxes**: User requests faxes → Backend fetches from InterFAX → Display in UI
4. **Preview Fax**: User clicks preview → Backend fetches content → Display in modal

---

## 📦 Installation

### Prerequisites

- **PHP**: 8.1 or higher
- **Node.js**: 18 or higher
- **Composer**: Latest version
- **SQLite**: For local development
- **InterFAX Account**: [Sign up here](https://www.interfax.net/en/dev)

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/interfax-project.git
cd interfax-project
```

### Step 2: Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database file (if not exists)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Backend will be running at `http://localhost:8000`

### Step 3: Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

Frontend will be running at `http://localhost:5173`

### Step 4: Configure InterFAX Credentials

Edit `backend/.env`:

```env
# InterFAX Configuration
INTERFAX_BASE_URL=https://rest.interfax.net
INTERFAX_USERNAME=your_username
INTERFAX_PASSWORD=your_password
```

---

## 🚀 Quick Start

### 1. Access the Application

Open your browser and navigate to `http://localhost:5173`

### 2. Login with InterFAX Credentials

- **Username**: Your InterFAX username
- **Password**: Your InterFAX password

The system will automatically create your user account on first login.

### 3. Explore Features

- **Dashboard**: View fax statistics and account balance
- **Inbound Faxes**: Receive and preview faxes
- **Outbound Faxes**: Send faxes and track status
- **Send Fax**: Upload documents and send to any fax number

---

## 📖 Usage

### Sending a Fax

1. Navigate to the **Outbound Faxes** tab
2. Click **Send Fax** button
3. Upload a file (PDF, TIFF, DOC, DOCX)
4. Enter the recipient's fax number 
5. Click **Send**
6. Monitor status in real-time

### Viewing Inbound Faxes

1. Navigate to the **Inbound Faxes** tab
2. View all received faxes
3. Click **Preview** to view fax inline
4. Click **Download** to save to your computer

### Checking Account Balance

1. Navigate to the **Dashboard**
2. View your current account balance
3. Balance is updated in real-time from InterFAX

---

## 🌐 API Reference

### Authentication Endpoints

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

Response:
```json
{
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "username": "your_username"
  }
}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

#### Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

### Fax Management Endpoints

#### Get Inbound Faxes
```http
GET /api/faxes/inbound
Authorization: Bearer {token}
```

#### Get Outbound Faxes
```http
GET /api/faxes/outbound
Authorization: Bearer {token}
```

#### Send Fax
```http
POST /api/faxes/outbound
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "file": {file_data},
  "fax_number": "+15551234567"
}
```

#### Get Fax Content
```http
GET /api/faxes/{type}/{id}/content
Authorization: Bearer {token}
```

#### Cancel Fax
```http
POST /api/faxes/outbound/{id}/cancel
Authorization: Bearer {token}
```

#### Get Account Balance
```http
GET /api/account/balance
Authorization: Bearer {token}
```

---

## 📁 Project Structure

```
interfax-project/
├── backend/                     # Laravel backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   └── FaxController.php
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   │   └── User.php
│   │   ├── Services/
│   │   │   └── InterfaxClient.php
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   └── database.sqlite
│   ├── routes/
│   │   └── api.php
│   └── storage/
├── frontend/                    # React frontend
│   ├── src/
│   │   ├── components/
│   │   │   ├── InboundFaxes.jsx
│   │   │   ├── OutboundFaxes.jsx
│   │   │   ├── SendFax.jsx
│   │   │   └── FaxPreview.jsx
│   │   ├── contexts/
│   │   │   └── AuthContext.jsx
│   │   ├── hooks/
│   │   │   └── useFaxData.js
│   │   ├── pages/
│   │   │   ├── Dashboard.jsx
│   │   │   └── Login.jsx
│   │   └── api/
│   │       └── client.js
│   ├── public/
│   └── package.json
├── docs/                        # Documentation
│   ├── backend/
│   ├── frontend/
│   └── general/
└── README.md                    # This file
```

---

## ⚙️ Configuration

### Environment Variables

#### Backend (.env)

```env
APP_NAME=InterFAX
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# InterFAX API
INTERFAX_BASE_URL=https://rest.interfax.net
INTERFAX_USERNAME=your_username
INTERFAX_PASSWORD=your_password

# File Storage
FILESYSTEM_DISK=local
```

#### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000
```

---

## 🧪 Development

### Running Tests

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests (if configured)
cd frontend
npm test
```

### Development Mode

```bash
# Terminal 1: Backend
cd backend
php artisan serve

# Terminal 2: Frontend
cd frontend
npm run dev
```

### Building for Production

```bash
# Frontend
cd frontend
npm run build

# Backend
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔒 Security

### Authentication
- **Laravel Sanctum**: Secure token-based authentication
- **JWT Tokens**: Bearer token authentication
- **User Isolation**: Each user's data is isolated

### Data Security
- **Password Hashing**: Laravel's bcrypt hashing
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Input Validation**: Comprehensive request validation
- **SQL Injection Prevention**: Eloquent ORM protection

### API Security
- **Rate Limiting**: API rate limiting enabled
- **CORS Configuration**: Proper CORS settings
- **Error Handling**: Secure error messages

---

## 🐛 Troubleshooting

### Common Issues

#### Backend Issues

**Issue**: `Class 'InterFAX\InterFAX' not found`
```bash
cd backend
composer require interfax/interfax
```

**Issue**: Database connection error
```bash
touch database/database.sqlite
php artisan migrate
```

**Issue**: Permission errors
```bash
chmod -R 775 storage bootstrap/cache
```

#### Frontend Issues

**Issue**: Cannot connect to backend
```bash
# Check backend is running
curl http://localhost:8000/api/user

# Update VITE_API_URL in .env
```

**Issue**: PDF preview not working
```bash
# Check browser console for errors
# Verify pdf.worker.min.js is loaded
```

### Debugging

**Backend Logs**:
```bash
tail -f backend/storage/logs/laravel.log
```

**Frontend Logs**:
- Open browser DevTools (F12)
- Check Console and Network tabs

---



[Back to Top](#-interfax---fax-management-system)

</div>
