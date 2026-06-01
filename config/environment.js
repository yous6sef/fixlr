/**
 * FLIX Platform - Environment Configuration
 * Centralized configuration management for all environments
 */

require('dotenv').config();

const config = {
  // App Settings
  APP_NAME: process.env.APP_NAME || 'Flix Marketplace',
  APP_VERSION: process.env.APP_VERSION || '1.0.0',
  APP_ENV: process.env.NODE_ENV || 'development',
  APP_URL: process.env.APP_URL || 'http://localhost:3001',
  FRONTEND_URL: process.env.FRONTEND_URL || 'http://localhost:3001',

  // Server Configuration
  PORT: process.env.PORT || 3001,
  SOCKET_PORT: process.env.SOCKET_PORT || 3000,
  HOST: process.env.HOST || 'localhost',

  // Database Configuration
  DATABASE: {
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 5432,
    user: process.env.DB_USER || 'postgres',
    password: process.env.DB_PASS || 'postgres',
    database: process.env.DB_NAME || 'flix',
    connectionString: process.env.DATABASE_URL,
    ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: false } : false,
    pool: {
      min: parseInt(process.env.DB_POOL_MIN || '2'),
      max: parseInt(process.env.DB_POOL_MAX || '20'),
      idleTimeoutMillis: parseInt(process.env.DB_POOL_IDLE || '30000'),
      connectionTimeoutMillis: parseInt(process.env.DB_CONN_TIMEOUT || '2000'),
    },
  },

  // Redis Configuration
  REDIS: {
    host: process.env.REDIS_HOST || 'localhost',
    port: process.env.REDIS_PORT || 6379,
    password: process.env.REDIS_PASS || undefined,
    db: parseInt(process.env.REDIS_DB || '0'),
    ttl: parseInt(process.env.REDIS_TTL || '3600'), // 1 hour default
  },

  // JWT Configuration
  JWT: {
    secret: process.env.JWT_SECRET || 'flix-super-secret-jwt-key-change-in-production',
    expiresIn: process.env.JWT_EXPIRES_IN || '7d',
    refreshExpiresIn: process.env.JWT_REFRESH_EXPIRES_IN || '30d',
    algorithm: 'HS256',
  },

  // Security
  SECURITY: {
    bcryptRounds: parseInt(process.env.BCRYPT_ROUNDS || '10'),
    corsOrigins: (process.env.CORS_ORIGINS || 'http://localhost:3001,http://localhost:8000').split(','),
    rateLimit: {
      windowMs: parseInt(process.env.RATE_LIMIT_WINDOW || '900000'), // 15 minutes
      maxRequests: parseInt(process.env.RATE_LIMIT_MAX || '100'),
      maxLoginAttempts: parseInt(process.env.MAX_LOGIN_ATTEMPTS || '5'),
      loginWindow: parseInt(process.env.LOGIN_WINDOW || '900000'), // 15 minutes
    },
  },

  // Third-party APIs
  APIs: {
    HERE: {
      apiKey: process.env.HERE_API_KEY || 'zbTcdxdMTu88G-q5LfQMBbALRFN7M0BMd4sEWPOLgmU',
      baseUrl: 'https://geocode.search.hereapi.com/v1',
    },
    STRIPE: {
      secretKey: process.env.STRIPE_SECRET_KEY,
      publishableKey: process.env.STRIPE_PUBLIC_KEY,
      webhookSecret: process.env.STRIPE_WEBHOOK_SECRET,
    },
    GOOGLE_AI: {
      apiKey: process.env.GOOGLE_AI_KEY,
      model: 'gemini-1.5-flash',
    },
    EMAIL: {
      provider: process.env.EMAIL_PROVIDER || 'sendgrid', // sendgrid or mailgun
      apiKey: process.env.EMAIL_API_KEY,
      fromEmail: process.env.EMAIL_FROM || 'noreply@flixmarketplace.com',
      fromName: process.env.EMAIL_FROM_NAME || 'Flix Marketplace',
    },
    FIREBASE: {
      apiKey: process.env.FIREBASE_API_KEY,
      authDomain: process.env.FIREBASE_AUTH_DOMAIN,
      projectId: process.env.FIREBASE_PROJECT_ID,
      storageBucket: process.env.FIREBASE_STORAGE_BUCKET,
    },
  },

  // Logging
  LOGGING: {
    level: process.env.LOG_LEVEL || 'info',
    format: process.env.LOG_FORMAT || 'json', // json or text
    destination: process.env.LOG_DESTINATION || 'file', // file or console
    maxSize: process.env.LOG_MAX_SIZE || '10m',
    maxFiles: parseInt(process.env.LOG_MAX_FILES || '14'),
  },

  // Error Tracking
  SENTRY: {
    enabled: process.env.SENTRY_ENABLED === 'true',
    dsn: process.env.SENTRY_DSN,
    environment: process.env.NODE_ENV || 'development',
    tracesSampleRate: parseFloat(process.env.SENTRY_TRACE_RATE || '0.1'),
  },

  // Storage
  STORAGE: {
    type: process.env.STORAGE_TYPE || 'local', // local or s3
    uploadDir: process.env.UPLOAD_DIR || './uploads',
    maxFileSize: parseInt(process.env.MAX_FILE_SIZE || '5242880'), // 5MB
    allowedMimes: (process.env.ALLOWED_MIMES || 'image/jpeg,image/png,application/pdf').split(','),
  },

  // Business Logic
  BUSINESS: {
    commissionPercentage: parseFloat(process.env.COMMISSION_PERCENT || '0.20'), // 20%
    taxPercentage: parseFloat(process.env.TAX_PERCENT || '0.15'), // 15%
    workerApprovalRadius: parseFloat(process.env.APPROVAL_RADIUS || '5'), // 5km
    minWorkerRating: parseFloat(process.env.MIN_WORKER_RATING || '3.5'),
    unpaidThreshold: parseInt(process.env.UNPAID_THRESHOLD || '7'), // 7 days
  },

  // Feature Flags
  FEATURES: {
    stripePayments: process.env.FEATURE_STRIPE === 'true',
    emailNotifications: process.env.FEATURE_EMAIL === 'true',
    audioNotifications: process.env.FEATURE_AUDIO === 'true',
    advancedSearch: process.env.FEATURE_SEARCH === 'true',
    aiDescriptions: process.env.FEATURE_AI === 'true',
  },

  // Production Checks
  isDevelopment() {
    return this.APP_ENV === 'development';
  },

  isProduction() {
    return this.APP_ENV === 'production';
  },

  isStaging() {
    return this.APP_ENV === 'staging';
  },

  // Validation
  validate() {
    const errors = [];

    if (this.isProduction()) {
      if (!process.env.JWT_SECRET) errors.push('JWT_SECRET is required in production');
      if (!process.env.DB_HOST) errors.push('DB_HOST is required in production');
      if (process.env.CORS_ORIGINS === '*') errors.push('CORS_ORIGINS should not be * in production');
    }

    if (this.SENTRY.enabled && !this.SENTRY.dsn) {
      errors.push('SENTRY_DSN is required when SENTRY_ENABLED=true');
    }

    if (errors.length > 0) {
      console.error('❌ Configuration Errors:', errors);
      if (this.isProduction()) {
        process.exit(1);
      }
    }

    return errors.length === 0;
  },
};

module.exports = config;
