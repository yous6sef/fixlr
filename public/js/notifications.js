/**
 * js/notifications.js
 * 
 * Toast notification system for real-time events
 * Handles display, animations, and auto-dismiss
 */

class NotificationSystem {
  constructor() {
    this.container = null;
    this.notifications = [];
    this.initialize();
  }

  /**
   * Initialize notification container
   */
  initialize() {
    // Check if container already exists
    this.container = document.getElementById('toast-container');
    
    if (!this.container) {
      // Create container
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(this.container);
    }

    // Listen for socket events to show toasts
    if (window.realtimeClient) {
      window.realtimeClient.on('toast:show', (data) => {
        this.show(data.title, data.message, data.type, data.duration);
      });
    }
  }

  /**
   * Show toast notification
   * @param {string} title Notification title
   * @param {string} message Notification message
   * @param {string} type 'info' | 'success' | 'warning' | 'alert' | 'offer'
   * @param {number} duration Auto-dismiss after ms (0 = manual close)
   */
  show(title, message, type = 'info', duration = 4000) {
    const notification = document.createElement('div');
    const id = `toast-${Date.now()}-${Math.random()}`;
    notification.id = id;

    // Determine colors based on type
    const colorClass = this._getColorClass(type);
    const icon = this._getIcon(type);

    notification.className = `
      toast toast-${type} ${colorClass}
      fixed top-4 right-4 
      px-5 py-4 rounded-lg shadow-lg 
      max-w-sm w-full
      animate-slideIn
      flex items-start gap-3
    `;

    notification.innerHTML = `
      <div class="flex-shrink-0 text-xl">${icon}</div>
      <div class="flex-1">
        <div class="font-semibold text-sm">${this._escapeHtml(title)}</div>
        <div class="text-sm opacity-90">${this._escapeHtml(message)}</div>
      </div>
      <button 
        class="flex-shrink-0 text-lg opacity-70 hover:opacity-100"
        onclick="window.notificationSystem.dismiss('${id}')"
      >
        ×
      </button>
    `;

    this.container.appendChild(notification);
    this.notifications.push({
      id,
      element: notification,
      type,
      timeout: null
    });

    // Auto-dismiss
    if (duration > 0) {
      const timeout = setTimeout(() => {
        this.dismiss(id);
      }, duration);

      const notifObj = this.notifications.find(n => n.id === id);
      if (notifObj) notifObj.timeout = timeout;
    }

    console.log(`[Notification] ${type}: ${title}`);
  }

  /**
   * Dismiss notification
   */
  dismiss(id) {
    const index = this.notifications.findIndex(n => n.id === id);
    if (index === -1) return;

    const notif = this.notifications[index];
    
    // Clear timeout
    if (notif.timeout) clearTimeout(notif.timeout);

    // Animate out
    notif.element.classList.add('animate-slideOut');

    // Remove after animation
    setTimeout(() => {
      notif.element.remove();
      this.notifications.splice(index, 1);
    }, 300);
  }

  /**
   * Get color class based on type
   */
  _getColorClass(type) {
    const colors = {
      'info': 'bg-blue-500 text-white',
      'success': 'bg-green-500 text-white',
      'warning': 'bg-yellow-500 text-white',
      'alert': 'bg-red-500 text-white',
      'offer': 'bg-purple-500 text-white'
    };
    return colors[type] || colors['info'];
  }

  /**
   * Get icon emoji based on type
   */
  _getIcon(type) {
    const icons = {
      'info': 'ℹ️',
      'success': '✓',
      'warning': '⚠️',
      'alert': '✕',
      'offer': '💰'
    };
    return icons[type] || icons['info'];
  }

  /**
   * Escape HTML to prevent XSS
   */
  _escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}

// === GLOBAL INSTANCE ===

window.notificationSystem = null;

/**
 * Initialize notification system on page load
 */
function initNotifications() {
  if (!window.notificationSystem) {
    window.notificationSystem = new NotificationSystem();
  }
  return window.notificationSystem;
}

/**
 * Show notification (shorthand)
 */
function showToast(title, message, type = 'info', duration = 4000) {
  if (!window.notificationSystem) {
    window.notificationSystem = new NotificationSystem();
  }
  window.notificationSystem.show(title, message, type, duration);
}

// === INITIALIZE ON DOM READY ===
document.addEventListener('DOMContentLoaded', () => {
  initNotifications();
});
