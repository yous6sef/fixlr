/**
 * Flix Real-Time WebSocket Server
 *
 * This server handles:
 * - WebSocket connections from users and workers
 * - Room management for order subscriptions
 * - Event broadcasting for order updates
 * - Real-time notifications
 */

const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const cors = require('cors');

// ============ CONFIGURATION ============

const PORT = process.env.PORT || 3000;
const FRONTEND_URL = process.env.FRONTEND_URL || 'http://localhost';

// ============ EXPRESS SETUP ============

const app = express();
app.use(cors());
app.use(express.json());

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        server: 'Flix WebSocket Server',
        timestamp: new Date().toISOString(),
        connectedSockets: Object.keys(io.sockets.sockets).length
    });
});

// ============ HTTP SERVER ============

const server = http.createServer(app);

// ============ SOCKET.IO SETUP ============

const io = socketIO(server, {
    cors: {
        origin: FRONTEND_URL,
        methods: ['GET', 'POST']
    },
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
    reconnectionAttempts: 10
});

// ============ DATA STRUCTURES ============

// Track connected users: { socketId: { userId, role, connectedAt } }
const connectedUsers = new Map();

// Track active rooms: { requestId: Set(socketIds) }
const activeRooms = new Map();

// ============ SOCKET.IO EVENTS ============

io.on('connection', (socket) => {
    const { userId, role } = socket.handshake.query;

    console.log(`✅ New connection: ${socket.id}`);
    console.log(`   User: ${userId}, Role: ${role}`);

    // Store user information
    if (userId && role) {
        connectedUsers.set(socket.id, {
            userId: userId,
            role: role,
            connectedAt: new Date(),
            lastHeartbeat: new Date()
        });

        // Join user-specific room
        socket.join(`user:${userId}`);
        socket.join(`role:${role}`);

        console.log(`   Joined rooms: user:${userId}, role:${role}`);
    }

    // ========== USER JOIN EVENT ==========
    socket.on('user:join', (data) => {
        console.log(`📍 User join event:`, data);

        // Broadcast user online status
        io.to(`role:${data.role}`).emit('user:online', {
            userId: data.userId,
            role: data.role,
            timestamp: new Date()
        });
    });

    // ========== REQUEST SUBSCRIPTION ==========
    socket.on('request:subscribe', (data) => {
        const { requestId, userId, role } = data;
        console.log(`👁️  Subscribe to request ${requestId}:`, { userId, role });

        // Join request-specific room
        socket.join(`request:${requestId}`);

        // Track active rooms
        if (!activeRooms.has(requestId)) {
            activeRooms.set(requestId, new Set());
        }
        activeRooms.get(requestId).add(socket.id);

        // Notify others in room that someone joined
        socket.to(`request:${requestId}`).emit('user:joined_request', {
            requestId: requestId,
            participantCount: activeRooms.get(requestId).size
        });

        console.log(`   Total participants: ${activeRooms.get(requestId).size}`);
    });

    socket.on('request:unsubscribe', (data) => {
        const { requestId } = data;
        console.log(`👁️ Unsubscribe from request ${requestId}`);

        socket.leave(`request:${requestId}`);

        if (activeRooms.has(requestId)) {
            activeRooms.get(requestId).delete(socket.id);
            if (activeRooms.get(requestId).size === 0) {
                activeRooms.delete(requestId);
            }
        }
    });

    // ========== MARKETPLACE EVENTS ==========

    // Worker submitted offer to user
    socket.on('worker:offer', (data) => {
        const { requestId, workerId, price, workerName } = data;
        console.log(`💰 Worker offer:`, { requestId, workerId, price });

        // Send to user
        io.to(`request:${requestId}`).emit('worker.offered', {
            requestId: requestId,
            workerId: workerId,
            price: price,
            workerName: workerName,
            timestamp: new Date()
        });

        // Log event
        logEvent('WORKER_OFFER', { requestId, workerId, price });
    });

    // User accepted offer
    socket.on('user:accept', (data) => {
        const { requestId, workerId } = data;
        console.log(`✅ User accepted offer:`, { requestId, workerId });

        io.to(`request:${requestId}`).emit('user.accepted', {
            requestId: requestId,
            workerId: workerId,
            timestamp: new Date()
        });

        logEvent('USER_ACCEPTED', { requestId, workerId });
    });

    // User sent counter offer
    socket.on('user:counter', (data) => {
        const { requestId, newBudget } = data;
        console.log(`🔁 User counter offer:`, { requestId, newBudget });

        io.to(`request:${requestId}`).emit('user.countered', {
            requestId: requestId,
            budget: newBudget,
            timestamp: new Date()
        });

        logEvent('USER_COUNTER', { requestId, newBudget });
    });

    // Worker rejected
    socket.on('worker:reject', (data) => {
        const { requestId } = data;
        console.log(`❌ Worker rejected:`, { requestId });

        io.to(`request:${requestId}`).emit('worker.rejected', {
            requestId: requestId,
            timestamp: new Date()
        });

        logEvent('WORKER_REJECTED', { requestId });
    });

    // Worker completed job
    socket.on('worker:complete', (data) => {
        const { requestId, workerId } = data;
        console.log(`✅ Worker completed job:`, { requestId, workerId });

        io.to(`request:${requestId}`).emit('worker.completed', {
            requestId: requestId,
            workerId: workerId,
            timestamp: new Date()
        });

        logEvent('WORKER_COMPLETED', { requestId, workerId });

        // Close the room
        activeRooms.delete(requestId);
    });

    // User rated worker
    socket.on('user:rate', (data) => {
        const { requestId, workerId, rating, comment } = data;
        console.log(`⭐ User rated:`, { requestId, workerId, rating });

        io.to(`user:${workerId}`).emit('user.rated', {
            requestId: requestId,
            rating: rating,
            comment: comment,
            timestamp: new Date()
        });

        logEvent('USER_RATED', { requestId, workerId, rating });
    });

    // ========== MESSAGING ==========

    socket.on('message:send', (data) => {
        const { requestId, senderId, message } = data;
        console.log(`💬 Message:`, { requestId, senderId });

        io.to(`request:${requestId}`).emit('message:received', {
            requestId: requestId,
            senderId: senderId,
            message: message,
            timestamp: new Date()
        });
    });

    // ========== HEARTBEAT / KEEP-ALIVE ==========

    socket.on('ping', () => {
        const user = connectedUsers.get(socket.id);
        if (user) {
            user.lastHeartbeat = new Date();
        }
        socket.emit('pong', { timestamp: new Date() });
    });

    // ========== DISCONNECT ==========

    socket.on('disconnect', () => {
        console.log(`❌ Socket disconnected: ${socket.id}`);

        const user = connectedUsers.get(socket.id);
        if (user) {
            console.log(`   User: ${user.userId} (${user.role})`);

            // Notify others user is offline
            io.to(`role:${user.role}`).emit('user:offline', {
                userId: user.userId,
                timestamp: new Date()
            });

            connectedUsers.delete(socket.id);
        }

        // Clean up active rooms
        activeRooms.forEach((participants, requestId) => {
            if (participants.has(socket.id)) {
                participants.delete(socket.id);
                if (participants.size === 0) {
                    activeRooms.delete(requestId);
                }
            }
        });
    });

    // ========== ERROR HANDLING ==========

    socket.on('error', (error) => {
        console.error(`❌ Socket error [${socket.id}]:`, error);
    });
});

// ============ UTILITIES ============

function logEvent(eventType, data) {
    const timestamp = new Date().toISOString();
    console.log(`[EVENT] ${timestamp} - ${eventType}:`, data);
    // TODO: Send to database/analytics service
}

function getConnectedStats() {
    let userCount = 0;
    let workerCount = 0;

    connectedUsers.forEach(user => {
        if (user.role === 'user') userCount++;
        else if (user.role === 'worker') workerCount++;
    });

    return {
        totalConnected: connectedUsers.size,
        users: userCount,
        workers: workerCount,
        activeRooms: activeRooms.size,
        timestamp: new Date()
    };
}

// ============ PERIODIC CLEANUP ==========

// Check for idle connections every 5 minutes
setInterval(() => {
    const now = new Date();
    const timeout = 5 * 60 * 1000; // 5 minutes

    connectedUsers.forEach((user, socketId) => {
        if (now - user.lastHeartbeat > timeout) {
            console.warn(`⏱️  Timeout: ${socketId} (${user.userId})`);
            io.sockets.sockets.get(socketId)?.disconnect(true);
        }
    });

    // Log stats
    console.log(`📊 Stats:`, getConnectedStats());
}, 5 * 60 * 1000);

// ============ SERVER START ==========

server.listen(PORT, () => {
    console.log(`
╔════════════════════════════════════════╗
║    Flix WebSocket Server Running      ║
╠════════════════════════════════════════╣
║  URL: http://localhost:${PORT}
║  Socket.IO: ws://localhost:${PORT}/socket.io
║
║  Health Check: http://localhost:${PORT}/health
║
║  Connected: ${connectedUsers.size} users
║  Active Rooms: ${activeRooms.size}
╚════════════════════════════════════════╝
    `);
});

// Handle server errors
server.on('error', (error) => {
    console.error('❌ Server error:', error);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM signal received: closing HTTP server');
    server.close(() => {
        console.log('HTTP server closed');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('SIGINT signal received: closing HTTP server');
    server.close(() => {
        console.log('HTTP server closed');
        process.exit(0);
    });
});

// Export for testing
module.exports = server;