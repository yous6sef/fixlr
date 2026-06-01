# Real-Time Marketplace Integration Guide

## Overview
This document explains the real-time marketplace system for Flix, including the WebSocket server, frontend client, and REST API endpoints.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Frontend (usermain.php / workermain.php)    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ RealTimeClient (socket-client.js)                        │  │
│  │ - Connects to WebSocket server (localhost:3000)         │  │
│  │ - Listens for real-time events                          │  │
│  │ - Updates DOM with live notifications                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└───────────────────┬──────────────────────────────────────────────┘
                    │
                    │ Socket.io connection
                    │ (ws://localhost:3000)
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│           WebSocket Server (node/server.js)                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Socket.io Server                                         │  │
│  │ - Accepts connections from users and workers            │  │
│  │ - Manages rooms for order subscriptions                 │  │
│  │ - Broadcasts real-time events                           │  │
│  └──────────────────────────────────────────────────────────┘  │
└───────────────────┬──────────────────────────────────────────────┘
                    │
                    │ REST API calls
                    │ (HTTP POST/GET)
                    ▼
┌─────────────────────────────────────────────────────────────────┐
│              REST API (api.php)                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Endpoints:                                               │  │
│  │ - GET /api.php?action=get_orders                        │  │
│  │ - POST /api.php?action=accept_order                     │  │
│  │ - POST /api.php?action=reject_order                     │  │
│  │ - POST /api.php?action=counter_offer                    │  │
│  │ - POST /api.php?action=submit_offer                     │  │
│  │ - GET /api.php?action=order_status&id=ORDER_ID         │  │
│  │ - GET /api.php?action=user_stats                        │  │
│  │ - GET /api.php?action=worker_stats                      │  │
│  └──────────────────────────────────────────────────────────┘  │
└───────────────────┬──────────────────────────────────────────────┘
                    │
                    │ Database queries
                    ▼
             ┌──────────────┐
             │  Database    │
             │ (PostgreSQL) │
             └──────────────┘
```

## Components

### 1. Frontend Client (`js/socket-client.js`)

**Class: RealTimeClient**

#### Methods

```javascript
// Initialize connection
connect(userId, role)
// Parameters:
//   userId: Current user ID (string or number)
//   role: 'user' or 'worker'

// Listen to events
on(eventName, handler)
// Example:
//   realTimeClient.on('worker.offered', (data) => {
//     console.log('New offer:', data);
//   });

// Subscribe to specific request
subscribeToRequest(requestId)

// Unsubscribe from request
unsubscribeFromRequest(requestId)

// Disconnect
disconnect()
```

#### Events Received

**For Users:**
- `worker.offered` - Worker submitted price offer
- `user.accepted` - User accepted offer (internal)
- `user.countered` - User submitted counter offer (internal)
- `worker.rejected` - Worker rejected request
- `worker.completed` - Worker marked order as completed
- `user.rated` - User left review for worker
- `status.updated` - Generic status update

**For Workers:**
- `worker.offered` - Worker submitted offer (internal)
- `user.accepted` - User accepted worker's offer
- `user.countered` - User sent counter offer
- `worker.rejected` - Worker rejected (internal)
- `worker.completed` - Order completed
- `user.rated` - User rated the worker

### 2. REST API (`api.php`)

All endpoints require active session (user must be logged in).

#### Get Orders

```bash
GET /api.php?action=get_orders
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "description": "Service description",
      "address": "123 Main St",
      "budget": 100.00,
      "worker_price": 80.00,
      "status": "pending",
      "negotiation_state": "offered",
      "worker_name": "John Doe",
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "count": 1
}
```

#### Accept Order

```bash
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=accept_order&order_id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Order accepted successfully",
  "orderId": 1,
  "status": "accepted"
}
```

#### Reject Order

```bash
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=reject_order&order_id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Order rejected successfully",
  "orderId": 1
}
```

#### Submit Counter Offer (User)

```bash
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=counter_offer&order_id=1&budget=85.00
```

**Response:**
```json
{
  "success": true,
  "message": "Counter offer submitted",
  "orderId": 1,
  "counterPrice": 85.00
}
```

#### Submit Price Offer (Worker)

```bash
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=submit_offer&order_id=1&price=90.00
```

**Response:**
```json
{
  "success": true,
  "message": "Offer submitted successfully",
  "orderId": 1,
  "price": 90.00
}
```

#### Complete Order (Worker)

```bash
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=complete_order&order_id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Order marked as completed",
  "orderId": 1,
  "status": "completed"
}
```

#### Get Order Status

```bash
GET /api.php?action=order_status&id=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "pending",
    "negotiation_state": "offered",
    "worker_price": 80.00,
    "budget": 100.00,
    "worker_id": "worker123"
  }
}
```

#### Get User Stats

```bash
GET /api.php?action=user_stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "pending": 5,
    "accepted": 2,
    "completed": 15,
    "totalSpent": 1250.50
  }
}
```

#### Get Worker Stats

```bash
GET /api.php?action=worker_stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "incoming": 10,
    "active": 3,
    "completedToday": 2,
    "todayRevenue": 250.00,
    "avgRating": 4.8
  }
}
```

## CSS Variables (`css/variables.css`)

The design system defines custom CSS properties for consistent styling:

```css
:root {
  /* Primary brand color */
  --color-primary: #3b82f6;
  --color-primary-dark: #2563eb;
  --color-primary-light: #dbeafe;
  
  /* Status-specific colors */
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-offer: #a855f7;
  
  /* Spacing scale */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  
  /* Transitions */
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
```

**Use in HTML:**
```html
<div style="background-color: var(--color-primary);">
  Primary color box
</div>

<div style="animation: fadeIn var(--transition-base);">
  Animated element
</div>
```

## Notification System (`js/notifications.js`)

Display toast notifications for user feedback:

```javascript
// Show notification
showNotification(title, message, type);

// Parameters:
//   title: Notification title
//   message: Notification body text
//   type: 'info' | 'success' | 'warning' | 'error' | 'offer'

// Examples:
showNotification('Success', 'Order accepted!', 'success');
showNotification('New Offer', 'Worker submitted price: $80', 'info');
showNotification('Error', 'Failed to submit offer', 'error');
```

## CSS Animations (`css/animations.css`)

Pre-built animations for smooth UX:

```css
/* Fade in/out */
@keyframes fadeIn { /* ... */ }
@keyframes fadeOut { /* ... */ }

/* Slide animations */
@keyframes slideInRight { /* ... */ }
@keyframes slideInLeft { /* ... */ }

/* Scale animations */
@keyframes scaleIn { /* ... */ }

/* Pulse animation */
@keyframes pulse { /* ... */ }

/* Bounce animation */
@keyframes bounce { /* ... */ }
```

**Usage:**
```html
<div style="animation: fadeIn 0.3s ease-in;">
  Fades in smoothly
</div>

<div class="animate-pulse">
  Pulses continuously
</div>
```

## Loaders (`css/loaders.css`)

Loading indicators and status badges:

```html
<!-- Spinner loader -->
<div class="loader-spinner"></div>

<!-- Pulse loader -->
<div class="loader-pulse"></div>

<!-- Status dot -->
<span class="status-dot bg-green-500"></span>

<!-- Status badge -->
<span class="status-badge bg-status-accepted">قيد المعالجة</span>
```

## Integration Steps

### 1. Setup WebSocket Server
```bash
cd node
npm install
node server.js
# Server runs on localhost:3000
```

### 2. Include Scripts in PHP Files
Already done! Both `usermain.php` and `workermain.php` include:
- CSS stylesheets
- Socket.io library
- Custom JavaScript files
- Initialization code

### 3. Initialize RealTimeClient
```javascript
const realTimeClient = new RealTimeClient();
realTimeClient.connect(userId, 'user'); // or 'worker'

// Listen for events
realTimeClient.on('worker.offered', (data) => {
  console.log('New offer received');
  refreshOrders(); // Refresh UI
});
```

### 4. Call API Endpoints
```javascript
// Fetch orders
fetch('api.php?action=get_orders')
  .then(r => r.json())
  .then(data => console.log(data));

// Accept order
fetch('api.php', {
  method: 'POST',
  body: new URLSearchParams({
    action: 'accept_order',
    order_id: 1
  })
})
  .then(r => r.json())
  .then(data => console.log(data));
```

## Event Flow Example

### User Receives Offer from Worker

```
1. Worker submits offer (workermain.php)
   ↓
2. API call: POST /api.php?action=submit_offer
   ↓
3. Database updates with worker_id and worker_price
   ↓
4. WebSocket server broadcasts event (worker.offered)
   ↓
5. User's RealTimeClient receives event
   ↓
6. Notification toast displayed
   ↓
7. RealTimeClient triggers handler
   ↓
8. UI refreshes with new order card showing offer
```

### User Accepts Worker Offer

```
1. User clicks "Accept" button
   ↓
2. API call: POST /api.php?action=accept_order
   ↓
3. Database updates status to 'accepted'
   ↓
4. WebSocket server broadcasts event (user.accepted)
   ↓
5. Worker's RealTimeClient receives event
   ↓
6. Notification toast: "User accepted your offer!"
   ↓
7. Worker dashboard refreshes
```

## Testing

### Manual Testing Checklist

- [ ] WebSocket server running on localhost:3000
- [ ] Users receive new offer notifications
- [ ] Workers receive order status notifications
- [ ] API endpoints return correct JSON responses
- [ ] Order cards update in real-time
- [ ] Notifications display properly
- [ ] Mobile responsiveness works
- [ ] Animations are smooth

### API Testing (cURL)

```bash
# Get orders
curl "http://localhost/api.php?action=get_orders" \
  -b "PHPSESSID=your_session_id"

# Accept order
curl -X POST "http://localhost/api.php" \
  -d "action=accept_order&order_id=1" \
  -b "PHPSESSID=your_session_id"

# Get user stats
curl "http://localhost/api.php?action=user_stats" \
  -b "PHPSESSID=your_session_id"
```

## Troubleshooting

### WebSocket Connection Fails
- Check if Node server is running: `node node/server.js`
- Verify server URL: `http://localhost:3000`
- Check browser console for errors
- Ensure CORS is properly configured

### API Endpoints Return 401
- Verify user is logged in
- Check session cookie is being sent
- Ensure session hasn't expired

### Notifications Don't Show
- Check `js/notifications.js` is loaded
- Verify `showNotification()` function exists
- Check browser console for errors

### Real-time Updates Not Working
- Verify RealTimeClient is initialized
- Check WebSocket connection status in console
- Verify event listeners are registered
- Check for JavaScript errors in console

## Security Considerations

1. **Session Authentication**: All API endpoints require valid session
2. **CORS**: Configure appropriately for production
3. **Rate Limiting**: Implement rate limiting on API endpoints
4. **Input Validation**: Always validate user inputs (already done in api.php)
5. **SQL Injection**: Use prepared statements (already done)
6. **XSS Prevention**: Sanitize output with htmlspecialchars()

## Performance Optimization

1. **Connection Pooling**: WebSocket maintains single connection
2. **Event Debouncing**: Refresh endpoints debounced to 8s intervals
3. **Lazy Loading**: Orders loaded on demand via API
4. **Caching**: Consider implementing browser cache for stats
5. **Compression**: Enable gzip compression on API responses

## Future Enhancements

- [ ] Implement message queuing system
- [ ] Add analytics/metrics tracking
- [ ] Implement typing indicators
- [ ] Add file upload for photos/documents
- [ ] Implement two-factor authentication
- [ ] Add end-to-end encryption for messages
- [ ] Implement push notifications
- [ ] Add offline mode with service workers
