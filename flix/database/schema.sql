-- ============================================================
-- FLIX MARKETPLACE - COMPLETE DATABASE SCHEMA
-- PostgreSQL Implementation
-- ============================================================

-- ============ USERS TABLE ============
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phoneNumber VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    userType ENUM('user', 'worker', 'admin') NOT NULL DEFAULT 'user',
    city VARCHAR(100) NOT NULL DEFAULT '6th of October City',
    profileImage VARCHAR(500),
    bio TEXT,
    totalRating DECIMAL(3, 2) DEFAULT 0.00,
    totalReviews INT DEFAULT 0,
    accountStatus ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    status ENUM('PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'SUSPENDED') DEFAULT 'PENDING_APPROVAL',
    isCurrentlyAssigned BOOLEAN DEFAULT FALSE,
    availableBalance DECIMAL(10, 2) DEFAULT 0.00,
    totalEarnings DECIMAL(10, 2) DEFAULT 0.00,
    pendingRemittance DECIMAL(10, 2) DEFAULT 0.00,
    approvedAt TIMESTAMP NULL,
    rejectedAt TIMESTAMP NULL,
    rejectionReason VARCHAR(500),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

-- ============ TASKS TABLE (Core State Machine) ============
CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    userId INT NOT NULL,
    workerId INT,
    city VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    currentStatus ENUM(
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
        'CANCELLED'
    ) DEFAULT 'REQUESTED',
    checkingFee DECIMAL(10, 2) DEFAULT 300.00,
    fixingPrice DECIMAL(10, 2),
    totalPrice DECIMAL(10, 2),
    userDecisionProceedWithFix BOOLEAN,
    statusHistory JSON DEFAULT '[]',
    assignedAt TIMESTAMP NULL,
    completedAt TIMESTAMP NULL,
    cancelledAt TIMESTAMP NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (workerId) REFERENCES workers(id) ON DELETE SET NULL
);

-- ============ RATINGS TABLE ============
CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    taskId INT NOT NULL UNIQUE,
    ratedByUserId INT,
    ratedToWorkerId INT,
    ratedByWorkerId INT,
    ratedToUserId INT,
    userRating INT,
    userComment TEXT,
    workerRating INT,
    workerComment TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taskId) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (ratedByUserId) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ratedToWorkerId) REFERENCES workers(id) ON DELETE SET NULL,
    FOREIGN KEY (ratedByWorkerId) REFERENCES workers(id) ON DELETE SET NULL,
    FOREIGN KEY (ratedToUserId) REFERENCES users(id) ON DELETE SET NULL
);

-- ============ PAYMENTS TABLE (Instapay Tracking) ============
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    workerId INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    paymentType ENUM('INSTAPAY', 'MANUAL') DEFAULT 'INSTAPAY',
    instapayDestination VARCHAR(50) DEFAULT 'test@instapay',
    transactionId VARCHAR(100),
    receiptImageUrl VARCHAR(500),
    status ENUM('PENDING', 'VERIFIED', 'REJECTED', 'COMPLETED') DEFAULT 'PENDING',
    verifiedAt TIMESTAMP NULL,
    verifiedByAdminId INT,
    rejectionReason VARCHAR(500),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
CREATE INDEX idx_payments_workerId ON payments(workerId);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_ratings_taskId ON ratings(taskId);
