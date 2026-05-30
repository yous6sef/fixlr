# FLIX Marketplace Platform - Complete System Documentation

**Last Updated:** May 27, 2026  
**Version:** 1.0.0  
**Status:** MVP Development Phase 2 (Partially Complete)  
**Lead Developer:** GitHub Copilot  

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Architecture & Technology Stack](#architecture--technology-stack)
3. [File Structure & Organization](#file-structure--organization)
4. [Current Implementation Status](#current-implementation-status)
5. [Known Issues & Bugs](#known-issues--bugs)
6. [Database Schema](#database-schema)
7. [Authentication & Authorization](#authentication--authorization)
8. [API Endpoints Reference](#api-endpoints-reference)
9. [Design System & UI](#design-system--ui)
10. [Critical Fixes Needed](#critical-fixes-needed)
11. [Next Development Phases](#next-development-phases)
12. [Deployment & Operations](#deployment--operations)
13. [Performance & Security Recommendations](#performance--security-recommendations)

---

## System Overview

### Purpose
FLIX is a peer-to-peer home services marketplace platform enabling users to post service requests and workers to fulfill them. The platform implements an 11-state task lifecycle with strict state transitions, mutual ratings, and financial processing.

### Core Features (MVP)
- **User Registration & Authentication** - Email/password signup for users and workers
- **Task Management** - Create, track, and manage service requests
- **Worker Marketplace** - Browse available tasks, accept assignments
- **11-State Task Lifecycle** - Structured progression from request to completion
- **Rating System** - Mutual 5-star ratings with comments
- **Payment Processing** - Worker payments with 80/20 platform split
- **Admin Dashboard** - Worker approvals, payment verification
- **Real-Time Notifications** - Socket.io event system (partially implemented)
- **Bilingual Support** - English & Arabic with RTL layout

### Business Model
- **Checking Fee:** 300 EGP (mandatory, charged after worker arrival)
- **Platform Remittance:** 20% of fixing price
- **Worker Earnings:** 80% of fixing price + checking fee (if service completed)
- **Default Fixing Price:** Negotiated between user and worker

---

## Architecture & Technology Stack

### Frontend
- **Framework:** Vanilla PHP (server-rendered pages)
- **Styling:** Custom CSS with green design system
- **Templating:** PHP with bilingual support
- **Client Libraries:** Axios (for API calls), Socket.io client
- **Package Manager:** npm (for Node.js dependencies)

### Backend
- **Runtime:** PHP 8.0.30 (dev server: localhost:8000)
- **Language:** PHP 8+ with modern syntax
- **API Style:** RESTful JSON endpoints
- **Real-Time:** Node.js + Express + Socket.io (port 3001/3000)

### Database
- **Primary:** PostgreSQL 10+ (production)
- **Fallback:** SQLite (local development)
- **Connection:** PDO/pg_query_params with connection pooling
- **Migrations:** Manual schema creation (no ORM)

### File Storage
- **Provider:** Cloudinary
- **Types:** ID cards (JPEG/PNG), documents (PDF), receipts
- **Integration:** CloudinaryUploadHandler.php class
- **Validation:** Signed tokens, MIME type checking

### Deployment
- **Dev Server:** PHP built-in web server
- **Production Ready:** Dockerized (Dockerfile included)
- **PaaS Options:** Railway, Heroku, Azure App Service
- **Environment:** .env configuration files

---

## File Structure & Organization

### Current Organization (Post-Cleanup)

```
fixlr/
├── pages/                          # All frontend PHP pages
│   ├── user/                       # User-facing pages (13 files)
│   │   ├── signup.php              # Registration form (unified user/worker)
│   │   ├── login.php               # Authentication
│   │   ├── logout.php              # Session termination
│   │   ├── task_create.php         # Create new service request
│   │   ├── track.php               # Real-time task tracking with timeline
│   │   ├── user_dashboard.php      # User home (active/completed tasks)
│   │   ├── user_requests.php       # Browse user's past requests
│   │   ├── user_new_request.php    # Alternative task creation
│   │   ├── user_profile.php        # User profile management
│   │   ├── profile.php             # Profile editing
│   │   ├── usermain.php            # Legacy main dashboard
│   │   ├── payments.php            # Payment history
│   │   ├── receipt.php             # Receipt viewing
│   │   └── request_detail.php      # Individual request details
│   │
│   ├── admin/                      # Admin-only pages (3 files)
│   │   ├── admin_dashboard.php     # Worker approvals + payment verification
│   │   ├── admin.php               # Legacy admin page
│   │   └── admin_chat.php          # Admin messaging (partially implemented)
│   │
│   └── worker/                     # Worker-facing pages (8 files)
│       ├── worker_dashboard.php    # Worker home (available tasks)
│       ├── worker_available_requests.php  # Browse REQUESTED tasks
│       ├── worker_orders.php       # Active assignments
│       ├── worker_track.php        # Track assigned task
│       ├── worker_payments.php     # Payment history
│       ├── worker_payment_submit.php # Instapay receipt upload
│       ├── worker_profile.php      # Profile management
│       ├── worker_receipt.php      # Receipt viewing
│       └── workermain.php          # Legacy main dashboard
│
├── api/                            # RESTful API endpoints
│   ├── api_task_state_machine.php  # 11-state lifecycle (14 endpoints)
│   ├── api.php                     # General purpose API
│   ├── api_get_devices.php         # Device/location API
│   ├── api_submit_rating.php       # Rating submission
│   ├── v1/
│   │   ├── database/
│   │   │   └── migrations.js       # Database migration helpers
│   │   └── middleware/
│   │       ├── error-handler.js    # Centralized error handling
│   │       └── validator.js        # Request validation
│   └── config/
│       └── environment.js          # Environment configuration
│
├── core/                           # Core application files
│   ├── db.php                      # Database connection & demo mode
│   ├── lang.php                    # Bilingual routing & translations
│   └── config.php                  # Application configuration
│
├── public/                         # Static assets
│   ├── css/                        # (12 stylesheets)
│   │   ├── app.css                 # Main stylesheet
│   │   ├── design-tokens.css       # Design system variables
│   │   ├── components.css          # Reusable components
│   │   ├── responsive.css          # Mobile-first responsive
│   │   ├── typography.css          # Font hierarchy
│   │   ├── animations.css          # Transitions & animations
│   │   ├── variables.css           # CSS custom properties
│   │   ├── loaders.css             # Loading states
│   │   ├── modern-ui.css           # Enhanced styling
│   │   ├── premium-ui.css          # Premium components
│   │   ├── responsive-utilities.css # Utility classes
│   │   └── animations-enhanced.css # Advanced animations
│   │
│   ├── js/                         # Client-side JavaScript
│   │   ├── socket-client.js        # Socket.io real-time client
│   │   └── notifications.js        # Notification system
│   │
│   ├── uploads/                    # User-uploaded files
│   │   ├── workers/                # Worker documents
│   │   └── tasks/                  # Task-related files
│   │
│   └── images/                     # Static images (future)
│
├── docs/                           # Documentation
│   ├── SYSTEM_DOCUMENTATION.md     # This file
│   ├── API_REFERENCE.md            # API documentation
│   ├── DEVELOPMENT_GUIDE.md        # Setup & contribution
│   ├── DEPLOYMENT_GUIDE.md         # Production deployment
│   └── TESTING_GUIDE.md            # QA & testing procedures
│
├── config/                         # Configuration files
│   └── environment.js              # Environment setup
│
├── database/                       # Database files
│   ├── flix.db                     # SQLite development DB
│   ├── schema.sql                  # PostgreSQL schema (if needed)
│   └── migrations/                 # Database migrations
│
├── logs/                           # Application logs
│   ├── error.log                   # Error log
│   ├── access.log                  # Access log
│   └── debug.log                   # Debug output
│
├── node/                           # Node.js backend server
│   ├── server.js                   # Express server
│   ├── package.json                # Node dependencies
│   └── SERVER_SETUP.md             # Node setup guide
│
├── lib/                            # Utility libraries
│   ├── CloudinaryUploadHandler.php # File upload utilities
│   └── [other utilities]
│
├── .env                            # Environment variables (local)
├── .env.example                    # Environment template
├── .gitignore                      # Git ignore rules
├── package.json                    # npm dependencies
├── Dockerfile                      # Docker containerization
├── docker-compose.yml              # Docker orchestration
├── railway.toml                    # Railway deployment config
├── start.sh                        # Startup script
├── server.js                       # Socket.io server
├── socket-server.js                # Alternative server
├── index.php                       # Landing page / router
├── README.md                       # Project overview
└── [root documentation files]
```

### Removed Files (Cleanup)
✅ `*.bak` backup files (8 removed)
✅ `*.new` temporary files (8 removed)
✅ `debug.php` - Debug utility
✅ `DASHBOARD_TEMPLATE.php` - Template file
✅ `setup-credentials.php` - Setup utility
✅ `setup-test-users.php` - Setup utility
✅ `index.html`, `landing.html` - Old HTML files
✅ `login_backup.php` - Backup file
✅ `server.log` - Log file

---

## Current Implementation Status

### ✅ COMPLETED (10/30 Major Deliverables)

#### Phase 1: Foundation (COMPLETE)
1. ✅ **Database Schema** - PostgreSQL schema with 6 tables (users, workers, tasks, ratings, payments, auditLogs)
2. ✅ **Core API** - api_task_state_machine.php with 14 endpoints
3. ✅ **Design System** - Green theme (#1A6B4A primary) with full CSS variables
4. ✅ **Bilingual Infrastructure** - lang.php router for English/Arabic

#### Phase 2: MVP User & Admin Flows (COMPLETE)
5. ✅ **signup.php** (~500 lines) - Unified user/worker registration
   - Form validation (client & server)
   - Type selection (User/Worker tabs)
   - Cloudinary document upload (workers only)
   - Password strength validation

6. ✅ **admin_dashboard.php** (~650 lines) - Admin control panel
   - Statistics display (234 users, 87 workers, 156 completed tasks)
   - Worker approvals table (pagination, document verification)
   - Payment verification table
   - Reject modals with reason capture

7. ✅ **task_create.php** (~400 lines) - User task creation
   - 6 specialization categories (Plumbing, Electrical, Carpentry, Painting, Cleaning, HVAC)
   - Description & address capture
   - Urgency indicator (Urgent/Normal)
   - Success page with task ID confirmation

8. ✅ **track.php** (~650 lines) - Task tracking with timeline
   - 11-state timeline visualization (all states labeled)
   - Current status badge
   - Task details card
   - State-conditional action buttons
   - Rating system on completion

9. ✅ **login.php** - User authentication
10. ✅ **Demo Mode System** - Safe testing without live database

### ⏳ PARTIALLY COMPLETE (8/30)

11. ⏳ **worker_available_requests.php** - Browse REQUESTED tasks (created, needs testing)
12. ⏳ **worker_dashboard.php** - Worker home (created, untested)
13. ⏳ **api_task_state_machine.php** - State API (14 endpoints defined, not tested)
14. ⏳ **Socket.io Real-Time** - Defined, server running, client incomplete
15. ⏳ **Cloudinary Integration** - Configured, tested on signup
16. ⏳ **Authentication System** - Sessions working, needs CSRF tokens
17. ⏳ **Admin Approval Workflow** - Dashboard created, action handlers need testing
18. ⏳ **Payment System** - Schema defined, no implementation

### ❌ NOT STARTED (12/30)

19. ❌ **worker_propose_price.php** - Price proposal form
20. ❌ **worker_payment_submit.php** - Payment submission
21. ❌ **worker_task_workflow.php** - Worker-side state management
22. ❌ **Mobile Responsive Testing** - Design ready, not validated on devices
23. ❌ **End-to-End Testing** - No automated test suite
24. ❌ **Security Audit** - CSRF tokens, XSS prevention, SQL injection checks
25. ❌ **Error Handling** - Timeout handling, retry logic, edge cases
26. ❌ **Email Notifications** - No email system integrated
27. ❌ **SMS Integration** - No SMS notifications (SMS Gateway ready in code)
28. ❌ **Analytics** - No user behavior tracking
29. ❌ **Performance Optimization** - No caching, database optimization pending
30. ❌ **Full QA Suite** - Comprehensive testing needed

---

## Known Issues & Bugs

### 🔴 CRITICAL ISSUES (Must Fix Before Production)

1. **CSRF Token Missing on All Forms**
   - **Impact:** XSS & CSRF attack vulnerability
   - **Status:** Unfixed
   - **Solution:** 
     ```php
     // Generate token in session
     if (!isset($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
     }
     
     // Add to all forms: <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
     
     // Validate on POST: if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) { exit('Invalid token'); }
     ```

2. **No Input Sanitization on Database Queries**
   - **Impact:** SQL Injection vulnerability (though parameterized queries partially used)
   - **Status:** Unfixed
   - **Solution:** Use prepared statements everywhere + sanitize output with htmlspecialchars()

3. **Password Hashing Not Implemented in Signup**
   - **Status:** Unfixed (demo mode uses plain text)
   - **Solution:** 
     ```php
     $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 10]);
     ```

4. **Session Fixation Not Prevented**
   - **Impact:** Session hijacking vulnerability
   - **Solution:** Call `session_regenerate_id(true)` after login

5. **API Endpoints Not Authenticated**
   - **Impact:** Anyone can call /api/v1/task endpoints
   - **Solution:** Add JWT authentication or session validation to all API endpoints

### 🟡 HIGH PRIORITY ISSUES (Should Fix Before MVP)

6. **Database Connection Fallback Not Tested**
   - **Status:** PostgreSQL primary assumed, SQLite fallback untested
   - **Solution:** Test both paths with sample data

7. **Socket.io Server Not Integrated**
   - **Status:** server.js exists but PHP pages don't emit events
   - **Solution:** Wire PHP pages to emit events via HTTP POST to Node.js endpoint

8. **No Error Handling on State Transitions**
   - **Status:** API defined but error responses not tested
   - **Solution:** Test invalid transitions, missing data, timeout scenarios

9. **File Upload Size Limits Not Enforced**
   - **Status:** Cloudinary limit is 10MB but PHP upload limits may differ
   - **Solution:** Set php.ini upload_max_filesize & post_max_size to 10MB

10. **Timezone Not Set Consistently**
    - **Impact:** Payment & rating timestamps may be incorrect
    - **Solution:** Add `date_default_timezone_set('Africa/Cairo');` to core/config.php

### 🟠 MEDIUM PRIORITY ISSUES

11. **Mobile Responsiveness Not Fully Tested**
    - **Status:** CSS media queries present, not tested on real devices
    - **Solution:** Test on iOS/Android devices, 480px+ breakpoints

12. **No Loading State Feedback**
    - **Status:** Forms may feel slow without feedback
    - **Solution:** Add spinner overlays, button disabled states during submission

13. **Arabic RTL Not Tested End-to-End**
    - **Status:** Declared in HTML but form labels/inputs may not align properly
    - **Solution:** Screenshot test on both languages

14. **No Duplicate Task Prevention**
    - **Status:** User can create same task twice
    - **Solution:** Add client-side debouncing + server-side duplicate check

15. **Worker Rating Not Recalculated on New Ratings**
    - **Status:** API endpoint defined but averaging logic untested
    - **Solution:** Test rating submission with multiple ratings

### 🔵 LOW PRIORITY ISSUES

16. **No User Notifications for Task Updates**
    - **Status:** Socket.io defined but frontend doesn't subscribe
    - **Solution:** Add notification bell in header, WebSocket listener

17. **Search/Filter Not Implemented**
    - **Status:** All tasks shown regardless of filters
    - **Solution:** Add filter sidebar to worker_available_requests.php

18. **No Task Expiration Logic**
    - **Status:** REQUESTED tasks never auto-expire
    - **Solution:** Add 7-day expiration, auto-cancel with user notification

---

## Database Schema

### Tables (PostgreSQL)

#### users
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fullName VARCHAR(255) NOT NULL,
    phoneNumber VARCHAR(20),
    profileImageUrl TEXT,
    city VARCHAR(100),
    address TEXT,
    averageRating DECIMAL(3,2) DEFAULT 0,
    totalRatings INT DEFAULT 0,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_city ON users(city);
```

#### workers
```sql
CREATE TABLE workers (
    id SERIAL PRIMARY KEY,
    userId INT REFERENCES users(id),
    idCardNumber VARCHAR(100) UNIQUE,
    residentialLocation VARCHAR(255),
    workLocation VARCHAR(100),
    specializations TEXT[], -- ARRAY of specialization names
    isApproved BOOLEAN DEFAULT false,
    isCurrentlyAssigned BOOLEAN DEFAULT false,
    averageRating DECIMAL(3,2) DEFAULT 0,
    totalRatings INT DEFAULT 0,
    totalEarnings DECIMAL(10,2) DEFAULT 0,
    idCardFrontUrl TEXT,
    idCardBackUrl TEXT,
    criminalRecordUrl TEXT,
    resumeUrl TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_workers_userId ON workers(userId);
CREATE INDEX idx_workers_workLocation ON workers(workLocation);
CREATE INDEX idx_workers_specializations ON workers USING GIN(specializations);
```

#### tasks
```sql
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    userId INT REFERENCES users(id),
    workerId INT REFERENCES workers(id),
    specialization VARCHAR(100) NOT NULL,
    description TEXT,
    address TEXT,
    urgency VARCHAR(20), -- 'Urgent' or 'Normal'
    checkingFee DECIMAL(10,2) DEFAULT 300, -- 300 EGP
    fixingPrice DECIMAL(10,2),
    currentStatus VARCHAR(50), -- 11 states
    workerArrivalTime TIMESTAMP,
    checkingStartTime TIMESTAMP,
    checkingEndTime TIMESTAMP,
    fixingStartTime TIMESTAMP,
    completionTime TIMESTAMP,
    cancellationReason TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tasks_userId ON tasks(userId);
CREATE INDEX idx_tasks_workerId ON tasks(workerId);
CREATE INDEX idx_tasks_status ON tasks(currentStatus);
CREATE INDEX idx_tasks_createdAt ON tasks(createdAt);
```

#### ratings
```sql
CREATE TABLE ratings (
    id SERIAL PRIMARY KEY,
    taskId INT REFERENCES tasks(id),
    fromUserId INT REFERENCES users(id),
    toUserId INT REFERENCES users(id),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ratings_taskId ON ratings(taskId);
CREATE INDEX idx_ratings_fromUser ON ratings(fromUserId);
CREATE INDEX idx_ratings_toUser ON ratings(toUserId);
```

#### payments
```sql
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    workerId INT REFERENCES workers(id),
    taskId INT REFERENCES tasks(id),
    checkingFeeAmount DECIMAL(10,2) DEFAULT 300,
    fixingPriceAmount DECIMAL(10,2),
    platformFee DECIMAL(10,2), -- 20% of fixingPrice
    workerEarnings DECIMAL(10,2), -- 80% of fixingPrice + checkingFee
    paymentMethod VARCHAR(50), -- 'Instapay', 'Bank Transfer'
    receiptImageUrl TEXT,
    transactionId VARCHAR(100),
    status VARCHAR(50), -- 'Pending', 'Verified', 'Paid', 'Rejected'
    rejectionReason TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_workerId ON payments(workerId);
CREATE INDEX idx_payments_taskId ON payments(taskId);
CREATE INDEX idx_payments_status ON payments(status);
```

#### auditLogs
```sql
CREATE TABLE auditLogs (
    id SERIAL PRIMARY KEY,
    userId INT REFERENCES users(id),
    action VARCHAR(255),
    details JSONB,
    ipAddress VARCHAR(45),
    userAgent TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_auditLogs_userId ON auditLogs(userId);
CREATE INDEX idx_auditLogs_createdAt ON auditLogs(createdAt);
```

### 11-State Task Lifecycle

```
REQUESTED 
    ↓ (worker accepts)
ACCEPTED 
    ↓ (worker arrives)
ARRIVED 
    ↓ (user confirms arrival)
ARRIVAL_CONFIRMED 
    ↓ (worker starts checking, 300 EGP charged)
CHECKING 
    ↓ (worker finishes inspection)
CHECKING_COMPLETED
    ↓ (user decides to proceed or cancel)
DECISION (no=CANCELLED, yes=continue) 
    ↓ (worker proposes fixing price)
PRICE_PROPOSED 
    ↓ (user accepts price)
PRICE_ACCEPTED 
    ↓ (worker performs fixing)
FIXING 
    ↓ (worker marks complete)
COMPLETED 
    ↓ (mutual ratings submitted)
[END - Task closed, worker earnings released]
```

---

## Authentication & Authorization

### Current System
- **Method:** Session-based (session_start())
- **User Types:** User, Worker, Admin
- **Storage:** $_SESSION['user_id'], $_SESSION['user_type']
- **Demo Accounts:**
  ```
  user@test.com / User@123456 (type: user)
  worker@test.com / Worker@123456 (type: worker)
  admin@test.com / Admin@123456 (type: admin)
  ```

### Missing Security Features
- ❌ CSRF tokens on all forms
- ❌ Password hashing in signup (bcrypt)
- ❌ Session regeneration after login
- ❌ API authentication (JWT)
- ❌ Rate limiting on login attempts
- ❌ Password reset functionality
- ❌ Email verification for signup

### Recommended Fixes
```php
// core/Auth.php (new file)
class Auth {
    public static function login($email, $password) {
        $user = /* query user by email */;
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            return true;
        }
        return false;
    }
    
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /pages/user/login.php');
            exit;
        }
    }
}
```

---

## API Endpoints Reference

### Base URL
```
Development: http://localhost:8000/api/api_task_state_machine.php
Production: https://api.flix.com/v1/tasks
```

### Authentication
**Status:** Not implemented - add JWT header: `Authorization: Bearer <token>`

### Endpoints (14 Total)

#### 1. Create Task
```
POST /create_task
Content-Type: application/json

{
    "specialization": "Plumbing",
    "description": "Leaking faucet in bathroom",
    "address": "123 Nile Street, Cairo",
    "urgency": "Normal"
}

Response 201:
{
    "success": true,
    "taskId": 2804,
    "status": "REQUESTED"
}
```

#### 2. Get Available Tasks
```
GET /get_available_tasks?workerId=42&specialization=Plumbing

Response 200:
{
    "tasks": [
        {
            "id": 2804,
            "userId": 10,
            "specialization": "Plumbing",
            "description": "...",
            "address": "...",
            "urgency": "Normal",
            "createdAt": "2026-05-27T10:00:00Z"
        }
    ]
}
```

#### 3. Accept Task
```
POST /accept_task
Content-Type: application/json

{
    "taskId": 2804,
    "workerId": 42
}

Response 201:
{
    "success": true,
    "taskId": 2804,
    "status": "ACCEPTED"
}
```

#### 4. Worker Arrived
```
POST /worker_arrived
{ "taskId": 2804, "workerId": 42 }

Response: { "status": "ARRIVED" }
```

#### 5. Confirm Arrival
```
POST /confirm_arrival
{ "taskId": 2804 }

Response: { "status": "ARRIVAL_CONFIRMED" }
```

#### 6. Start Checking
```
POST /start_checking
{ "taskId": 2804, "workerId": 42 }

Response: { "status": "CHECKING", "checkingFee": 300 }
```

#### 7. Complete Checking
```
POST /complete_checking
{ "taskId": 2804 }

Response: { "status": "CHECKING_COMPLETED" }
```

#### 8. User Decision
```
POST /user_decision
{ "taskId": 2804, "proceed": true/false }

Response: 
{
    "status": "DECISION",
    "nextStatus": "PRICE_PROPOSED" (if true) or "CANCELLED" (if false)
}
```

#### 9. Propose Price
```
POST /propose_price
{ "taskId": 2804, "workerId": 42, "proposedPrice": 500 }

Response: { "status": "PRICE_PROPOSED", "proposedPrice": 500 }
```

#### 10. Accept Price
```
POST /accept_price
{ "taskId": 2804, "fixingPrice": 500 }

Response: { "status": "PRICE_ACCEPTED" }
```

#### 11. Mark Fixing Complete
```
POST /mark_fixing_complete
{ "taskId": 2804, "workerId": 42 }

Response: { "status": "COMPLETED" }
```

#### 12. Confirm Completion
```
POST /confirm_completion
{ "taskId": 2804 }

Response: 
{
    "success": true,
    "workerEarnings": 900,
    "platformFee": 100
}
```

#### 13. Submit Ratings
```
POST /submit_ratings
{
    "taskId": 2804,
    "rater": "worker", // or "user"
    "ratingValue": 5,
    "comment": "Great worker!"
}

Response:
{
    "success": true,
    "workerAverageRating": 4.8,
    "userAverageRating": 4.5
}
```

#### 14. Get Task
```
GET /get_task?taskId=2804

Response:
{
    "task": {
        "id": 2804,
        "userId": 10,
        "workerId": 42,
        "specialization": "Plumbing",
        "currentStatus": "REQUESTED",
        "checkingFee": 300,
        "fixingPrice": null,
        ...
    }
}
```

### Error Responses
```
400 Bad Request:
{ "error": "Missing required field: taskId" }

401 Unauthorized:
{ "error": "Authentication required" }

404 Not Found:
{ "error": "Task not found" }

409 Conflict:
{ "error": "Invalid state transition: ACCEPTED → CHECKING" }
```

---

## Design System & UI

### Color Palette
```
Primary Green:      #1A6B4A (dark green)
Secondary Green:    #2D9A6C (medium green)
Light Background:   #E8F5EE (very light green)
White:              #FFFFFF
Light Gray:         #F7F8F6
Very Light Gray:    #F0F2EE
Dark Text:          #1A1A1A
Gray Text:          #666666
Border:             #D0D0D0
Success:            #27AE60 (green)
Error:              #E74C3C (red)
Warning:            #F39C12 (orange)
Info:               #3498DB (blue)
```

### Typography
```
Headers:     Segoe UI, sans-serif, 600 weight
Body:        Segoe UI, sans-serif, 400 weight
Size Scale:  12px, 14px, 16px, 18px, 20px, 24px, 32px, 40px
Line Height: 1.6 for body, 1.3 for headers
```

### Spacing (8px grid)
```
xs: 4px    (2x grid)
sm: 8px    (1x grid)
md: 16px   (2x grid)
lg: 24px   (3x grid)
xl: 32px   (4x grid)
2xl: 48px  (6x grid)
```

### Components
- **Buttons:** Rounded 8px, shadow 0 2px 12px rgba(0,0,0,0.06)
- **Cards:** Rounded 14px, shadow same as buttons
- **Forms:** Input height 44px, border-radius 8px
- **Modals:** 300px min-width, center overlay
- **Badges:** Inline labels with background-color
- **Timeline:** Vertical layout with connecting line

### Responsive Breakpoints
```
Mobile:  0px - 480px
Tablet:  480px - 640px
Desktop: 640px+

Media Query Pattern:
@media (min-width: 480px) { /* tablet styles */ }
@media (min-width: 640px) { /* desktop styles */ }
```

### Bilingual Support
- **Language Selector:** ?lang=en or ?lang=ar query parameter
- **Direction:** `dir="rtl"` on html tag for Arabic
- **Storage:** $_SESSION['lang'] persistent across pages
- **Translations:** Hardcoded in lang.php (no translation file)

### Current Stylesheets
1. **app.css** (main) - Primary stylesheet with design tokens
2. **design-tokens.css** - CSS variables
3. **components.css** - Reusable component styles
4. **responsive.css** - Mobile-first responsive design
5. **typography.css** - Font hierarchy
6. **animations.css** - Transitions & micro-interactions
7. **variables.css** - Custom properties
8. **loaders.css** - Loading spinners
9. **modern-ui.css** - Enhanced styling
10. **premium-ui.css** - Premium components
11. **responsive-utilities.css** - Utility classes
12. **animations-enhanced.css** - Advanced animations

---

## Critical Fixes Needed

### Priority 1 - Security (Do Before Production)

#### 1.1 Add CSRF Protection to All Forms
```php
// In page header after session_start():
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In every <form>:
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// In every form handler:
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid CSRF token');
}
```

#### 1.2 Implement Password Hashing
```php
// signup.php - Replace plain text storage:
$hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 10]);
// Store $hashedPassword in database

// login.php - Update verification:
if (password_verify($_POST['password'], $user['password_hash'])) {
    // Login successful
}
```

#### 1.3 Add Session Security
```php
// core/config.php:
session_set_cookie_params([
    'secure' => true,  // HTTPS only
    'httponly' => true, // JS can't access
    'samesite' => 'Lax'  // CSRF protection
]);

// After login in login.php:
session_regenerate_id(true);
```

#### 1.4 Sanitize All Output
```php
// Replace all <?php echo $var; ?> with:
<?php echo htmlspecialchars($var, ENT_QUOTES, 'UTF-8'); ?>
```

#### 1.5 Validate All API Requests
```php
// api/api_task_state_machine.php - Add at top:
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Add authentication:
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}
```

### Priority 2 - Database & Data Integrity (Do Before MVP Launch)

#### 2.1 Create Database Migration Script
```sql
-- database/schema.sql
CREATE TABLE users ( /* ... */ );
CREATE TABLE workers ( /* ... */ );
/* ... all tables ... */
```

#### 2.2 Implement Database Connection Retry Logic
```php
// core/db.php - Add retry:
$retries = 3;
while ($retries > 0) {
    try {
        $connection = pg_connect($connection_string);
        if ($connection) break;
    } catch (Exception $e) {
        $retries--;
        sleep(1);
    }
}
```

#### 2.3 Add Database Validation
```php
// After every query:
if (pg_last_error($connection)) {
    error_log('Database error: ' . pg_last_error($connection));
    http_response_code(500);
    exit(json_encode(['error' => 'Database error']));
}
```

### Priority 3 - Testing & QA (Do Before Public Launch)

#### 3.1 Create Unit Tests
```php
// tests/UserTest.php
class UserTest {
    public function testUserCanRegister() { /* ... */ }
    public function testWorkerCanAcceptTask() { /* ... */ }
    public function testInvalidStateTransitionFails() { /* ... */ }
}
```

#### 3.2 Add Integration Tests
```php
// tests/TaskLifecycleTest.php
class TaskLifecycleTest {
    public function testCompleteTaskFlow() {
        // 1. Create task
        // 2. Worker accepts
        // 3. All 11 states transition
        // 4. Payment calculated
        // 5. Ratings submitted
    }
}
```

#### 3.3 End-to-End Testing Script
```bash
#!/bin/bash
# test/e2e.sh
php -S localhost:8000 &
npm run dev # Start Socket.io server
# Run browser tests via Selenium/Puppeteer
```

### Priority 4 - User Experience

#### 4.1 Add Timeout Handling
```php
// pages/user/track.php - Add refresh logic:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    // Not an AJAX call - redirect after delay
    header('Refresh: 2; url=' . $_SERVER['REQUEST_URI']);
}
```

#### 4.2 Implement Duplicate Submission Prevention
```php
// In forms - add token:
<input type="hidden" name="submit_token" value="<?php echo uniqid(); ?>">

// Handler - check if already processed:
if ($_POST['submit_token'] === $_SESSION['last_submit_token']) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
$_SESSION['last_submit_token'] = $_POST['submit_token'];
```

#### 4.3 Add Better Error Messages
```php
// Replace generic errors with:
function showError($type, $message) {
    $errors = [
        'task_not_found' => 'The task request #TASKID was not found or has expired.',
        'worker_busy' => 'You are currently assigned to another task.',
        'invalid_state' => 'This action is not allowed at the current task stage.'
    ];
    echo "<div class='alert alert-error'>" . htmlspecialchars($errors[$type] ?? $message) . "</div>";
}
```

---

## Next Development Phases

### Phase 3: Complete Worker Workflow (2-3 days)
**Goal:** Enable workers to accept tasks and propose prices

**Tasks:**
1. ✅ Create worker_available_requests.php
   - Display REQUESTED tasks filtered by worker specializations
   - Add task card layout with task details
   - Implement 'Accept Task' button

2. ✅ Create worker_task_workflow.php
   - Show current assigned task status
   - Display timeline and action buttons
   - Real-time updates via Socket.io

3. Create worker_propose_price.php
   - After checking complete, worker enters fixing price
   - Form validation (must be > 0)
   - Success page with price proposal confirmation

4. Test end-to-end flow
   - Create task as user
   - Accept as worker
   - Propose price
   - Accept price
   - Complete task

**Estimated Time:** 2 days

### Phase 4: Payment & Completion System (2-3 days)
**Goal:** Implement payment submission and task completion

**Tasks:**
1. Create worker_payment_submit.php
   - Instapay receipt upload interface
   - Receipt validation (JPEG/PNG/PDF)
   - Cloudinary integration

2. Create payment verification admin page
   - Display payment submissions
   - Verify receipt legitimacy
   - Approve/reject with reason
   - Transfer funds (mock for now)

3. Implement task completion flow
   - Mark task as complete
   - Calculate earnings (80/20 split)
   - Release payment to worker

4. Create receipt viewing pages
   - User can view payment receipts
   - Worker can view earnings history

**Estimated Time:** 2-3 days

### Phase 5: Real-Time Features (1-2 days)
**Goal:** Add live notifications and status updates

**Tasks:**
1. Wire Socket.io to PHP pages
   - Create event emitters in PHP
   - Subscribe to task updates
   - Update UI in real-time

2. Add notification bell
   - Show badge count of unread notifications
   - Dropdown list of recent notifications
   - Mark as read functionality

3. Implement live status updates
   - Worker location tracking (optional)
   - Live chat between user and worker
   - Typing indicators

**Estimated Time:** 1-2 days

### Phase 6: Security & Polish (2 days)
**Goal:** Harden security and improve UX

**Tasks:**
1. Add CSRF tokens to all forms
2. Implement password hashing
3. Add rate limiting
4. Implement email notifications
5. Test bilingual UI
6. Mobile responsiveness testing

**Estimated Time:** 2 days

### Phase 7: Testing & Deployment (3-5 days)
**Goal:** Full QA and production deployment

**Tasks:**
1. Create test suite
2. Perform security audit
3. Load testing
4. Mobile testing
5. Deploy to Railway/Heroku
6. Monitor production

**Estimated Time:** 3-5 days

**Total Timeline to MVP:** 12-17 days

---

## Deployment & Operations

### Development Environment
```bash
# Start PHP dev server
php -S localhost:8000 &

# Start Node.js Socket.io server  
cd node && node server.js &

# Access application
http://localhost:8000
```

### Environment Variables (.env)
```
# Database
DATABASE_URL=postgresql://user:pass@localhost/flix_db
DATABASE_FALLBACK=sqlite:flix.db

# Cloudinary
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret

# Email (future)
MAIL_PROVIDER=sendgrid
SENDGRID_API_KEY=your_key

# SMS (future)
SMS_PROVIDER=twilio
TWILIO_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_PHONE=+1234567890

# Socket.io
SOCKET_IO_HOST=localhost
SOCKET_IO_PORT=3001

# Session
SESSION_SECRET=random_long_string_here
```

### Docker Deployment
```bash
# Build image
docker build -t flix:latest .

# Run container
docker run -p 8000:8000 -e DATABASE_URL=... flix:latest

# Or use docker-compose
docker-compose up -d
```

### Railway Deployment
```bash
# Install Railway CLI
npm i -g @railway/cli

# Configure project
railway init

# Deploy
railway up
```

### Production Checklist
- [ ] HTTPS enabled (self-signed cert minimum)
- [ ] Database password set (no default credentials)
- [ ] CSRF tokens on all forms
- [ ] Rate limiting enabled (login, API)
- [ ] Error logging to file
- [ ] Backup strategy defined
- [ ] Monitoring & alerting set up
- [ ] Cloudinary credentials secure
- [ ] Session cookie secure flags set
- [ ] Database indexes optimized

---

## Performance & Security Recommendations

### Database Optimization
```sql
-- Current indexes:
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_tasks_status ON tasks(currentStatus);
CREATE INDEX idx_tasks_createdAt ON tasks(createdAt);
CREATE INDEX idx_workers_specializations ON workers USING GIN(specializations);

-- Add these for better performance:
CREATE INDEX idx_tasks_userId_status ON tasks(userId, currentStatus);
CREATE INDEX idx_tasks_workerId_status ON tasks(workerId, currentStatus);
CREATE INDEX idx_ratings_toUser ON ratings(toUserId);
CREATE INDEX idx_payments_status_createdAt ON payments(status, createdAt);
```

### Caching Strategy
```php
// Use Redis or file cache for frequently accessed data
// Cache worker specializations
// Cache active task count
// Cache user ratings

// Invalidate on updates:
// - User registration → invalidate all workers
// - Task completion → invalidate worker cache
```

### Security Headers
```php
// Add to core/config.php:
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
```

### SQL Query Optimization
```php
// Use prepared statements:
$result = pg_query_params($connection, 
    'SELECT * FROM tasks WHERE userId = $1 AND currentStatus = $2 LIMIT 50',
    [$userId, 'REQUESTED']
);

// Avoid N+1 queries:
// Bad: Loop through tasks and fetch user for each
// Good: JOIN with users table

// Use LIMIT & OFFSET for pagination:
SELECT * FROM tasks WHERE userId = $1 LIMIT 50 OFFSET 0;
```

### Monitoring & Logging
```php
// Log all critical actions:
function logAction($action, $userId, $details) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'userId' => $userId,
        'details' => json_encode($details),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    // Log to file
    file_put_contents('logs/actions.log', json_encode($log) . "\n", FILE_APPEND);
    
    // Log to database
    pg_query_params($connection,
        'INSERT INTO auditLogs (userId, action, details, ipAddress) VALUES ($1, $2, $3, $4)',
        [$userId, $action, json_encode($details), $_SERVER['REMOTE_ADDR']]
    );
}
```

---

## Testing Guide

### Manual Testing Checklist

#### User Flow
- [ ] Register as user (signup.php)
- [ ] Login with created account
- [ ] Create task (task_create.php)
- [ ] View task timeline (track.php) - all 11 states visible
- [ ] Receive notification when worker accepts (future: real-time)

#### Worker Flow
- [ ] Register as worker (signup.php with Worker type)
- [ ] Get approved by admin
- [ ] Browse available tasks (worker_available_requests.php)
- [ ] Accept task
- [ ] Navigate through all 11 states
- [ ] Propose price
- [ ] Submit payment receipt
- [ ] Rate user after completion

#### Admin Flow
- [ ] Login as admin
- [ ] View admin dashboard
- [ ] Approve worker applications
- [ ] Verify payment receipts
- [ ] View system statistics

### Automated Testing
```bash
# Install test framework
composer require phpunit/phpunit

# Run tests
php vendor/bin/phpunit tests/

# Coverage report
php vendor/bin/phpunit --coverage-html coverage/ tests/
```

---

## Troubleshooting Guide

### Common Issues & Solutions

#### Issue: Database Connection Error
```
Error: "pg_query_params(): Argument #1 ($connection) must be of type resource, null given"

Solution:
1. Check DATABASE_URL environment variable
2. Verify PostgreSQL server is running
3. Check credentials in .env file
4. Use SQLite fallback if PostgreSQL unavailable
```

#### Issue: Session Not Persisting
```
Error: User logged in but redirected to login on next page

Solution:
1. Verify session_start() called at top of every file
2. Check session cookie settings
3. Ensure SESSION_SECRET is set
4. Check browser cookie storage is enabled
```

#### Issue: File Upload Fails
```
Error: "Cloudinary upload failed"

Solution:
1. Verify CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET
2. Check file size < 10MB
3. Verify MIME type (JPEG/PNG for images)
4. Check PHP upload_max_filesize (php.ini)
```

#### Issue: Socket.io Not Working
```
Error: Real-time updates not received

Solution:
1. Verify Node.js server running (node server.js)
2. Check Socket.io port is open (3001 or 3000)
3. Check firewall rules
4. Verify socket-client.js loaded in page
```

---

## Glossary

| Term | Definition |
|------|-----------|
| **State** | Current status of task (REQUESTED, ACCEPTED, etc.) |
| **Checking Fee** | Mandatory 300 EGP inspection fee |
| **Fixing Price** | Negotiated service completion price |
| **Platform Fee** | 20% of fixing price |
| **Worker Earnings** | 80% of fixing price + checking fee |
| **Task ID** | Unique identifier for service request |
| **Worker** | Service provider (plumber, electrician, etc.) |
| **User** | Service requester (person needing work done) |
| **Rating** | 5-star quality score with optional comment |
| **RTL** | Right-to-Left text direction (Arabic) |
| **CSRF Token** | Cross-Site Request Forgery protection |
| **Demo Mode** | Testing mode with mock data (no database) |

---

## Changelog

### Version 1.0.0 (May 27, 2026)
- Initial documentation
- 10 major deliverables completed
- 20 identified issues (3 critical, 7 high, 5 medium, 5 low)
- Roadmap for 7 development phases

---

## Appendix: Quick Commands

### Start Development Server
```bash
php -S localhost:8000 &
node flix/node/server.js &
```

### Access Application
```
http://localhost:8000
http://localhost:8000/pages/user/signup.php
http://localhost:8000/pages/user/login.php
http://localhost:8000/pages/admin/admin_dashboard.php
```

### View Logs
```bash
tail -f logs/error.log
tail -f logs/access.log
```

### Database Management
```bash
# PostgreSQL
psql postgresql://user:pass@localhost/flix_db
\dt  # List tables
\d users  # Describe table

# SQLite  
sqlite3 flix.db
.tables
.schema users
```

### File Cleanup (Already Done)
```bash
# Remove backup files
rm *.bak

# Remove temporary files
rm *.new

# Remove debug files
rm debug.php DASHBOARD_TEMPLATE.php
```

---

**Document End**

For questions or updates, contact the development team.
