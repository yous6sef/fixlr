/**
 * Enhanced Real-Time WebSocket Server for Flix Marketplace
 * Uber-inspired features: GPS tracking, instant matching, live chat
 * Integrated with HERE Maps API for location services
 */

const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const cors = require('cors');
const axios = require('axios');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
  cors: {
    origin: ['http://localhost:8000', 'http://localhost:3000', 'http://localhost:3001'],
    methods: ['GET', 'POST'],
    credentials: true
  },
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionDelayMax: 5000,
  reconnectionAttempts: Infinity
});

app.use(cors());
app.use(express.json());

// HERE Maps configuration
const HERE_API_KEY = 'zbTcdxdMTu88G-q5LfQMBbALRFN7M0BMd4sEWPOLgmU';
const HERE_BASE_URL = 'https://geocode.search.hereapi.com/v1';

// Store active connections with enhanced data
const activeUsers = new Map();    // { userId: { socket, location, status } }
const activeWorkers = new Map();  // { workerId: { socket, location, status, serviceType } }
const activeRequests = new Map(); // { requestId: { customerId, workerId, status, location } }

// === ENHANCED SOCKET.IO CONNECTION HANDLERS ===

io.on('connection', (socket) => {
  console.log(`[Socket.io] 🚀 New connection: ${socket.id}`);

  // Enhanced user authentication
  socket.on('auth:login', (data) => {
    const { userId, role, token } = data;
    console.log(`[Auth] User ${userId} (${role}) authenticated via socket ${socket.id}`);

    const userData = {
      socket,
      userId,
      role,
      connectedAt: new Date(),
      location: null,
      status: 'online'
    };

    if (role === 'user') {
      activeUsers.set(userId, userData);
      socket.join(`user-${userId}`);
      socket.join('users-broadcast');

      // Notify nearby workers of new customer
      broadcastToNearbyWorkers(userId, 'customer_online', { customerId: userId });

    } else if (role === 'worker') {
      userData.serviceType = data.serviceType;
      activeWorkers.set(userId, userData);
      socket.join(`worker-${userId}`);
      socket.join('workers-broadcast');

      // Notify all users that a worker is online
      socket.to('users-broadcast').emit('worker_online', {
        workerId: userId,
        serviceType: data.serviceType
      });
    }

    socket.emit('auth:success', { message: 'Authenticated successfully' });
  });

  // GPS Location tracking (Uber-style)
  socket.on('location:update', (data) => {
    const { lat, lng, accuracy } = data;
    const userId = getUserIdFromSocket(socket);

    if (!userId) return;

    const location = { lat, lng, accuracy, timestamp: new Date() };

    if (activeUsers.has(userId)) {
      activeUsers.get(userId).location = location;
    } else if (activeWorkers.has(userId)) {
      activeWorkers.get(userId).location = location;
    }

    // Broadcast location to relevant parties
    socket.to(`user-${userId}`).emit('location_updated', location);
  });

  // Service Request Creation (Uber-style instant matching)
  socket.on('request:create', async (data) => {
    const { serviceType, location, description, urgency = 'normal' } = data;
    const customerId = getUserIdFromSocket(socket);

    if (!customerId || !activeUsers.has(customerId)) {
      socket.emit('error', { message: 'Unauthorized' });
      return;
    }

    try {
      // Generate request ID
      const requestId = `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

      const requestData = {
        id: requestId,
        customerId,
        serviceType,
        location,
        description,
        urgency,
        status: 'searching',
        createdAt: new Date(),
        workersNotified: []
      };

      activeRequests.set(requestId, requestData);

      // Find nearby workers using enhanced matching
      const nearbyWorkers = await findNearbyWorkers(location, serviceType, 5000); // 5km radius

      // Notify workers instantly
      const notifiedWorkers = [];
      for (const worker of nearbyWorkers) {
        const workerSocket = activeWorkers.get(worker.workerId);
        if (workerSocket && workerSocket.socket) {
          workerSocket.socket.emit('request:new', {
            requestId,
            customerId,
            serviceType,
            location,
            description,
            urgency,
            distance: worker.distance,
            estimatedArrival: calculateETA(worker.distance)
          });
          notifiedWorkers.push(worker.workerId);
        }
      }

      requestData.workersNotified = notifiedWorkers;

      // Start timeout for auto-cancellation
      setTimeout(() => {
        if (activeRequests.get(requestId)?.status === 'searching') {
          activeRequests.get(requestId).status = 'expired';
          socket.emit('request:expired', { requestId });
          socket.to(`user-${customerId}`).emit('request:expired', { requestId });
        }
      }, 5 * 60 * 1000); // 5 minutes

      socket.emit('request:created', {
        requestId,
        workersNotified: notifiedWorkers.length,
        estimatedWaitTime: calculateWaitTime(nearbyWorkers.length)
      });

    } catch (error) {
      console.error('[Request Create Error]', error);
      socket.emit('error', { message: 'Failed to create service request' });
    }
  });

  // Worker Response to Request
  socket.on('request:respond', (data) => {
    const { requestId, response, estimatedTime, price } = data;
    const workerId = getUserIdFromSocket(socket);

    if (!workerId || !activeWorkers.has(workerId)) {
      socket.emit('error', { message: 'Unauthorized worker' });
      return;
    }

    const request = activeRequests.get(requestId);
    if (!request) {
      socket.emit('error', { message: 'Request not found' });
      return;
    }

    if (response === 'accept') {
      // Update request status
      request.status = 'accepted';
      request.workerId = workerId;
      request.acceptedAt = new Date();
      request.estimatedTime = estimatedTime;
      request.price = price;

      // Notify customer
      const customerSocket = activeUsers.get(request.customerId);
      if (customerSocket && customerSocket.socket) {
        customerSocket.socket.emit('request:accepted', {
          requestId,
          workerId,
          estimatedTime,
          price,
          workerLocation: activeWorkers.get(workerId).location
        });
      }

      // Notify other workers that request is taken
      request.workersNotified.forEach(wId => {
        if (wId !== workerId) {
          const workerSocket = activeWorkers.get(wId);
          if (workerSocket && workerSocket.socket) {
            workerSocket.socket.emit('request:taken', { requestId });
          }
        }
      });

    } else if (response === 'decline') {
      // Remove worker from notified list
      request.workersNotified = request.workersNotified.filter(id => id !== workerId);
    }

    socket.emit('request:response_sent', { requestId, response });
  });

  // Real-time Chat (WhatsApp-style)
  socket.on('chat:send', (data) => {
    const { to, message, requestId } = data;
    const fromId = getUserIdFromSocket(socket);

    if (!fromId) return;

    const messageData = {
      id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      from: fromId,
      to,
      message,
      requestId,
      timestamp: new Date(),
      read: false
    };

    // Send to recipient
    const recipientSocket = getSocketByUserId(to);
    if (recipientSocket) {
      recipientSocket.emit('chat:new_message', messageData);
    }

    // Send delivery confirmation to sender
    socket.emit('chat:message_sent', {
      messageId: messageData.id,
      delivered: !!recipientSocket
    });
  });

  // Message read receipts
  socket.on('chat:mark_read', (data) => {
    const { messageId, requestId } = data;
    const userId = getUserIdFromSocket(socket);

    // Notify sender that message was read
    socket.to(`user-${userId}`).emit('chat:message_read', { messageId, requestId });
  });

  // Worker status updates (online/offline/busy)
  socket.on('worker:status_update', (data) => {
    const { status, serviceType } = data;
    const workerId = getUserIdFromSocket(socket);

    if (!workerId || !activeWorkers.has(workerId)) return;

    const worker = activeWorkers.get(workerId);
    worker.status = status;
    worker.serviceType = serviceType;

    // Broadcast status change
    socket.to('users-broadcast').emit('worker:status_changed', {
      workerId,
      status,
      serviceType
    });
  });

  // Live tracking during service
  socket.on('service:start_tracking', (data) => {
    const { requestId } = data;
    const workerId = getUserIdFromSocket(socket);

    if (!workerId) return;

    const request = activeRequests.get(requestId);
    if (request && request.workerId === workerId) {
      request.status = 'in_progress';
      request.startedAt = new Date();

      // Start live location sharing
      const customerSocket = activeUsers.get(request.customerId);
      if (customerSocket && customerSocket.socket) {
        customerSocket.socket.emit('service:started', {
          requestId,
          workerId,
          startTime: request.startedAt
        });
      }
    }
  });

  // Service completion
  socket.on('service:complete', async (data) => {
    const { requestId, finalPrice, rating, review } = data;
    const workerId = getUserIdFromSocket(socket);

    if (!workerId) return;

    const request = activeRequests.get(requestId);
    if (request && request.workerId === workerId) {
      request.status = 'completed';
      request.completedAt = new Date();
      request.finalPrice = finalPrice;
      request.rating = rating;
      request.review = review;

      // Notify customer
      const customerSocket = activeUsers.get(request.customerId);
      if (customerSocket && customerSocket.socket) {
        customerSocket.socket.emit('service:completed', {
          requestId,
          finalPrice,
          rating,
          review,
          completionTime: request.completedAt
        });
      }

      // Clean up
      setTimeout(() => {
        activeRequests.delete(requestId);
      }, 300000); // Keep for 5 minutes after completion
    }
  });

  // Emergency/SOS feature
  socket.on('emergency:alert', (data) => {
    const { location, type, description } = data;
    const userId = getUserIdFromSocket(socket);

    if (!userId) return;

    // Broadcast emergency to all nearby workers
    const emergencyData = {
      userId,
      location,
      type,
      description,
      timestamp: new Date()
    };

    // Find all workers within 2km
    broadcastToNearbyWorkers(userId, 'emergency:alert', emergencyData, 2000);

    socket.emit('emergency:alert_sent', { success: true });
  });

  // Disconnect handling
  socket.on('disconnect', () => {
    console.log(`[Socket.io] 👋 Disconnection: ${socket.id}`);

    // Find and remove user from active connections
    for (const [userId, userData] of activeUsers) {
      if (userData.socket.id === socket.id) {
        activeUsers.delete(userId);
        socket.to('workers-broadcast').emit('customer_offline', { customerId: userId });
        break;
      }
    }

    for (const [workerId, workerData] of activeWorkers) {
      if (workerData.socket.id === socket.id) {
        activeWorkers.delete(workerId);
        socket.to('users-broadcast').emit('worker_offline', { workerId: workerId });
        break;
      }
    }
  });
});

// === API ENDPOINTS ===

// HERE Maps integration for location services
app.get('/api/location/autocomplete', async (req, res) => {
  try {
    const { q, limit = 5 } = req.query;

    const response = await axios.get(`${HERE_BASE_URL}/autosuggest`, {
      params: {
        q,
        apiKey: HERE_API_KEY,
        in: 'countryCode:EGY',
        limit
      }
    });

    res.json(response.data);
  } catch (error) {
    console.error('[HERE API Error]', error);
    res.status(500).json({ error: 'Location service unavailable' });
  }
});

app.get('/api/location/reverse-geocode', async (req, res) => {
  try {
    const { lat, lng } = req.query;

    const response = await axios.get(`${HERE_BASE_URL}/revgeocode`, {
      params: {
        at: `${lat},${lng}`,
        apiKey: HERE_API_KEY,
        limit: 1
      }
    });

    res.json(response.data);
  } catch (error) {
    console.error('[HERE Reverse Geocode Error]', error);
    res.status(500).json({ error: 'Reverse geocoding unavailable' });
  }
});

// Get active workers for a service type
app.get('/api/workers/active', (req, res) => {
  const { serviceType, lat, lng, radius = 5000 } = req.query;

  const activeWorkersList = [];

  for (const [workerId, workerData] of activeWorkers) {
    if (workerData.status === 'online' &&
        (!serviceType || workerData.serviceType === serviceType)) {

      // Calculate distance if location provided
      let distance = null;
      if (lat && lng && workerData.location) {
        distance = calculateDistance(
          parseFloat(lat), parseFloat(lng),
          workerData.location.lat, workerData.location.lng
        );
      }

      if (!distance || distance <= parseFloat(radius)) {
        activeWorkersList.push({
          workerId,
          serviceType: workerData.serviceType,
          location: workerData.location,
          distance,
          connectedAt: workerData.connectedAt
        });
      }
    }
  }

  res.json({ workers: activeWorkersList, count: activeWorkersList.length });
});

// === HELPER FUNCTIONS ===

function getUserIdFromSocket(socket) {
  for (const [userId, userData] of activeUsers) {
    if (userData.socket.id === socket.id) return userId;
  }
  for (const [workerId, workerData] of activeWorkers) {
    if (workerData.socket.id === socket.id) return workerId;
  }
  return null;
}

function getSocketByUserId(userId) {
  const user = activeUsers.get(userId);
  const worker = activeWorkers.get(userId);
  return user ? user.socket : worker ? worker.socket : null;
}

async function findNearbyWorkers(location, serviceType, radius = 5000) {
  const nearbyWorkers = [];

  for (const [workerId, workerData] of activeWorkers) {
    if (workerData.status === 'online' &&
        workerData.location &&
        (!serviceType || workerData.serviceType === serviceType)) {

      const distance = calculateDistance(
        location.lat, location.lng,
        workerData.location.lat, workerData.location.lng
      );

      if (distance <= radius) {
        nearbyWorkers.push({
          workerId,
          distance,
          location: workerData.location,
          serviceType: workerData.serviceType
        });
      }
    }
  }

  // Sort by distance
  return nearbyWorkers.sort((a, b) => a.distance - b.distance);
}

function broadcastToNearbyWorkers(fromUserId, event, data, radius = 5000) {
  const userLocation = activeUsers.get(fromUserId)?.location;
  if (!userLocation) return;

  for (const [workerId, workerData] of activeWorkers) {
    if (workerData.location && workerData.status === 'online') {
      const distance = calculateDistance(
        userLocation.lat, userLocation.lng,
        workerData.location.lat, workerData.location.lng
      );

      if (distance <= radius) {
        workerData.socket.emit(event, { ...data, distance });
      }
    }
  }
}

function calculateDistance(lat1, lng1, lat2, lng2) {
  const R = 6371; // Earth's radius in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLng/2) * Math.sin(dLng/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c * 1000; // Return in meters
}

function calculateETA(distanceInMeters) {
  // Assume average speed of 30 km/h for workers
  const speedKmh = 30;
  const distanceKm = distanceInMeters / 1000;
  const timeHours = distanceKm / speedKmh;
  const timeMinutes = Math.ceil(timeHours * 60);

  return Math.max(5, timeMinutes); // Minimum 5 minutes
}

function calculateWaitTime(nearbyWorkersCount) {
  if (nearbyWorkersCount === 0) return 'غير متاح حالياً';
  if (nearbyWorkersCount >= 5) return 'أقل من دقيقة';
  if (nearbyWorkersCount >= 3) return '1-2 دقائق';
  return '3-5 دقائق';
}

app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    server: 'Flix Real-Time WebSocket Server',
    port: process.env.SOCKET_PORT || 3000,
    timestamp: new Date().toISOString()
  });
});

// === SERVER STARTUP ===

const PORT = process.env.SOCKET_PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 Flix Real-Time Server running on port ${PORT}`);
  console.log(`📍 HERE Maps integrated with key: ${HERE_API_KEY.substring(0, 10)}...`);
  console.log(`👥 Active connections tracking enabled`);
  console.log(`🚨 Emergency alert system ready`);
  console.log(`
╔════════════════════════════════════════════════════════════╗
║  Flix Real-Time WebSocket Server                           ║
║  ✓ Socket.io running on http://localhost:${PORT}           ║
║  ✓ CORS enabled for http://localhost:8000                  ║
║  ✓ Ready for real-time events                              ║
╚════════════════════════════════════════════════════════════╝
  `);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('\n[Shutdown] Received SIGTERM, closing server...');
  server.close(() => {
    console.log('[Shutdown] Server closed');
    process.exit(0);
  });
});
