# ⚡ TEAM QUICKSTART - FLIX Marketplace

**For new team members: Read this first (2 minutes)** ⏱️

---

## 🚀 Start in 3 Steps

### Step 1️⃣ Open Terminal
```bash
cd fixlr/flix
```

### Step 2️⃣ Start Server
```bash
php -S localhost:8000
```

### Step 3️⃣ Open Browser
```
http://localhost:8000/landing.html
```

**✅ Done!** System is running.

---

## 🔐 Test Account Credentials

**All accounts use password:** `Test@1234`

```
👤 Customer:    user@flix.com
👷 Technician:  worker@flix.com  
⚙️ Admin:       admin@flix.com
```

**→ Full details:** [TEST_CREDENTIALS.txt](TEST_CREDENTIALS.txt)

---

## 📖 Documentation Map

| What You Need | Where to Find |
|---|---|
| **Accounts & URLs** | [TEST_CREDENTIALS.txt](TEST_CREDENTIALS.txt) |
| **Project Overview** | [README.md](../README.md) |
| **File Organization** | [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md) |
| **Database Setup** | [DATABASE_CONFIGURATION_GUIDE.md](../DATABASE_CONFIGURATION_GUIDE.md) |
| **Team Deployment** | [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md) |
| **Database Status** | [DATABASE_FIX_SUMMARY.md](DATABASE_FIX_SUMMARY.md) |

---

## 🌐 Key URLs

| Page | URL | Login Required? |
|---|---|---|
| Landing Page | http://localhost:8000/landing.html | ❌ No |
| Login | http://localhost:8000/login.php | ❌ No |
| Customer Dashboard | http://localhost:8000/user_dashboard.php | ✅ Yes (user@flix.com) |
| Tech Dashboard | http://localhost:8000/worker_dashboard.php | ✅ Yes (worker@flix.com) |
| Admin Dashboard | http://localhost:8000/admin.php | ✅ Yes (admin@flix.com) |

---

## 💻 Tech Stack at a Glance

```
Frontend:  HTML5, CSS3, JavaScript, React
Backend:   PHP 8.0+
Database:  SQLite (dev) / PostgreSQL (prod)
Language:  Arabic (RTL layout)
Auth:      bcrypt + Sessions
```

---

## 📁 Project Structure (Simplified)

```
fixlr/                           Root project folder
├── README.md                    👈 Project overview
├── flix/                        Main application
│   ├── TEST_CREDENTIALS.txt     Test accounts ⭐
│   ├── landing.html             Homepage
│   ├── login.php                Login page
│   ├── db.php                   Database connection
│   ├── user_dashboard.php       Customer dashboard
│   ├── worker_dashboard.php     Tech dashboard
│   ├── admin.php                Admin dashboard
│   ├── api/                     API endpoints
│   ├── css/                     Styling (premium-ui.css)
│   ├── js/                      JavaScript utilities
│   └── flix.db                  SQLite database (auto-created)
├── PROJECT_STRUCTURE.md         File organization
├── DATABASE_CONFIGURATION_GUIDE.md  Database info
└── DEPLOYMENT_GUIDE.md          Team setup guide
```

---

## ✨ Features Overview

### 👥 Three User Roles
- **Customer (عميل):** Books services
- **Technician (فني):** Provides services  
- **Admin (مدير):** Manages platform

### 🔧 Six Services Available
🚰 Plumbing | ⚡ Electrical | 🧹 Cleaning | 🔨 Carpentry | 🎨 Painting | 📦 Moving

### 🏙️ Four Cities
القاهرة | الجيزة | الإسكندرية | المنصورة

### 🎨 Beautiful UI
- Premium design with gradients
- Fully Arabic RTL support
- Responsive mobile design
- Smooth animations

---

## 🔒 Security Notes

✅ Passwords hashed with bcrypt
✅ Session-based authentication  
✅ SQL injection protection
✅ Database auto-initialization

⚠️ **Important:** These are TEST credentials for development only.
For production: Use strong passwords and PostgreSQL.

---

## 🎯 First Time Test Checklist

- [ ] Server running? (See Step 1-2 above)
- [ ] Landing page loads? (http://localhost:8000/landing.html)
- [ ] Can login? (Use user@flix.com / Test@1234)
- [ ] Dashboard shows? (After login)
- [ ] Arabic text visible? (Should show عميل, فني, etc.)
- [ ] Mobile responsive? (Zoom browser to 50%)
- [ ] No errors? (Check F12 console)

**All checks pass?** ✅ You're ready to develop!

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---|---|
| "Database connection failed" | Delete `flix/flix.db` and refresh |
| "Login shows blank" | Check PHP running: `php -S localhost:8000` |
| "Arabic shows as ????" | Check UTF-8 encoding in browser |
| "CSS not loading" | Clear cache: Ctrl+Shift+Delete |
| "Port 8000 in use" | Use different port: `php -S localhost:8001` |

**Still stuck?** → Check [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md)

---

## 📚 Next Steps

1. ✅ **Setup Complete** - Server running, can login
2. 📖 **Read Documentation** - Understand the system
3. 🔍 **Explore Code** - Review project structure
4. 💻 **Start Development** - Pick a feature to work on
5. 🤝 **Team Collaboration** - Use Git for version control

---

## 🎓 Learning Resources

### Understanding Database
- [DATABASE_CONFIGURATION_GUIDE.md](../DATABASE_CONFIGURATION_GUIDE.md) - Complete database info
- [DATABASE_FIX_SUMMARY.md](DATABASE_FIX_SUMMARY.md) - What was fixed

### Understanding Code Structure  
- [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md) - File organization
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) - What was implemented

### Understanding Deployment
- [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md) - Team setup & deployment
- [README.md](../README.md) - Full project overview

---

## 👥 Team Roles & Responsibilities

### Frontend Developer
- Update HTML/CSS in *.php files
- Improve UI/UX with premium-ui.css
- Ensure Arabic RTL works
- Test responsive design

### Backend Developer
- Develop API endpoints in api/
- Implement business logic in *.php
- Database migrations & queries
- Security & performance

### Full-Stack Developer
- Full development across stack
- Feature completion end-to-end
- Testing & bug fixes
- Documentation updates

### QA/Tester
- Test all features systematically
- Report bugs with details
- Verify fixes work
- Create test cases

---

## 📞 Getting Help

**When you need help:**

1. **Check Documentation First**
   - [TEAM_QUICKSTART.md](TEAM_QUICKSTART.md) (this file)
   - [README.md](../README.md)
   - [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md)

2. **Check Code**
   - Browse [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md)
   - Read file comments
   - Check similar implementations

3. **Ask Team**
   - Daily standup meetings
   - Team chat/Slack
   - Code review discussions

4. **Debug**
   - Browser DevTools (F12)
   - Check error logs
   - Enable PHP error display

---

## ⚡ Pro Tips

### Speed Up Development
```bash
# Save this as your daily command:
cd fixlr/flix && php -S localhost:8000
```

### Monitor Database Changes
```bash
# Watch database file:
watch ls -lah flix/flix.db
```

### Debug Database Queries
```php
// Add to db.php:
error_log("Query executed: " . $sql);
```

### Test All Roles Quickly
```
User:    http://localhost:8000/user_dashboard.php
Tech:    http://localhost:8000/worker_dashboard.php
Admin:   http://localhost:8000/admin.php
```

---

## 🎯 Success Indicators

You've successfully onboarded when:

✅ Server starts without errors  
✅ Can login with all 3 test accounts  
✅ Dashboards display user information  
✅ Arabic text shows correctly  
✅ Understand [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md)  
✅ Know where to find database info  
✅ Can find the code you need to modify  

---

## 📋 Daily Workflow

```
1. Start Day
   └─ cd fixlr/flix && php -S localhost:8000

2. Pick Feature/Bug
   └─ Check PROJECT_STRUCTURE.md for location

3. Make Changes
   └─ Edit code, test in browser

4. Commit Changes
   └─ git add . && git commit -m "Description"

5. Create Pull Request
   └─ Push to GitHub, ask for review

6. Code Review
   └─ Address feedback, merge when approved
```

---

## 🚀 Ready to Start?

```bash
# Navigate to project
cd fixlr/flix

# Start server
php -S localhost:8000

# Open browser
# http://localhost:8000/landing.html

# Login with credentials from TEST_CREDENTIALS.txt
# Start exploring!
```

---

## 📚 Complete Documentation Index

| Document | Purpose | Length |
|---|---|---|
| **TEAM_QUICKSTART.md** | This file - Quick overview | 5 min read |
| **README.md** | Full project introduction | 10 min read |
| **TEST_CREDENTIALS.txt** | All test accounts & info | 2 min read |
| **PROJECT_STRUCTURE.md** | File organization guide | 10 min read |
| **DATABASE_CONFIGURATION_GUIDE.md** | Database technical details | 15 min read |
| **DEPLOYMENT_GUIDE.md** | Team setup & deployment | 15 min read |
| **DATABASE_FIX_SUMMARY.md** | Database fixes & status | 5 min read |
| **IMPLEMENTATION_SUMMARY.md** | What was built/fixed | 10 min read |

---

## 🎯 System Status

**✅ Production Ready:**
- ✅ User authentication system
- ✅ Three-role dashboard system
- ✅ Database with auto-initialization
- ✅ Premium UI with Arabic support
- ✅ API structure in place
- ✅ Test data pre-loaded
- ✅ Security best practices implemented

**🔄 In Progress:**
- 🔄 Signup page styling
- 🔄 Worker dashboard features
- 🔄 Admin dashboard features
- 🔄 Service request workflow

**📋 Future Features:**
- 📋 Payment processing
- 📋 Real-time notifications
- 📋 Map integration
- 📋 Mobile app

---

## 💡 Pro Tip: Bookmarks

Save these URLs as bookmarks for quick access:

```
Landing:     http://localhost:8000/landing.html
Login:       http://localhost:8000/login.php
User:        http://localhost:8000/user_dashboard.php
Tech:        http://localhost:8000/worker_dashboard.php
Admin:       http://localhost:8000/admin.php
Setup:       http://localhost:8000/setup-test-users.php
```

---

**Questions?** → Check the documentation or ask your team lead! 🚀

**Ready to code?** → Start with [PROJECT_STRUCTURE.md](../PROJECT_STRUCTURE.md) to find where the code lives.

