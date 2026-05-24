# 🚀 Deployment & Team Setup Guide

## 📋 Prerequisites Checklist

Before team members can use the platform, verify:

- [ ] PHP 8.0+ installed (`php -v`)
- [ ] SQLite enabled in PHP (`php -m | grep sqlite`)
- [ ] Node.js 16+ installed (optional, for frontend tools)
- [ ] Port 8000 available (or change in start command)
- [ ] Git installed and repository cloned
- [ ] All team members have [TEST_CREDENTIALS.txt](flix/TEST_CREDENTIALS.txt)

---

## 🎯 Quick Setup (2 Steps)

### Step 1: Navigate to Project
```bash
cd fixlr/flix
```

### Step 2: Start Server
```bash
php -S localhost:8000
```

**That's it!** 🎉

Server runs at: http://localhost:8000

---

## 🌐 Access URLs (Bookmarks)

Save these for quick access:

| Page | URL | Role |
|------|-----|------|
| Homepage | http://localhost:8000/landing.html | Public |
| Login | http://localhost:8000/login.php | Public |
| Signup | http://localhost:8000/signup.php | Public |
| Customer Dashboard | http://localhost:8000/user_dashboard.php | After Login |
| Technician Dashboard | http://localhost:8000/worker_dashboard.php | After Login |
| Admin Dashboard | http://localhost:8000/admin.php | After Login |

---

## 👥 Team Onboarding

### 1. Share Documentation
Send team members these files:
- [TEST_CREDENTIALS.txt](flix/TEST_CREDENTIALS.txt) ⭐ Start here
- [README.md](../README.md) - Project overview
- [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md) - Code organization

### 2. Get System Running
```bash
cd fixlr/flix
php -S localhost:8000
```

### 3. Test Login
1. Open browser: http://localhost:8000/login.php
2. Enter credentials from TEST_CREDENTIALS.txt
3. Click الدخول (Login)
4. Verify redirect to dashboard

### 4. Explore Features
Use checklist below to verify each feature

---

## ✅ Feature Testing Checklist

### Public Pages
- [ ] Landing page loads (http://localhost:8000/landing.html)
- [ ] Services display correctly
- [ ] Cities are visible
- [ ] Booking button works

### Authentication
- [ ] Login page loads
- [ ] Customer login works (user@flix.com / Test@1234)
- [ ] Technician login works (worker@flix.com / Test@1234)
- [ ] Admin login works (admin@flix.com / Test@1234)
- [ ] Wrong password shows error
- [ ] Session persists on refresh

### Customer Dashboard
- [ ] User name displays ("أحمد محمد")
- [ ] Stats show (0 requests, 0 completed, etc.)
- [ ] Navigation buttons visible
- [ ] Logout works

### Technician Dashboard
- [ ] User name displays ("علي الفني")
- [ ] Available requests show
- [ ] Earnings display
- [ ] Navigation buttons visible

### Admin Dashboard
- [ ] Admin panel loads
- [ ] User management visible
- [ ] Service management visible
- [ ] Analytics display

### Database
- [ ] flix.db file exists (`ls flix/flix.db`)
- [ ] Test data persists on refresh
- [ ] New accounts can be created

---

## 🏗️ Development Environment Setup

### IDE Recommendations

**VS Code (Recommended)**
```bash
code fixlr/
```

**Required Extensions:**
- PHP Intelephense
- PHP Server
- SQLite
- Arabic (Arabic) - for RTL text display

### Project Structure Navigation

```
fixlr/
├── README.md                          👈 Start here
├── DATABASE_CONFIGURATION_GUIDE.md    💾 Database info
├── PROJECT_STRUCTURE.md               📁 File organization
└── flix/
    ├── landing.html                   🌐 Homepage
    ├── login.php                      🔐 Authentication
    ├── db.php                         💾 Database connection
    ├── TEST_CREDENTIALS.txt           ✅ Test accounts
    ├── user_dashboard.php             👤 Customer panel
    ├── worker_dashboard.php           👷 Technician panel
    ├── admin.php                      ⚙️ Admin panel
    ├── css/
    │   ├── premium-ui.css             🎨 Design system
    │   ├── variables.css              🎨 CSS variables
    │   ├── responsive.css             📱 Mobile design
    │   └── ...
    ├── js/
    │   ├── notifications.js           🔔 Notifications
    │   ├── socket-client.js           ⚡ Real-time
    │   └── ...
    ├── api/
    │   └── v1/
    │       ├── middleware/            🔒 Validation
    │       └── database/              💾 Migrations
    ├── config/
    │   └── environment.js             ⚙️ Settings
    └── uploads/
        └── workers/                   📤 Worker data
```

---

## 🔒 Security Setup

### Development (Non-Production)
```bash
# Default setup - fine for local development
php -S localhost:8000
```

### Production Pre-Deployment
- [ ] Change all test account passwords
- [ ] Enable HTTPS/SSL
- [ ] Set `DATABASE_URL` to production PostgreSQL
- [ ] Disable debug mode
- [ ] Hide error messages from users
- [ ] Set proper file permissions
- [ ] Enable CORS restrictions
- [ ] Add rate limiting

---

## 🐛 Common Issues & Fixes

### Issue: "Database connection failed"
```
Error: ❌ Connection failed: DATABASE_URL environment variable is missing
```
**Solution:**
1. Delete `flix/flix.db` if it exists
2. Refresh page - SQLite auto-creates
3. Verify flix/ directory is writable

### Issue: "Login page shows blank"
**Solution:**
1. Check browser console (F12) for errors
2. Verify PHP is running (`php -S localhost:8000`)
3. Clear browser cache (Ctrl+Shift+Delete)
4. Check if port 8000 is in use

### Issue: "Arabic text shows as ????"
**Solution:**
1. Check page source has `<meta charset="UTF-8">`
2. Verify Cairo font is loaded (DevTools → Network)
3. Check CSS has `dir="rtl"`
4. Browser encoding set to UTF-8

### Issue: "CSS not loading / styles missing"
**Solution:**
1. Verify `css/` folder exists
2. Check CSS file links in HTML
3. Clear browser cache
4. Check DevTools → Network for 404 errors
5. Restart PHP server

### Issue: "Cannot read property 'addEventListener'"
**Solution:**
- This is normal - React/Babel transformer warning
- Does not affect functionality
- Ignore in development

---

## 📱 Testing on Different Devices

### Mobile Testing (Same Network)
```bash
# Find your IP address
ipconfig getifaddr en0         # macOS
hostname -I                    # Linux
ipconfig                       # Windows (look for IPv4 Address)

# Access from phone on same WiFi:
http://[YOUR_IP]:8000/landing.html
```

### Mobile Testing (Browser DevTools)
```
Chrome/Firefox DevTools
→ Toggle Device Toolbar (Ctrl+Shift+M)
→ Select device (iPhone, Android, etc.)
→ Test responsive design
```

---

## 👥 Role-Based Testing Matrix

### Customer (عميل)
Test Account: `user@flix.com` / `Test@1234`

**Actions to Verify:**
- [ ] View available services
- [ ] Create service request
- [ ] View my requests
- [ ] View profile
- [ ] Update profile
- [ ] See request status updates
- [ ] Make payment
- [ ] Leave rating/review

### Technician (فني)
Test Account: `worker@flix.com` / `Test@1234`

**Actions to Verify:**
- [ ] View available requests
- [ ] Accept job request
- [ ] Update job status
- [ ] View earnings
- [ ] View payment history
- [ ] Update profile
- [ ] See ratings received

### Admin (مدير)
Test Account: `admin@flix.com` / `Test@1234`

**Actions to Verify:**
- [ ] View all users
- [ ] View all technicians
- [ ] Approve/reject technicians
- [ ] View all requests
- [ ] View payments
- [ ] View analytics
- [ ] Manage services
- [ ] Manage cities

---

## 🚀 Deployment Options

### Option 1: Local Development (Current)
```bash
cd flix
php -S localhost:8000
# Use for testing and team development
```

### Option 2: Railway (Cloud Hosting - Recommended)
1. Create account: https://railway.app
2. Connect GitHub repo
3. Add PostgreSQL service
4. Deploy - automatically gets URL

### Option 3: Heroku
1. Create account: https://heroku.com
2. Create new app
3. Add PostgreSQL add-on
4. Deploy using Git

### Option 4: Docker
```bash
docker build -t flix .
docker run -p 8000:8000 flix
# Requires Dockerfile configuration
```

### Option 5: Traditional Server
1. SSH into server
2. Clone repository
3. Run: `php -S 0.0.0.0:8000`
4. Point domain to server IP
5. Set up PostgreSQL database

---

## 📦 Production Deployment Checklist

### Pre-Deployment
- [ ] All features tested locally
- [ ] Database migration script ready
- [ ] PostgreSQL server provisioned
- [ ] SSL certificate obtained
- [ ] Domain configured
- [ ] Environment variables documented

### Deployment
- [ ] Code pushed to production branch
- [ ] DATABASE_URL set to production PostgreSQL
- [ ] Application server started
- [ ] Database migrations run
- [ ] Smoke tests pass
- [ ] Team members notified

### Post-Deployment
- [ ] Monitor error logs daily
- [ ] Check database performance
- [ ] Verify backups running
- [ ] Test critical user flows
- [ ] Collect team feedback

---

## 🔄 Git Workflow

### Clone Repository
```bash
git clone <repository-url>
cd fixlr
```

### Create Feature Branch
```bash
git checkout -b feature/your-feature-name
# Make changes
git add .
git commit -m "Description of changes"
git push origin feature/your-feature-name
```

### Update from Main
```bash
git pull origin main
```

### Merge Pull Request
```bash
git checkout main
git merge feature/your-feature-name
git push origin main
```

---

## 💾 Backup & Recovery

### Backup SQLite Database
```bash
cp flix/flix.db flix/flix.db.backup
```

### Restore SQLite Database
```bash
cp flix/flix.db.backup flix/flix.db
```

### Reset to Fresh State
```bash
# Delete database
rm flix/flix.db

# Refresh page - auto-creates fresh database
# http://localhost:8000/login.php

# Re-run setup
# http://localhost:8000/setup-test-users.php
```

---

## 📊 Monitoring & Logs

### PHP Error Logs
```bash
# Start PHP with errors visible
php -S localhost:8000 -d display_errors=1

# Or check log file
tail -f /var/log/php.log
```

### Database Queries
```php
// Enable in db.php:
error_log("Query: " . $sql);  // Log SQL queries
```

### Browser DevTools
```
F12 → Console: Check for JavaScript errors
F12 → Network: Check for failed requests
F12 → Storage: View session/cookies
```

---

## 🎓 Team Training Plan

### Day 1: Setup & Orientation
- [ ] Everyone clones repository
- [ ] Test accounts verified
- [ ] All features explored
- [ ] Questions answered

### Day 2: Feature Deep-Dive
- [ ] Code structure walkthrough
- [ ] Database schema explained
- [ ] API endpoints demonstrated
- [ ] Development workflow established

### Day 3: Development Starts
- [ ] Feature assignments made
- [ ] Coding standards reviewed
- [ ] Git branching strategy confirmed
- [ ] Daily standup established

---

## 📞 Support & Help

**If stuck:**
1. Check [TEST_CREDENTIALS.txt](flix/TEST_CREDENTIALS.txt) for credentials
2. Review [README.md](../README.md) for quick start
3. See [DATABASE_CONFIGURATION_GUIDE.md](../DATABASE_CONFIGURATION_GUIDE.md) for database issues
4. Check browser console (F12) for errors
5. Ask team lead or refer to documentation

---

## ✨ What's Next?

After successful deployment:

1. **User Testing** - Get real user feedback
2. **Feature Completion** - Implement remaining features
3. **Performance Tuning** - Optimize slow queries
4. **Mobile App** - Consider native apps
5. **Payment Integration** - Connect payment processors
6. **Analytics** - Track user behavior
7. **Marketing** - Launch marketing campaign

---

## 🎯 Success Criteria

Your deployment is successful when:

- ✅ All team members can login
- ✅ Database persists data
- ✅ All pages load without errors
- ✅ Arabic text displays correctly
- ✅ Mobile design is responsive
- ✅ Forms submit successfully
- ✅ Navigations work
- ✅ No console errors

---

**Ready?** Start with [TEST_CREDENTIALS.txt](flix/TEST_CREDENTIALS.txt) and happy deploying! 🚀

