# FLIX Bilingual Marketplace - Complete Testing Guide

## 🚀 Server Status
✅ **PHP Development Server Running**
- **URL:** http://localhost:8000
- **PHP Version:** 8.0.30
- **Database:** PostgreSQL (via db.php)
- **Status:** Ready for Testing

---

## 📋 Quick Access Links

### Main Entry Points
- 🏠 **Home Page:** http://localhost:8000/index.html
- 🔐 **Login (Arabic):** http://localhost:8000/login-new.php?lang=ar
- 🔐 **Login (English):** http://localhost:8000/login-new.php?lang=en

### User Workflows
- 📝 **Signup (Arabic):** http://localhost:8000/signup-new.php?lang=ar
- 📝 **Signup (English):** http://localhost:8000/signup-new.php?lang=en
- 📋 **Create Service Request (Arabic):** http://localhost:8000/order-new.php?lang=ar
- 📋 **Create Service Request (English):** http://localhost:8000/order-new.php?lang=en
- 📍 **Track Request (Arabic):** http://localhost:8000/track-new.php?request_id=1&lang=ar
- 📍 **Track Request (English):** http://localhost:8000/track-new.php?request_id=1&lang=en
- 💳 **Payment (Arabic):** http://localhost:8000/payment-new.php?request_id=1&lang=ar
- 💳 **Payment (English):** http://localhost:8000/payment-new.php?request_id=1&lang=en
- 🏆 **Receipt & Rating (Arabic):** http://localhost:8000/receipt-new.php?request_id=1&lang=ar
- 🏆 **Receipt & Rating (English):** http://localhost:8000/receipt-new.php?request_id=1&lang=en

### Worker Workflows
- 👷 **Available Requests (Arabic):** http://localhost:8000/worker_requests-new.php?lang=ar
- 👷 **Available Requests (English):** http://localhost:8000/worker_requests-new.php?lang=en
- 💰 **Earnings Dashboard (Arabic):** http://localhost:8000/worker_earnings-new.php?lang=ar
- 💰 **Earnings Dashboard (English):** http://localhost:8000/worker_earnings-new.php?lang=en

---

## 🧪 Testing Workflows

### Workflow 1: User Signup to Service Completion

**Step 1: Create User Account**
1. Go to: http://localhost:8000/signup-new.php?lang=ar
2. Select "أنا أحتاج خدمة" (I need service) tab
3. Fill in:
   - Full Name: محمد علي
   - Email: user@example.com
   - Phone: +201012345678
   - City: Cairo
   - Password: TestPass123!
   - Confirm Password: TestPass123!
4. Click "إنشاء حساب" (Sign Up)
5. ✅ Account created, redirects to login

**Step 2: User Login**
1. Go to: http://localhost:8000/login-new.php?lang=ar
2. Tab should be on "أنا أحتاج خدمة" (User)
3. Enter:
   - Email/Phone: user@example.com
   - Password: TestPass123!
4. Click "تسجيل دخول" (Sign In)
5. ✅ Redirects to usermain.php (user dashboard)

**Step 3: Create Service Request**
1. Go to: http://localhost:8000/order-new.php?lang=ar
2. Step 1 - Select Service: Choose "السباكة" (Plumbing)
3. Step 2 - Describe Problem: Enter "تسرب الماء من الأنبوب" (Water leak from pipe)
4. Step 3 - Location & Budget: 
   - City: Cairo
   - Budget: 500 (ج.م)
5. Step 4 - Confirm Cost:
   - Checking Fee: 300 ج.م (fixed)
   - Fixing Fee: 500 ج.م (budget)
   - Total: 800 ج.م
6. Click "إرسال الطلب" (Submit Request)
7. ✅ Request created with ID displayed

**Step 4: Track Request**
1. Go to: http://localhost:8000/track-new.php?request_id=1&lang=ar
2. View:
   - Request ID: #1
   - Status: Pending (waiting for worker)
   - Service Details
   - Price Breakdown
3. ✅ Page displays tracking information

**Step 5: Process Payment**
1. Go to: http://localhost:8000/payment-new.php?request_id=1&lang=ar
2. Select Payment Amount: "Checking Fee Only" or "Total Cost"
3. Select Payment Method: Card, Wallet, or Transfer
4. Click "الدفع الآن" (Pay Now)
5. ✅ Payment recorded successfully

**Step 6: Complete Service & Rate**
1. Go to: http://localhost:8000/receipt-new.php?request_id=1&lang=ar
2. View:
   - Receipt with service details
   - Worker information
   - Payment summary
3. Rate Worker:
   - Click 5 stars for rating
   - Write review (optional)
   - Click "إرسال التقييم" (Submit Rating)
4. ✅ Rating submitted, worker profile updated

---

### Workflow 2: Worker Signup to Earnings

**Step 1: Create Worker Account**
1. Go to: http://localhost:8000/signup-new.php?lang=ar
2. Click "أنا مقدم خدمة" (I provide service) tab
3. Fill in:
   - Full Name: أحمد محمود
   - Email: worker@example.com
   - Phone: +201098765432
   - Specialization: السباكة (Plumbing)
   - City: Cairo
   - Password: WorkPass123!
   - Confirm Password: WorkPass123!
4. Upload Documents: (optional for testing)
5. Click "إنشاء حساب" (Sign Up)
6. ✅ Worker account created (status: pending approval)

**Step 2: Worker Login**
1. Go to: http://localhost:8000/login-new.php?lang=ar
2. Click "أنا مقدم خدمة" (Provider) tab
3. Enter:
   - Email/Phone: worker@example.com
   - Password: WorkPass123!
4. Click "تسجيل دخول" (Sign In)
5. ✅ Redirects to worker_dashboard.php

**Step 3: Browse Available Requests**
1. Go to: http://localhost:8000/worker_requests-new.php?lang=ar
2. View:
   - Available Requests matching specialization
   - Request cards showing:
     - Service type
     - Problem description
     - Location
     - Budget
     - Total cost (checking + fixing)
3. ✅ Requests displayed in grid

**Step 4: Accept Request**
1. Click "✓ قبول" (Accept) button on any request
2. REQUEST_ID updates: worker_id assigned, status changes to 'accepted'
3. ✅ Request appears in active tasks
4. "Active Tasks" counter increments in header

**Step 5: View Earnings**
1. Go to: http://localhost:8000/worker_earnings-new.php?lang=ar
2. View Today's Stats:
   - Tasks Completed: 0 (will increase after task completion)
   - Total Earned: 0 ج.م
   - Commission (20%): 0 ج.م
   - Net Earnings: 0 ج.م
3. View Earnings Breakdown:
   - Checking Fee: 300 × tasks completed
   - Fixing Fees: Variable amount
   - Platform Commission (20%): -20% of total
   - Your Net: 80% of total
4. ✅ Earnings dashboard displays correctly

---

## 🌐 Language Switching Test

**Test on Every Page:**
1. Open any page in Arabic: ?lang=ar
2. Click language switcher (top-right): "English"
3. ✅ Page content changes to English
4. ✅ HTML dir attribute changes to ltr
5. ✅ Layout reflows for LTR
6. Click back to "العربية"
7. ✅ Page content returns to Arabic
8. ✅ HTML dir attribute returns to rtl

**Example:**
- Arabic: http://localhost:8000/order-new.php?lang=ar
- English: http://localhost:8000/order-new.php?lang=en

---

## 💾 Database Tables Verification

To verify data is being saved, check these queries:

```sql
-- Users table
SELECT * FROM users WHERE user_type = 'user' ORDER BY created_at DESC;

-- Workers table
SELECT * FROM workers WHERE approved = 'pending' ORDER BY created_at DESC;

-- Service Requests
SELECT * FROM service_requests ORDER BY created_at DESC;

-- Payments
SELECT * FROM payments ORDER BY created_at DESC;

-- Ratings
SELECT * FROM ratings ORDER BY created_at DESC;
```

---

## 🎨 UI/UX Features to Test

✅ **Bilingual Support**
- [ ] All text appears in selected language
- [ ] Arabic text is RTL, English is LTR
- [ ] Language switcher appears on every page
- [ ] Form labels and placeholders are translated

✅ **Responsive Design**
- [ ] Desktop (1920px): Full layout
- [ ] Tablet (768px): Grid adjusts
- [ ] Mobile (375px): Single column, readable

✅ **Interactive Elements**
- [ ] Service selection cards highlight on click
- [ ] Star rating system responds to clicks
- [ ] Payment method selection changes
- [ ] Status badges display correct colors
- [ ] Buttons have hover effects

✅ **Animations**
- [ ] Page transitions are smooth (fadeIn)
- [ ] Alerts slide down on appearance
- [ ] Cards lift on hover
- [ ] Buttons have scale effect on click

✅ **Accessibility**
- [ ] Form fields are clearly labeled
- [ ] Error messages appear with red styling
- [ ] Success messages appear with green styling
- [ ] All buttons are keyboard accessible

---

## 🔗 API Integration Points

### Authentication
- `login-new.php`: Validates credentials against users/workers table
- `signup-new.php`: Creates new users/workers with password hashing
- Session: Stores user_id, user_type, user_name, user_city, specialization

### Service Requests
- `order-new.php`: Creates service_requests with status='pending'
- `track-new.php`: Queries service_requests for status updates
- `worker_requests-new.php`: Shows pending requests, accepts with worker_id update

### Payments
- `payment-new.php`: Inserts payments record with transaction_id
- Amounts: checking_fee (300 EGP) + fixing_price (variable)

### Ratings
- `receipt-new.php`: Inserts ratings, updates worker total_rating
- Calculation: total_rating = sum of all ratings, total_reviews = count

### Earnings
- `worker_earnings-new.php`: Calculates daily totals
- Commission: 20% platform, 80% worker
- Formula: net_earnings = (checking_fee + fixing_price) × 0.8

---

## 🚨 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| "Session not found" error | Make sure you're logged in first via login-new.php |
| Database connection error | Verify db.php exists and PostgreSQL is running |
| Language not changing | Clear browser cache, check ?lang parameter in URL |
| Redirect loop | Ensure session variables are being set correctly |
| Pages showing blank | Check browser console for PHP errors |

---

## 📱 Test Accounts (For Manual Testing)

**User Account:**
- Email: user@example.com
- Password: TestPass123!
- Type: I need service

**Worker Account:**
- Email: worker@example.com
- Password: WorkPass123!
- Type: I provide service

**Admin Account:**
- Email: admin@example.com
- Password: AdminPass123!
- Type: Admin

---

## ✅ Final Verification Checklist

- [ ] PHP server running on localhost:8000
- [ ] All 9 pages load without errors
- [ ] Bilingual switching works (Arabic/English)
- [ ] User signup/login flow works
- [ ] Service request creation works
- [ ] Payment processing displays correctly
- [ ] Worker request acceptance works
- [ ] Earnings calculations show 80/20 split
- [ ] Rating system works
- [ ] All links are functional
- [ ] Responsive design works on mobile
- [ ] Database is saving all records

---

## 🎯 Next Steps

1. **Test each workflow completely** using the links above
2. **Verify database records** are being created
3. **Check calculations** (300 EGP + fixing fee, 20% commission)
4. **Test language switching** on every page
5. **Report any issues** or unexpected behavior
6. **Once verified**, integrate with Node.js server (port 3001)

Good luck! 🚀
