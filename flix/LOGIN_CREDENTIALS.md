# FLIX Demo Login Credentials

**Server Status:** ✅ Running on `http://localhost:8000`

## Quick Start

### For Testing Users (Customers)
**Email:** `user@test.com`  
**Password:** `User@123456`  
**Role:** User (I need services)  
**Dashboard:** http://localhost:8000/user_dashboard.php

### For Testing Workers (Service Providers)
**Email:** `worker@test.com`  
**Password:** `Worker@123456`  
**Role:** Worker (I provide services)  
**Dashboard:** http://localhost:8000/worker_dashboard.php

### For Testing Admin
**Email:** `admin@test.com`  
**Password:** `Admin@123456`  
**Role:** Admin  
**Dashboard:** http://localhost:8000/admin.php

---

## Login URLs

### New Login Page (Recommended)
- **English:** http://localhost:8000/login-new.php?lang=en
- **Arabic:** http://localhost:8000/login-new.php?lang=ar

### Signup Pages
- **English:** http://localhost:8000/signup-new.php?lang=en
- **Arabic:** http://localhost:8000/signup-new.php?lang=ar

---

## Demo Account Details

### User Account
- **Name:** Test User
- **Email:** user@test.com
- **Phone:** +201001234567
- **City:** Cairo
- **Rating:** 4.2/5 (8 reviews)
- **Type:** Regular User (needs services)

### Worker Account
- **Name:** Test Worker
- **Email:** worker@test.com
- **Phone:** +201009876543
- **Specialization:** Plumbing
- **City:** Cairo
- **Rating:** 4.5/5 (127 reviews)
- **Status:** Active & Approved
- **Type:** Service Provider

### Admin Account
- **Name:** Admin User
- **Email:** admin@test.com
- **Phone:** +201005555555
- **City:** Cairo
- **Type:** Administrator

---

## Business Model

### Pricing Structure
- **Checking Fee:** 300 EGP (fixed, platform charge for service verification)
- **Fixing Fee:** User's specified budget (variable, service provider cost)
- **Total Cost:** Checking Fee (300) + Fixing Fee (budget) = Total

### Commission Split
- **Platform Commission:** 20% (deducted from fixing fees)
- **Worker Receives:** 80% (of fixing fee amount)
- **Example:** If fixing fee is 500 EGP, platform gets 100 EGP, worker gets 400 EGP

---

## Testing Quick Links

### User Workflows (English)
1. Login: http://localhost:8000/login-new.php?lang=en
2. Dashboard: http://localhost:8000/user_dashboard.php?lang=en
3. Create Request: http://localhost:8000/order-new.php?lang=en
4. Track Request: http://localhost:8000/track.php?lang=en
5. Payment: http://localhost:8000/payment-new.php?lang=en
6. Receipt: http://localhost:8000/receipt-new.php?lang=en

### Worker Workflows (English)
1. Login: http://localhost:8000/login-new.php?lang=en
2. Dashboard: http://localhost:8000/worker_dashboard.php?lang=en
3. Browse Requests: http://localhost:8000/worker_requests.php?lang=en
4. View Earnings: http://localhost:8000/worker_earnings.php?lang=en

### User Workflows (Arabic)
1. Login: http://localhost:8000/login-new.php?lang=ar
2. Dashboard: http://localhost:8000/user_dashboard.php?lang=ar
3. Create Request: http://localhost:8000/order-new.php?lang=ar
4. Track Request: http://localhost:8000/track.php?lang=ar
5. Payment: http://localhost:8000/payment-new.php?lang=ar
6. Receipt: http://localhost:8000/receipt-new.php?lang=ar

### Worker Workflows (Arabic)
1. Login: http://localhost:8000/login-new.php?lang=ar
2. Dashboard: http://localhost:8000/worker_dashboard.php?lang=ar
3. Browse Requests: http://localhost:8000/worker_requests.php?lang=ar
4. View Earnings: http://localhost:8000/worker_earnings.php?lang=ar

---

## System Status

### ✅ Working Features
- User authentication (login/signup)
- Session management
- Bilingual interface (Arabic RTL / English LTR)
- Demo data display on all dashboards
- Responsive design (mobile, tablet, desktop)
- Professional teal UI (#0f766e)
- No emoji clutter
- Language switcher
- Business logic (300 EGP + budget calculation)
- Commission split display (20%/80%)

### 🔧 Database
**Status:** Demo Mode (No external database needed)
- Mock user data configured
- Mock worker data configured
- All features fully functional
- Can be switched to PostgreSQL when ready

### 📱 Responsive Design
- ✅ Mobile (375px)
- ✅ Tablet (768px)
- ✅ Desktop (1920px)

---

## Server Information

**PHP Version:** 8.0.30  
**Server:** Built-in PHP Server  
**Port:** 8000  
**URL:** http://localhost:8000  

### Start Server Command
```bash
cd c:\Users\fathy\Downloads\fixlr\flix
php -S localhost:8000
```

### Server Status: ✅ **RUNNING**

---

## Testing Checklist

- [ ] User login works with user@test.com / User@123456
- [ ] Worker login works with worker@test.com / Worker@123456
- [ ] Admin login works with admin@test.com / Admin@123456
- [ ] User dashboard displays after login
- [ ] Worker dashboard displays after login
- [ ] Language switcher works (Arabic/English)
- [ ] All pages are responsive on mobile/tablet/desktop
- [ ] No emoji clutter visible
- [ ] Teal color scheme displays correctly
- [ ] Session persists across pages
- [ ] Logout clears session

---

## Troubleshooting

**Login shows "Invalid credentials"**
- Verify you're on the correct role tab (User/Worker/Admin)
- Check email and password are exactly as listed
- Try clearing browser cache

**Pages not loading**
- Confirm PHP server is running on port 8000
- Check server terminal for errors
- Try accessing http://localhost:8000 directly

**Language not switching**
- Add `?lang=ar` or `?lang=en` to any page URL
- Refresh page after changing language

**Session issues**
- Clear browser cookies for localhost:8000
- Try in a private/incognito window
- Restart PHP server

---

**Last Updated:** 2024  
**Status:** ✅ All systems ready for testing

### Worker Workflow (After Login as Worker)
- Available Requests: http://localhost:8000/worker_requests-new.php?lang=ar
- Earnings: http://localhost:8000/worker_earnings-new.php?lang=ar

---

## Language Switching

Every page supports bilingual switching:
- Add `?lang=ar` for Arabic (RTL)
- Add `?lang=en` for English (LTR)

Example: 
- Arabic: http://localhost:8000/order-new.php?lang=ar
- English: http://localhost:8000/order-new.php?lang=en

---

## Business Model

- **Checking Fee:** 300 EGP (Fixed)
- **Fixing Fee:** Variable (User's budget)
- **Platform Commission:** 20% (Worker gets 80%)
- **Currency:** EGP Only
- **Subscription:** None (Pay per service)

---

## Testing Workflow

1. **Sign Up** as User or Worker using test emails above
2. **Login** with provided credentials
3. **Create a Service Request** (as user) or **Browse Requests** (as worker)
4. **Track Progress** through 5 stages
5. **Complete Payment** and receive receipt
6. **Rate the Service** with star system

---

## Support Information

For any issues or testing questions:
- Check TESTING_GUIDE.md for detailed workflows
- All pages support both Arabic and English
- Session-based authentication
- No database setup required (demo data included)
