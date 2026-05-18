/**
 * FLIX API Error Handler & Response Formatter
 * Centralized error handling and standardized responses
 */

class APIError extends Error {
  constructor(message, statusCode = 500, code = 'INTERNAL_ERROR') {
    super(message);
    this.statusCode = statusCode;
    this.code = code;
    this.timestamp = new Date().toISOString();
  }
}

/**
 * Standardized API response formatter
 */
class APIResponse {
  /**
   * Success response
   */
  static success(data, message = 'Success', statusCode = 200) {
    return {
      success: true,
      statusCode,
      message,
      data,
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Paginated success response
   */
  static paginated(data, page, limit, total, message = 'Success') {
    return {
      success: true,
      statusCode: 200,
      message,
      data,
      pagination: {
        page: parseInt(page) || 1,
        limit: parseInt(limit) || 20,
        total,
        pages: Math.ceil(total / (parseInt(limit) || 20)),
        hasNextPage: page * limit < total,
        hasPrevPage: page > 1,
      },
      timestamp: new Date().toISOString(),
    };
  }

  /**
   * Error response
   */
  static error(message, statusCode = 500, code = 'INTERNAL_ERROR', details = null) {
    const response = {
      success: false,
      statusCode,
      error: {
        code,
        message,
        timestamp: new Date().toISOString(),
      },
    };

    if (details) {
      response.error.details = details;
    }

    return response;
  }

  /**
   * Validation error response
   */
  static validationError(errors, message = 'Validation failed') {
    return {
      success: false,
      statusCode: 400,
      message,
      error: {
        code: 'VALIDATION_ERROR',
        message,
        details: errors,
        timestamp: new Date().toISOString(),
      },
    };
  }
}

/**
 * Express error handler middleware
 */
function errorHandler(err, req, res, next) {
  // Log error
  console.error({
    timestamp: new Date().toISOString(),
    path: req.path,
    method: req.method,
    error: {
      message: err.message,
      code: err.code || 'UNKNOWN_ERROR',
      stack: process.env.NODE_ENV === 'development' ? err.stack : undefined,
    },
  });

  // Default error values
  let statusCode = err.statusCode || 500;
  let code = err.code || 'INTERNAL_SERVER_ERROR';
  let message = err.message || 'An unexpected error occurred';

  // Handle specific error types
  if (err.name === 'ValidationError') {
    statusCode = 400;
    code = 'VALIDATION_ERROR';
    message = 'Request validation failed';
  }

  if (err.name === 'UnauthorizedError') {
    statusCode = 401;
    code = 'UNAUTHORIZED';
    message = 'Authentication required';
  }

  if (err.name === 'NotFoundError') {
    statusCode = 404;
    code = 'NOT_FOUND';
    message = 'Resource not found';
  }

  if (err.name === 'ConflictError') {
    statusCode = 409;
    code = 'CONFLICT';
    message = 'Resource already exists';
  }

  // Handle database errors
  if (err.code && err.code.startsWith('23')) {
    // PostgreSQL constraint errors
    if (err.code === '23505') {
      statusCode = 409;
      code = 'DUPLICATE_ENTRY';
      message = 'This resource already exists';
    } else {
      statusCode = 400;
      code = 'DATABASE_CONSTRAINT_ERROR';
      message = 'Database constraint violation';
    }
  }

  // Send error response
  res.status(statusCode).json(
    APIResponse.error(message, statusCode, code, process.env.NODE_ENV === 'development' ? err.stack : undefined)
  );
}

/**
 * Async route wrapper to catch errors
 */
function asyncHandler(fn) {
  return (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
}

/**
 * Auth middleware for protected routes
 */
function requireAuth(req, res, next) {
  const token = req.headers.authorization?.replace('Bearer ', '');

  if (!token) {
    return res.status(401).json(APIResponse.error('Authentication required', 401, 'UNAUTHORIZED'));
  }

  try {
    // JWT verification would happen here
    // const decoded = jwt.verify(token, config.JWT.secret);
    // req.user = decoded;
    next();
  } catch (error) {
    return res.status(401).json(APIResponse.error('Invalid or expired token', 401, 'INVALID_TOKEN'));
  }
}

/**
 * Role-based access control middleware
 */
function requireRole(allowedRoles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json(APIResponse.error('Authentication required', 401, 'UNAUTHORIZED'));
    }

    if (!allowedRoles.includes(req.user.role)) {
      return res.status(403).json(APIResponse.error('Insufficient permissions', 403, 'FORBIDDEN'));
    }

    next();
  };
}

/**
 * Rate limiting handler
 */
function handleRateLimit(req, res, next) {
  res.setHeader('RateLimit-Limit', req.rateLimit.limit);
  res.setHeader('RateLimit-Remaining', req.rateLimit.current);
  res.setHeader('RateLimit-Reset', req.rateLimit.resetTime);
  next();
}

/**
 * CORS handler
 */
function setCORSHeaders(allowedOrigins) {
  return (req, res, next) => {
    const origin = req.headers.origin;

    if (allowedOrigins.includes(origin) || allowedOrigins.includes('*')) {
      res.setHeader('Access-Control-Allow-Origin', origin || '*');
      res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
      res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
      res.setHeader('Access-Control-Allow-Credentials', 'true');
    }

    if (req.method === 'OPTIONS') {
      return res.sendStatus(200);
    }

    next();
  };
}

/**
 * Request logging middleware
 */
function requestLogger(req, res, next) {
  const start = Date.now();

  res.on('finish', () => {
    const duration = Date.now() - start;
    console.log({
      timestamp: new Date().toISOString(),
      method: req.method,
      path: req.path,
      statusCode: res.statusCode,
      duration: `${duration}ms`,
      ip: req.ip || req.connection.remoteAddress,
    });
  });

  next();
}

module.exports = {
  APIError,
  APIResponse,
  errorHandler,
  asyncHandler,
  requireAuth,
  requireRole,
  handleRateLimit,
  setCORSHeaders,
  requestLogger,
};
