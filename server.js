require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const path = require('path');
const { Pool } = require('pg');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const axios = require('axios');

// Import new modules
const config = require('./config/environment');
const { APIResponse, asyncHandler, errorHandler, requestLogger, setCORSHeaders } = require('./api/v1/middleware/error-handler');
const { Validator } = require('./api/v1/middleware/validator');

// Validate configuration
if (!config.validate()) {
  console.error('Configuration validation failed');
  process.exit(1);
}

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: config.SECURITY.corsOrigins,
    methods: ['GET', 'POST'],
    credentials: true,
  },
  transports: ['websocket', 'polling'],
});

// ========== MIDDLEWARE SETUP ==========

// Security headers
app.use(helmet());

// Logging middleware
app.use(requestLogger);

// CORS configuration
app.use(setCORSHeaders(config.SECURITY.corsOrigins));
app.use(cors({
  origin: config.SECURITY.corsOrigins,
  credentials: true,
}));

// Body parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Static files
app.use(express.static(path.join(__dirname)));

// Rate limiting
const limiter = rateLimit({
  windowMs: config.SECURITY.rateLimit.windowMs,
  max: config.SECURITY.rateLimit.maxRequests,
  message: 'Too many requests, please try again later',
  standardHeaders: true,
  legacyHeaders: false,
});
app.use('/api/', limiter);

// ========== ROUTES ==========

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'landing.html'));
});

app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    server: 'Flix Marketplace API Server',
    version: config.APP_VERSION,
    environment: config.APP_ENV,
    port: config.PORT,
    timestamp: new Date().toISOString(),
  });
});

// ========== API ROUTES ==========

// Services endpoint
app.get('/api/services', (req, res) => {
  const services = [
    { id: 'plumbing', name: 'سباكة', description: 'إصلاحات السباكة والصرف الصحي', icon: '🚰' },
    { id: 'electrical', name: 'كهرباء', description: 'أعمال الكهرباء والتركيبات', icon: '⚡' },
    { id: 'cleaning', name: 'تنظيف', description: 'تنظيف المنازل والمكاتب', icon: '🧹' },
    { id: 'carpentry', name: 'نجارة', description: 'أعمال النجارة والتركيب', icon: '🔨' },
    { id: 'painting', name: 'دهان', description: 'دهان وتجديد المباني', icon: '🎨' },
    { id: 'moving', name: 'نقل', description: 'خدمات النقل والتغليف', icon: '📦' },
  ];
  res.json(APIResponse.success(services, 'Services retrieved successfully'));
});

// Get nearby workers
app.get('/api/workers/nearby', (req, res) => {
  const { lat, lng, service } = req.query;
  
  if (!lat || !lng || !service) {
    return res.status(400).json(APIResponse.error('Missing required parameters', 400, 'INVALID_PARAMS'));
  }

  // Simulate finding nearby workers
  const workers = Array.from({ length: 7 }, (_, i) => ({
    id: `worker_${i + 1}`,
    name: `فني محترف ${i + 1}`,
    service: service,
    rating: 4.5 + Math.random() * 0.5,
    reviews: Math.floor(Math.random() * 500) + 50,
    distance: (Math.random() * 5).toFixed(1) + ' km',
    price: Math.floor(Math.random() * 200) + 50 + ' ج.م',
    avatar: `https://api.dicebear.com/7.x/avataaars/svg?seed=worker${i}`,
    available: true,
    eta: Math.floor(Math.random() * 15) + 10 + ' دقائق'
  }));

  res.json(APIResponse.success(workers, 'Nearby workers found'));
});

// Create service request
app.post('/api/service-requests', asyncHandler(async (req, res) => {
  const { userId, serviceId, location, description } = req.body;

  // Basic validation
  if (!serviceId || !location) {
    return res.status(400).json(APIResponse.validationError(
      { serviceId: 'Service is required', location: 'Location is required' }
    ));
  }

  // Simulate creating a request
  const requestId = `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  
  res.status(201).json(APIResponse.success(
    {
      id: requestId,
      serviceId,
      location,
      description,
      status: 'pending',
      createdAt: new Date().toISOString(),
    },
    'Service request created successfully',
    201
  ));
}));

// Get service request details
app.get('/api/service-requests/:requestId', (req, res) => {
  const { requestId } = req.params;

  res.json(APIResponse.success({
    id: requestId,
    status: 'searching',
    workersFound: 7,
    estimatedWait: '15-20 minutes',
    workers: []
  }, 'Request details retrieved'));
});

// Get users profile
app.get('/api/users/:userId', (req, res) => {
  const { userId } = req.params;

  res.json(APIResponse.success({
    id: userId,
    name: 'عميل Flix',
    email: 'customer@flix.com',
    phone: '+201000000000',
    avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=user1',
    rating: 4.8,
    totalRequests: 12,
    memberSince: '2024-01-15',
  }, 'User profile retrieved'));
});

// Database CONNECTION ==========

const pool = new Pool(config.DATABASE.connectionString ? 
  { connectionString: config.DATABASE.connectionString, ssl: config.DATABASE.ssl }
  : {
    host: config.DATABASE.host,
    port: config.DATABASE.port,
    user: config.DATABASE.user,
    password: config.DATABASE.password,
    database: config.DATABASE.database,
    ssl: config.DATABASE.ssl,
    ...config.DATABASE.pool,
  }
);

// Test database connection
pool.on('connect', () => {
  console.log('✅ Database connection established');
});

pool.on('error', (err) => {
  console.error('❌ Unexpected error on idle client', err);
});

// ========== SOCKET.IO SETUP ==========

// Active connections tracking
const activeUsers = new Map();
const activeWorkers = new Map();

io.on('connection', (socket) => {
  console.log(`🔌 User connected: ${socket.id}`);

  socket.on('authenticate', (token) => {
    try {
      const decoded = jwt.verify(token, config.JWT.secret);
      socket.userId = decoded.id;
      socket.userType = decoded.type;

      if (decoded.type === 'worker') {
        activeWorkers.set(decoded.id, { socketId: socket.id, online: true, timestamp: Date.now() });
        socket.broadcast.emit('worker_online', { workerId: decoded.id });
        console.log(`👷 Worker authenticated: ${decoded.id}`);
      } else {
        activeUsers.set(decoded.id, { socketId: socket.id, online: true, timestamp: Date.now() });
        console.log(`👤 User authenticated: ${decoded.id}`);
      }

      socket.emit('authenticated', { success: true, message: 'Authentication successful' });
    } catch (error) {
      console.error('Authentication error:', error.message);
      socket.emit('authentication_error', { message: 'Invalid token' });
      socket.disconnect();
    }
  });

  socket.on('disconnect', () => {
    console.log(`🔌 User disconnected: ${socket.id}`);
    
    // Clean up active connections
    for (const [id, data] of activeUsers.entries()) {
      if (data.socketId === socket.id) {
        activeUsers.delete(id);
        break;
      }
    }

    for (const [id, data] of activeWorkers.entries()) {
      if (data.socketId === socket.id) {
        activeWorkers.delete(id);
        socket.broadcast.emit('worker_offline', { workerId: id });
        break;
      }
    }
  });

  // Location updates
  socket.on('location:update', (data) => {
    if (socket.userType === 'worker' && socket.userId) {
      const worker = activeWorkers.get(socket.userId);
      if (worker) {
        worker.location = data.location;
        worker.lastUpdate = Date.now();
        socket.broadcast.emit('location_updated', {
          workerId: socket.userId,
          location: data.location,
          timestamp: Date.now(),
        });
      }
    }
  });

  // Error handler
  socket.on('error', (error) => {
    console.error(`Socket error for ${socket.id}:`, error);
  });
});

// ========== ERROR HANDLING ==========

app.use(errorHandler);

// 404 handler
app.use((req, res) => {
  res.status(404).json(APIResponse.error('Route not found', 404, 'NOT_FOUND'));
});

// ========== SERVER START ==========

server.listen(config.PORT, config.HOST, () => {
  console.log(`
╔════════════════════════════════════════════════╗
║        🚀 FLIX MARKETPLACE API SERVER          ║
╠════════════════════════════════════════════════╣
║  Status:    ✅ Running                         ║
║  Port:      ${config.PORT}                                 ║
║  Host:      ${config.HOST}                              ║
║  Env:       ${config.APP_ENV}                         ║
║  Version:   ${config.APP_VERSION}                            ║
║                                                ║
║  🌐 Web:    ${config.APP_URL}              ║
║  📡 Socket: ws://localhost:${config.SOCKET_PORT}                   ║
║  🏥 Health: ${config.APP_URL}/health                  ║
╚════════════════════════════════════════════════╝
  `);

  // Log database status
  pool.query('SELECT NOW()', (err, result) => {
    if (err) {
      console.error('❌ Database connection failed:', err.message);
    } else {
      console.log('✅ Database connected');
    }
  });

  // Log feature flags
  console.log('\n📋 Enabled Features:');
  Object.entries(config.FEATURES).forEach(([key, value]) => {
    console.log(`  ${value ? '✅' : '⛔'} ${key}`);
  });
});

// ========== EXPORTS ==========

module.exports = { app, server, pool, io };
