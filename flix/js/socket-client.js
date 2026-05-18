/**
 * js/socket-client.js
 * 
 * Socket.io client for real-time marketplace updates
 * Handles connection, room subscriptions, and event listeners
 */

class RealTimeClient {
  constructor() {
    this.socket = null;
    this.userId = null;
    this.role = null;
    this.isConnected = false;
    this.eventHandlers = new Map();
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 10;
    this.connectionTimeout = null;
  }

  /**
   * Initialize socket connection
   * @param {number|string} userId Current user ID
   * @param {string} role 'user' or 'worker'
   */
  connect(userId, role) {
    if (!userId || !role) {
      console.error('[Socket] Missing userId or role');
      return;
    }

    this.userId = userId;
    this.role = role;

    console.log(`[Socket] Connecting ${role} ${userId}...`);

    // Connect to socket.io server
    this.socket = io('http://localhost:3000', {
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      reconnectionAttempts: this.maxReconnectAttempts,
      query: {
        userId: userId,
        role: role
      }
    });

    // === CONNECTION EVENTS ===

    this.socket.on('connect', () => {
      console.log(`[Socket] Connected: ${this.socket.id}`);
      this.isConnected = true;
      this.reconnectAttempts = 0;

      // Tell server we're joining
      this.socket.emit('user:join', {
        userId: this.userId,
        role: this.role
      });

      // Show connection indicator
      this._showConnectionStatus(true);
    });

    this.socket.on('connection:confirmed', (data) => {
      console.log(`[Socket] Connection confirmed`, data);
      this._fireEvent('connection:confirmed', data);
    });

    this.socket.on('disconnect', () => {
      console.warn(`[Socket] Disconnected`);
      this.isConnected = false;
      this._showConnectionStatus(false);
    });

    this.socket.on('error', (error) => {
      console.error(`[Socket] Error:`, error);
    });

    // === MARKETPLACE EVENTS ===

    // Worker offered price to user
    this.socket.on('worker.offered', (data) => {
      console.log(`[Event] Worker offered:`, data);
      this._fireEvent('worker.offered', data);
      this._playSound('offer');
      this._showToast('New Offer!', `Worker offered $${data.price}`, 'offer');
    });

    // User accepted price
    this.socket.on('user.accepted', (data) => {
      console.log(`[Event] User accepted:`, data);
      this._fireEvent('user.accepted', data);
      this._playSound('accepted');
      this._showToast('Job Accepted!', 'User accepted your offer', 'success');
    });

    // User sent counter offer
    this.socket.on('user.countered', (data) => {
      console.log(`[Event] User countered:`, data);
      this._fireEvent('user.countered', data);
      this._playSound('offer');
      this._showToast('Counter Offer', `New budget: $${data.budget}`, 'warning');
    });

    // Worker rejected
    this.socket.on('worker.rejected', (data) => {
      console.log(`[Event] Worker rejected:`, data);
      this._fireEvent('worker.rejected', data);
      this._showToast('Worker Rejected', 'Request reassigned to another worker', 'warning');
    });

    // Job completed
    this.socket.on('worker.completed', (data) => {
      console.log(`[Event] Worker completed:`, data);
      this._fireEvent('worker.completed', data);
      this._playSound('accepted');
      this._showToast('Job Completed!', 'Please rate this worker', 'success');
    });

    // User rated worker
    this.socket.on('user.rated', (data) => {
      console.log(`[Event] User rated:`, data);
      this._fireEvent('user.rated', data);
      this._showToast('Rated', 'You received a rating', 'success');
    });

    // Status updated (generic)
    this.socket.on('status.updated', (data) => {
      console.log(`[Event] Status updated:`, data);
      this._fireEvent('status.updated', data);
    });
  }

  /**
   * Subscribe to a specific request (both user & worker join same room)
   * @param {number} requestId Request ID
   */
  subscribeToRequest(requestId) {
    if (!this.socket) {
      console.error('[Socket] Not connected');
      return;
    }

    this.socket.emit('request:subscribe', {
      requestId: requestId,
      userId: this.userId,
      role: this.role
    });

    console.log(`[Socket] Subscribed to request ${requestId}`);
  }

  /**
   * Unsubscribe from request
   * @param {number} requestId Request ID
   */
  unsubscribeFromRequest(requestId) {
    if (!this.socket) return;
    this.socket.off(`request-${requestId}`);
    console.log(`[Socket] Unsubscribed from request ${requestId}`);
  }

  /**
   * Register custom event handler
   * @param {string} eventName Event name
   * @param {Function} handler Callback function
   */
  on(eventName, handler) {
    if (!this.eventHandlers.has(eventName)) {
      this.eventHandlers.set(eventName, []);
    }
    this.eventHandlers.get(eventName).push(handler);
  }

  /**
   * Fire registered event handlers
   */
  _fireEvent(eventName, data) {
    if (this.eventHandlers.has(eventName)) {
      this.eventHandlers.get(eventName).forEach(handler => {
        try {
          handler(data);
        } catch (error) {
          console.error(`[Event Handler Error] ${eventName}:`, error);
        }
      });
    }
  }

  /**
   * Play sound notification (if enabled in settings)
   */
  _playSound(soundName) {
    try {
      const soundEnabled = localStorage.getItem('soundNotifications') !== 'false';
      if (!soundEnabled) return;

      const soundPath = `/sounds/${soundName}.mp3`;
      const audio = new Audio(soundPath);
      audio.volume = parseFloat(localStorage.getItem('soundVolume') || '0.5');
      audio.play().catch(err => console.log('[Sound] Could not play:', err));
    } catch (error) {
      console.log('[Sound] Error:', error);
    }
  }

  /**
   * Show toast notification
   */
  _showToast(title, message, type = 'info') {
    // This is called by notification system, not here
    // Just trigger event that notification system listens to
    this._fireEvent('toast:show', { title, message, type });
  }

  /**
   * Show connection status indicator
   */
  _showConnectionStatus(isConnected) {
    const statusEl = document.getElementById('socket-status');
    if (!statusEl) return;

    if (isConnected) {
      statusEl.classList.remove('offline');
      statusEl.classList.add('online');
      statusEl.title = 'Real-time connected';
    } else {
      statusEl.classList.remove('online');
      statusEl.classList.add('offline');
      statusEl.title = 'Real-time disconnected';
    }
  }

  /**
   * Disconnect socket
   */
  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.isConnected = false;
    }
  }
}

// === GLOBAL INSTANCE ===

window.realtimeClient = null;

/**
 * Initialize real-time client on page load
 * Call this in the HTML: <script>initRealTime(userId, role);</script>
 */
function initRealTime(userId, role) {
  if (window.realtimeClient) {
    window.realtimeClient.disconnect();
  }

  window.realtimeClient = new RealTimeClient();
  window.realtimeClient.connect(userId, role);

  return window.realtimeClient;
}

/**
 * Send custom event from client to server (for testing)
 */
function sendCustomEvent(eventName, data) {
  if (!window.realtimeClient || !window.realtimeClient.socket) {
    console.error('[Socket] Not connected');
    return;
  }
  window.realtimeClient.socket.emit(eventName, data);
}
