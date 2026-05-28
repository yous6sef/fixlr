-- ============================================================
-- FLIX MARKETPLACE - COMPLETE DATABASE SCHEMA
-- PostgreSQL Implementation
-- ============================================================

-- ============ PostgreSQL Enum Types ============
CREATE TYPE user_type_enum AS ENUM ('user', 'worker', 'admin');
CREATE TYPE account_status_enum AS ENUM ('active', 'inactive', 'suspended');
CREATE TYPE worker_status_enum AS ENUM ('PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'SUSPENDED');
CREATE TYPE task_status_enum AS ENUM (
    'REQUESTED',
    'ACCEPTED',
    'ARRIVED',
    'ARRIVAL_CONFIRMED',
    'CHECKING',
    'CHECKING_COMPLETED',
    'DECISION',
    'PRICE_PROPOSED',
    'PRICE_ACCEPTED',
    'FIXING',
    'COMPLETED',
    'CANCELLED_AFTER_CHECK',
    'CANCELLED'
);
CREATE TYPE payment_type_enum AS ENUM ('INSTAPAY', 'MANUAL');
CREATE TYPE payment_status_enum AS ENUM ('PENDING', 'VERIFIED', 'REJECTED', 'COMPLETED');

-- ============ USERS TABLE ============
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phoneNumber VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    userType user_type_enum NOT NULL DEFAULT 'user',
    city VARCHAR(100) NOT NULL DEFAULT '6th of October City',
    profileImage VARCHAR(500),
    bio TEXT,
    totalRating DECIMAL(3, 2) DEFAULT 0.00,
    totalReviews INT DEFAULT 0,
    accountStatus account_status_enum DEFAULT 'active',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ WORKERS TABLE ============
CREATE TABLE IF NOT EXISTS workers (
    id SERIAL PRIMARY KEY,
    userId INT NOT NULL UNIQUE,
    idCardNumber VARCHAR(50) NOT NULL UNIQUE,
    idCardFrontUrl VARCHAR(500),
    idCardBackUrl VARCHAR(500),
    criminalRecordUrl VARCHAR(500),
    resumeUrl VARCHAR(500),
    specializations JSON NOT NULL DEFAULT '[]',
    residentialLocation VARCHAR(255) NOT NULL,
    workLocation VARCHAR(100) NOT NULL DEFAULT '6th of October City',
    workLocationUpdateable BOOLEAN DEFAULT TRUE,
    status worker_status_enum DEFAULT 'PENDING_APPROVAL',
    isCurrentlyAssigned BOOLEAN DEFAULT FALSE,
    availableBalance DECIMAL(10, 2) DEFAULT 0.00,
    totalEarnings DECIMAL(10, 2) DEFAULT 0.00,
    pendingRemittance DECIMAL(10, 2) DEFAULT 0.00,
    approvedAt TIMESTAMP NULL,
    rejectedAt TIMESTAMP NULL,
    rejectionReason VARCHAR(500),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

-- ============ TASKS TABLE (Core State Machine) ============
CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    userId INT NOT NULL,
    workerId INT,
    city VARCHAR(100) NOT NULL,
    service_type VARCHAR(100) NOT NULL DEFAULT 'general',
    specialization VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    urgency VARCHAR(20) NOT NULL DEFAULT 'Normal',
    address TEXT,
    googleMapsLink VARCHAR(500),
    addressDescription TEXT,
    problemDescription TEXT,
    currentStatus task_status_enum DEFAULT 'REQUESTED',
    checkingFee DECIMAL(10, 2) DEFAULT 300.00,
    fixingPrice DECIMAL(10, 2),
    totalPrice DECIMAL(10, 2),
    userDecisionProceedWithFix BOOLEAN,
    statusHistory JSON DEFAULT '[]',
    assignedAt TIMESTAMP NULL,
    completedAt TIMESTAMP NULL,
    cancelledAt TIMESTAMP NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE SET NULL
);

-- ============ RATINGS TABLE ============
CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    taskId INT NOT NULL,
    raterId INT NOT NULL,
    ratedToWorkerId INT,
    ratedToUserId INT,
    rating INT NOT NULL,
    comment TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (taskId, raterId),
    FOREIGN KEY (taskId) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (raterId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ratedToWorkerId) REFERENCES workers(id) ON DELETE SET NULL,
    FOREIGN KEY (ratedToUserId) REFERENCES users(id) ON DELETE SET NULL
);

-- ============ PAYMENTS TABLE (Instapay Tracking) ============
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    taskId INT NOT NULL,
    workerId INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    paymentType payment_type_enum DEFAULT 'INSTAPAY',
    instapayDestination VARCHAR(50) DEFAULT 'test@instapay',
    transactionId VARCHAR(100),
    receiptImageUrl VARCHAR(500),
    status payment_status_enum DEFAULT 'PENDING',
    verifiedAt TIMESTAMP NULL,
    verifiedByAdminId INT,
    rejectionReason VARCHAR(500),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (taskId) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE CASCADE,
    FOREIGN KEY (verifiedByAdminId) REFERENCES users(id) ON DELETE SET NULL
);

-- ============ AUDIT LOG TABLE ============
CREATE TABLE IF NOT EXISTS auditLogs (
    id SERIAL PRIMARY KEY,
    adminId INT,
    action VARCHAR(255) NOT NULL,
    targetTable VARCHAR(100) NOT NULL,
    targetId INT NOT NULL,
    oldData JSON,
    newData JSON,
    reason VARCHAR(500),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (adminId) REFERENCES users(id) ON DELETE SET NULL
);

-- ============ INDEXES FOR PERFORMANCE ============
CREATE INDEX idx_workers_userId ON workers(userId);
CREATE INDEX idx_workers_status ON workers(status);
CREATE INDEX idx_workers_isCurrentlyAssigned ON workers(isCurrentlyAssigned);
CREATE INDEX idx_tasks_userId ON tasks(userId);
CREATE INDEX idx_tasks_workerId ON tasks(workerId);
CREATE INDEX idx_tasks_currentStatus ON tasks(currentStatus);
CREATE INDEX idx_tasks_city_specialization ON tasks(city, specialization);
CREATE INDEX idx_payments_taskId ON payments(taskId);
CREATE INDEX idx_payments_workerId ON payments(workerId);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_ratings_taskId ON ratings(taskId);
CREATE INDEX idx_ratings_raterId ON ratings(raterId);
