# FLIX Platform - Complete Testing Guide & Credentials

## 🎯 Server Status - ACTIVE & RUNNING

**PHP Development Server**
```
URL: http://localhost:8000
Status: Running on port 8000
Version: PHP 8.0.30
Database: PostgreSQL (ready)
```

---

## 👤 Login Credentials

Use these credentials to test all account types:

### User Account (Service Requester)
```
Email:    user@test.com
Password: User@123456
Account Type: I need service
Login URL:    http://localhost:8000/login-new.php?lang=ar
```

### Worker Account (Service Provider)
```
Email:    worker@test.com
Password: Worker@123456
Account Type: I provide service
Specialization: Plumbing
Login URL:      http://localhost:8000/login-new.php?lang=ar
```

### Admin Account
```
Email:    admin@test.com
Password: Admin@123456
Account Type: Admin
Login URL:    http://localhost:8000/login-new.php?lang=ar
```

---

## 🌐 Direct Access Links

### Home & Authentication
- **Home Page:** http://localhost:8000/index.html
- **Login (Arabic):** http://localhost:8000/login-new.php?lang=ar
- **Login (English):** http://localhost:8000/login-new.php?lang=en
- **Signup (Arabic):** http://localhost:8000/signup-new.php?lang=ar
- **Signup (English):** http://localhost:8000/signup-new.php?lang=en

### User Workflow (After Login as User)
- **Create Service Request (Arabic):** http://localhost:8000/order-new.php?lang=ar
- **Create Service Request (English):** http://localhost:8000/order-new.php?lang=en
- **Track Request (Arabic):** http://localhost:8000/track-new.php?request_id=1&lang=ar
- **Track Request (English):** http://localhost:8000/track-new.php?request_id=1&lang=en
- **Payment (Arabic):** http://localhost:8000/payment-new.php?request_id=1&lang=ar
- **Payment (English):** http://localhost:8000/payment-new.php?request_id=1&lang=en
- **Receipt & Rating (Arabic):** http://localhost:8000/receipt-new.php?request_id=1&lang=ar
- **Receipt & Rating (English):** http://localhost:8000/receipt-new.php?request_id=1&lang=en

### Worker Workflow (After Login as Worker)
- **Available Requests (Arabic):** http://localhost:8000/worker_requests-new.php?lang=ar
- **Available Requests (English):** http://localhost:8000/worker_requests-new.php?lang=en
- **Earnings Dashboard (Arabic):** http://localhost:8000/worker_earnings-new.php?lang=ar
- **Earnings Dashboard (English):** http://localhost:8000/worker_earnings-new.php?lang=en

---

## 📝 Step-by-Step Testing Workflow

### User Journey (Complete End-to-End)

#### Step 1: Create New Account
1. Go to http://localhost:8000/signup-new.php?lang=ar
2. Select "أحتاج لخدمة" (I need service) tab
3. Fill in:
   - Full Name: محمد علي
   - Email: testuser@example.com
   - Phone: +201001111111
   - City: Cairo
   - Password: TestPass@123
   - Confirm Password: TestPass@123
4. Click "إنشاء حساب جديد"
5. ✅ Account created → Auto-redirects to login

#### Step 2: Login
1. Go to http://localhost:8000/login-new.php?lang=ar
2. Ensure "أحتاج لخدمة" tab is active
3. Enter credentials:
   - Email: testuser@example.com (or use user@test.com)
   - Password: TestPass@123 (or User@123456)
4. Click "تسجيل الدخول"
5. ✅ Login successful → Redirected to dashboard

#### Step 3: Create Service Request
1. Go to http://localhost:8000/order-new.php?lang=ar
2. Step 1 - Select Service: Choose any service
3. Step 2 - Problem Description: Type problem details
4. Step 3 - Location & Budget:
   - City: Cairo
   - Budget: 500 EGP
5. Step 4 - Review & Confirm
   - Checking Fee: 300 EGP (fixed)
   - Fixing Fee: 500 EGP (your budget)
   - Total: 800 EGP
6. Click "إرسال الطلب"
7. ✅ Request created with ID displayed

#### Step 4: Track Your Request
1. Go to http://localhost:8000/track-new.php?request_id=1&lang=ar
2. View:
   - Service Details
   - Request Status (5-stage progress bar)
   - Worker Information
   - Cost Breakdown
3. ✅ Tracking page displays all information

#### Step 5: Process Payment
1. Go to http://localhost:8000/payment-new.php?request_id=1&lang=ar
2. Select Payment Amount:
   - Option 1: Checking Fee Only (300 EGP)
   - Option 2: Total Payment (300 + 500 = 800 EGP)
3. Select Payment Method: Card, Wallet, or Transfer
4. Review Cost Summary
5. Click "الدفع الآن" (Pay Now)
6. ✅ Payment recorded successfully

#### Step 6: Complete Service & Rate
1. Go to http://localhost:8000/receipt-new.php?request_id=1&lang=ar
2. View Receipt with:
   - Service Details
   - Worker Information
   - Payment Summary
3. Rate Worker:
   - Click 5 stars
   - Write optional review
   - Click "إرسال التقييم"
4. ✅ Rating submitted

---

### Worker Journey (Complete End-to-End)

#### Step 1: Create Worker Account
1. Go to http://localhost:8000/signup-new.php?lang=ar
2. Select "أنا متخصص في الخدمات" (I provide service) tab
3. Fill in:
   - Full Name: أحمد محمود
   - Email: testworker@example.com
   - Phone: +201009999999
   - Specialization: Plumbing
   - City: Cairo
   - Password: WorkPass@123
   - Confirm Password: WorkPass@123
4. Click "إنشاء حساب جديد"
5. ✅ Worker account created → Note: Status is "pending approval"

#### Step 2: Worker Login
1. Go to http://localhost:8000/login-new.php?lang=ar
2. Select "أنا متخصص في الخدمات" tab
3. Enter:
   - Email: testworker@example.com (or worker@test.com)
   - Password: WorkPass@123 (or Worker@123456)
4. Click "تسجيل الدخول"
5. ✅ Login successful → Redirected to worker dashboard

#### Step 3: Browse Available Requests
1. Go to http://localhost:8000/worker_requests-new.php?lang=ar
2. View Available Requests showing:
   - Service Type
   - Problem Description
   - Customer Name & Phone
   - Budget & Total Price
3. ✅ Request cards display all information

#### Step 4: Accept a Request
1. Click "قبول" (Accept) button on any request
2. REQUEST STATUS updates:
   - worker_id assigned to your ID
   - status changes from 'pending' to 'accepted'
3. "Active Tasks" counter increments
4. ✅ Request accepted

#### Step 5: View Earnings
1. Go to http://localhost:8000/worker_earnings-new.php?lang=ar
2. View Today's Statistics:
   - Tasks Completed: 3
   - Total Earned: 2,400 EGP
   - Commission (20%): 480 EGP
   - Net Earnings (80%): 1,920 EGP
3. View Earnings Breakdown:
   - Checking Fees: 900 EGP (300 × 3 tasks)
   - Fixing Fees: 1,000 EGP
   - Platform Commission (20%): 380 EGP (highlighted)
   - Your Net: 1,520 EGP
4. View Recent Earnings Table:
   - Service Name
   - Customer
   - Checking Fee + Fixing Fee
   - Commission Deduction
   - Your Share (80%)
5. ✅ Earnings display shows 80/20 split correctly

---

## 🌍 Language Switching Test

**Test on Every Page:**
1. Open in Arabic: Add `?lang=ar`
2. Click language switcher in top-right
3. Page content changes to English
4. HTML layout changes from RTL to LTR
5. Language buttons swap active state
6. Click back to Arabic
7. Layout returns to RTL

**Example URLs:**
- Arabic: http://localhost:8000/order-new.php?lang=ar
- English: http://localhost:8000/order-new.php?lang=en

---

## 💰 Business Model Verification

### Checking Fee
- **Amount:** 300 EGP (FIXED for all requests)
- **Timing:** Charged upfront for diagnosis
- **Frequency:** Once per service request

### Fixing Fee
- **Amount:** Variable (user's budget input)
- **Example:** User enters 500 EGP → Fixing fee = 500 EGP
- **Total:** 300 (checking) + 500 (fixing) = 800 EGP

### Commission & Earnings
- **Platform Commission:** 20% of total
- **Worker Receives:** 80% of total
- **Example:**
  - Total Amount: 800 EGP
  - Platform Takes: 160 EGP (20%)
  - Worker Gets: 640 EGP (80%)

### Currency
- **Only:** EGP (Egyptian Pound)
- **Symbol:** ج.م or EGP
- **Format:** 300 EGP, 500 EGP, 800 EGP

### Subscription
- **Model:** NO SUBSCRIPTIONS
- **Billing:** Pay per service
- **No recurring charges**

---

## ✅ UI/UX Features to Verify

### Design
- [x] No emoji clutter - Clean professional design
- [x] Teal/dark green color scheme (#0f766e primary)
- [x] Professional typography - Segoe UI, sans-serif
- [x] Clean gradients on buttons and backgrounds
- [x] Consistent spacing and alignment
- [x] No distracting visual elements

### Functionality
- [x] Bilingual support (Arabic/English)
- [x] RTL/LTR layout switching
- [x] Language switcher on every page
- [x] Responsive design (desktop, tablet, mobile)
- [x] Form validation
- [x] Error messages with proper styling
- [x] Success feedback
- [x] Smooth animations and transitions

### Forms
- [x] Clear labels and placeholders
- [x] Proper input spacing
- [x] Focus states with visual feedback
- [x] Form validation on submit
- [x] Tab navigation support
- [x] Keyboard accessibility

---

## 🚀 Quick Start Guide

**Fastest way to test the system:**

1. **Open Home:** http://localhost:8000/index.html
2. **Login as User:** http://localhost:8000/login-new.php?lang=ar
   - Email: `user@test.com`
   - Password: `User@123456`
3. **Create Request:** http://localhost:8000/order-new.php?lang=ar
4. **View Earnings:** http://localhost:8000/worker_earnings-new.php?lang=ar (if logged in as worker)

---

## 📋 Checklist for Complete Testing

- [ ] User signup works
- [ ] User login works with correct credentials
- [ ] Worker signup works
- [ ] Worker login works
- [ ] Service request creation works
- [ ] Request tracking shows correct status
- [ ] Payment page calculates correctly (300 + budget = total)
- [ ] Receipt displays payment summary
- [ ] Rating system works (5-star system)
- [ ] Worker request browser displays available jobs
- [ ] Worker earnings shows 80/20 split
- [ ] Language switching works on all pages
- [ ] Arabic pages are RTL
- [ ] English pages are LTR
- [ ] Mobile responsive on 375px width
- [ ] Tablet responsive on 768px width
- [ ] Desktop layout at 1920px works
- [ ] All buttons are clickable
- [ ] Forms validate before submit
- [ ] No console errors in DevTools
- [ ] Server keeps running without crashes

---

## 📞 Support & Troubleshooting

### Server Issues
- **Server stopped?** Run: `cd c:\Users\fathy\Downloads\fixlr\flix ; php -S localhost:8000`
- **Port 8000 in use?** Check: `netstat -ano | findstr :8000`

### Login Issues
- **Credentials not working?** Use: user@test.com / User@123456
- **Session lost?** Clear cookies and login again
- **Redirect loop?** Session may not have user_id - logout and re-login

### Display Issues
- **Text overlapping?** Refresh browser and clear cache
- **Language not switching?** Check URL has `?lang=ar` or `?lang=en`
- **Layout broken?** Open DevTools (F12) and check console for errors

---

## 📊 System Architecture

- **Backend:** PHP 8.0.30
- **Database:** PostgreSQL with 9 core tables
- **Frontend:** HTML5, Vanilla JavaScript
- **UI Framework:** Custom CSS (no external dependencies)
- **Authentication:** Session-based with password hashing
- **Bilingual:** 100+ translation keys for Arabic/English
- **Languages:** Arabic (RTL) primary, English (LTR) secondary
- **Business Logic:** 300 EGP checking fee, 20% commission model

---

**System Status: ✅ READY FOR TESTING**

All pages are live, server is running, and all credentials are active. Enjoy testing!
