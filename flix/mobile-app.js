/**
 * Flix Mobile App - React Native Style Component
 * Uber-inspired mobile interface for service booking
 */

class FlixMobileApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      currentScreen: 'home',
      selectedService: null,
      location: null,
      locationPermission: false,
      nearbyWorkers: [],
      activeRequests: [],
      messages: [],
      userLocation: null,
      isWorker: false,
      workerStatus: 'offline'
    };

    this.socket = null;
    this.map = null;
    this.watchId = null;
  }

  componentDidMount() {
    this.initializeApp();
    this.requestLocationPermission();
  }

  componentWillUnmount() {
    if (this.socket) {
      this.socket.disconnect();
    }
    if (this.watchId) {
      navigator.geolocation.clearWatch(this.watchId);
    }
  }

  initializeApp = () => {
    // Initialize Socket.io connection
    this.socket = io('http://localhost:3000');

    this.socket.on('connect', () => {
      console.log('Connected to Flix server');
      this.authenticateUser();
    });

    this.setupSocketListeners();
  };

  authenticateUser = () => {
    // Get user token from localStorage or props
    const token = localStorage.getItem('flix_token');
    const userType = localStorage.getItem('user_type') || 'user';

    if (token) {
      this.socket.emit('auth:login', {
        userId: this.getCurrentUserId(),
        role: userType,
        token: token
      });
    }
  };

  setupSocketListeners = () => {
    // Service request responses
    this.socket.on('request:accepted', (data) => {
      this.showNotification('تم قبول طلبك!', 'الفني في الطريق إليك');
      this.setState({
        currentScreen: 'tracking',
        activeRequest: data
      });
    });

    // New messages
    this.socket.on('chat:new_message', (data) => {
      this.addMessage(data);
      this.showNotification('رسالة جديدة', data.message);
    });

    // Worker status updates
    this.socket.on('worker:status_changed', (data) => {
      this.updateWorkerStatus(data);
    });

    // Emergency alerts
    this.socket.on('emergency:alert', (data) => {
      this.showEmergencyAlert(data);
    });
  };

  requestLocationPermission = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const location = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy
          };

          this.setState({
            userLocation: location,
            locationPermission: true
          });

          // Start location tracking
          this.startLocationTracking();

          // Update server with location
          this.socket.emit('location:update', location);
        },
        (error) => {
          console.error('Location error:', error);
          this.setState({ locationPermission: false });
        }
      );
    }
  };

  startLocationTracking = () => {
    this.watchId = navigator.geolocation.watchPosition(
      (position) => {
        const location = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
          accuracy: position.coords.accuracy
        };

        this.setState({ userLocation: location });
        this.socket.emit('location:update', location);
      },
      (error) => console.error('Location tracking error:', error),
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    );
  };

  createServiceRequest = (serviceType, description) => {
    const requestData = {
      serviceType,
      location: this.state.userLocation,
      description,
      urgency: 'normal'
    };

    this.socket.emit('request:create', requestData);

    this.setState({
      currentScreen: 'searching',
      selectedService: serviceType
    });
  };

  sendMessage = (to, message, requestId) => {
    this.socket.emit('chat:send', {
      to,
      message,
      requestId
    });
  };

  toggleWorkerStatus = () => {
    const newStatus = this.state.workerStatus === 'online' ? 'offline' : 'online';

    this.socket.emit('worker:status_update', {
      status: newStatus,
      serviceType: this.state.selectedService
    });

    this.setState({ workerStatus: newStatus });
  };

  showNotification = (title, message) => {
    // Use browser notification API or custom notification
    if (Notification.permission === 'granted') {
      new Notification(title, { body: message, icon: '/favicon.ico' });
    }
  };

  render() {
    const { currentScreen, selectedService, userLocation, workerStatus } = this.state;

    return (
      <div className="mobile-app">
        {/* Header */}
        <div className="mobile-header">
          <div className="location-bar">
            <MapPin size={20} />
            <span>{userLocation ? 'موقعك الحالي' : 'تحديد الموقع...'}</span>
          </div>
          <div className="user-menu">
            <Menu size={24} />
          </div>
        </div>

        {/* Main Content */}
        <div className="mobile-content">
          {currentScreen === 'home' && this.renderHomeScreen()}
          {currentScreen === 'searching' && this.renderSearchingScreen()}
          {currentScreen === 'tracking' && this.renderTrackingScreen()}
          {currentScreen === 'chat' && this.renderChatScreen()}
          {currentScreen === 'worker' && this.renderWorkerScreen()}
        </div>

        {/* Bottom Navigation */}
        <div className="mobile-bottom-nav">
          <button
            className={currentScreen === 'home' ? 'active' : ''}
            onClick={() => this.setState({ currentScreen: 'home' })}
          >
            <Home size={24} />
            <span>الرئيسية</span>
          </button>
          <button
            className={currentScreen === 'worker' ? 'active' : ''}
            onClick={() => this.setState({ currentScreen: 'worker' })}
          >
            <Briefcase size={24} />
            <span>العمل</span>
          </button>
          <button
            className={currentScreen === 'chat' ? 'active' : ''}
            onClick={() => this.setState({ currentScreen: 'chat' })}
          >
            <MessageSquare size={24} />
            <span>المحادثات</span>
          </button>
        </div>
      </div>
    );
  }

  renderHomeScreen = () => {
    const services = [
      { id: 'plumbing', name: 'سباكة', icon: Droplets, color: 'text-blue-600' },
      { id: 'electrical', name: 'كهرباء', icon: Electric, color: 'text-yellow-600' },
      { id: 'cleaning', name: 'تنظيف', icon: Paintbrush, color: 'text-green-600' },
      { id: 'carpentry', name: 'نجارة', icon: Wrench, color: 'text-orange-600' },
      { id: 'painting', name: 'دهان', icon: Paintbrush, color: 'text-purple-600' },
      { id: 'moving', name: 'نقل', icon: Truck, color: 'text-indigo-600' },
    ];

    return (
      <div className="home-screen">
        <div className="hero-section">
          <h1 className="hero-title">فني في دقائق</h1>
          <p className="hero-subtitle">اختر الخدمة وحدد موقعك</p>
        </div>

        <div className="services-grid">
          {services.map(service => (
            <button
              key={service.id}
              className="service-button"
              onClick={() => this.createServiceRequest(service.id, `${service.name} service requested`)}
            >
              <service.icon size={32} className={service.color} />
              <span>{service.name}</span>
            </button>
          ))}
        </div>

        <div className="quick-actions">
          <button className="emergency-btn" onClick={this.triggerEmergency}>
            <AlertTriangle size={24} />
            <span>طوارئ</span>
          </button>
        </div>
      </div>
    );
  };

  renderSearchingScreen = () => {
    return (
      <div className="searching-screen">
        <div className="searching-animation">
          <div className="pulse-circle"></div>
          <div className="searching-icon">
            <Search size={48} />
          </div>
        </div>
        <h2>جاري البحث عن فني...</h2>
        <p>سنخبرك فور وصول فني قريب</p>
        <button
          className="cancel-btn"
          onClick={() => this.setState({ currentScreen: 'home' })}
        >
          إلغاء الطلب
        </button>
      </div>
    );
  };

  renderTrackingScreen = () => {
    const { activeRequest } = this.state;

    return (
      <div className="tracking-screen">
        <div className="map-container">
          {/* HERE Maps integration would go here */}
          <div className="map-placeholder">
            <MapPin size={48} />
            <p>خريطة التتبع</p>
          </div>
        </div>

        <div className="tracking-info">
          <div className="worker-info">
            <div className="worker-avatar">
              <User size={32} />
            </div>
            <div>
              <h3>الفني في الطريق</h3>
              <p>يصل خلال {activeRequest?.estimatedTime} دقيقة</p>
            </div>
          </div>

          <div className="action-buttons">
            <button className="chat-btn" onClick={() => this.setState({ currentScreen: 'chat' })}>
              <MessageSquare size={20} />
              <span>محادثة</span>
            </button>
            <button className="call-btn">
              <Phone size={20} />
              <span>اتصال</span>
            </button>
          </div>
        </div>
      </div>
    );
  };

  renderChatScreen = () => {
    const { messages } = this.state;
    const [newMessage, setNewMessage] = React.useState('');

    return (
      <div className="chat-screen">
        <div className="chat-header">
          <h3>المحادثة مع الفني</h3>
        </div>

        <div className="messages-list">
          {messages.map(message => (
            <div key={message.id} className={`message ${message.from === this.getCurrentUserId() ? 'sent' : 'received'}`}>
              <p>{message.message}</p>
              <span className="timestamp">{new Date(message.timestamp).toLocaleTimeString()}</span>
            </div>
          ))}
        </div>

        <div className="message-input">
          <input
            type="text"
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
            placeholder="اكتب رسالتك..."
            onKeyPress={(e) => e.key === 'Enter' && this.sendMessageToWorker(newMessage)}
          />
          <button onClick={() => this.sendMessageToWorker(newMessage)}>
            <Send size={20} />
          </button>
        </div>
      </div>
    );
  };

  renderWorkerScreen = () => {
    const { workerStatus, activeRequests } = this.state;

    return (
      <div className="worker-screen">
        <div className="status-toggle">
          <h3>حالة العمل</h3>
          <button
            className={`status-btn ${workerStatus}`}
            onClick={this.toggleWorkerStatus}
          >
            <div className="status-indicator"></div>
            <span>{workerStatus === 'online' ? 'متاح' : 'غير متاح'}</span>
          </button>
        </div>

        <div className="earnings-card">
          <h4>الأرباح اليوم</h4>
          <div className="earnings-amount">150 جنيه</div>
          <div className="earnings-stats">3 طلبات مكتملة</div>
        </div>

        <div className="active-requests">
          <h4>الطلبات النشطة</h4>
          {activeRequests.length === 0 ? (
            <p className="no-requests">لا توجد طلبات حالية</p>
          ) : (
            activeRequests.map(request => (
              <div key={request.id} className="request-card">
                <div className="request-info">
                  <h5>{request.serviceType}</h5>
                  <p>{request.description}</p>
                  <div className="request-meta">
                    <span>{request.distance} km</span>
                    <span>{request.estimatedTime} دقيقة</span>
                  </div>
                </div>
                <div className="request-actions">
                  <button className="accept-btn" onClick={() => this.acceptRequest(request.id)}>
                    قبول
                  </button>
                  <button className="decline-btn" onClick={() => this.declineRequest(request.id)}>
                    رفض
                  </button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    );
  };

  // Helper methods
  getCurrentUserId = () => {
    return localStorage.getItem('user_id') || 'user_123';
  };

  addMessage = (message) => {
    this.setState(prevState => ({
      messages: [...prevState.messages, message]
    }));
  };

  sendMessageToWorker = (message) => {
    if (message.trim()) {
      // Send to worker (implement based on active request)
      this.sendMessage('worker_123', message, 'request_123');
      this.addMessage({
        id: Date.now(),
        from: this.getCurrentUserId(),
        message,
        timestamp: new Date()
      });
    }
  };

  triggerEmergency = () => {
    this.socket.emit('emergency:alert', {
      location: this.state.userLocation,
      type: 'emergency',
      description: 'طلب طوارئ من العميل'
    });
  };

  acceptRequest = (requestId) => {
    this.socket.emit('request:respond', {
      requestId,
      response: 'accept',
      estimatedTime: 15,
      price: 100
    });
  };

  declineRequest = (requestId) => {
    this.socket.emit('request:respond', {
      requestId,
      response: 'decline'
    });
  };

  showEmergencyAlert = (data) => {
    // Show emergency notification
    this.showNotification('تنبيه طوارئ!', 'طلب مساعدة من عميل قريب');
  };

  updateWorkerStatus = (data) => {
    // Update worker status in state
    console.log('Worker status updated:', data);
  };
}

// Export for use in other files
window.FlixMobileApp = FlixMobileApp;