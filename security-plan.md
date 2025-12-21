# Security Plan for Adminability

## Implemented Security Features

### Authentication
- [x] Password hashing with `password_hash()` (PASSWORD_DEFAULT/bcrypt)
- [x] Session-based authentication with 30-minute inactivity timeout
- [x] CSRF token protection on all forms
- [x] Login required for all dashboard pages
- [x] Role-based access control (RBAC)
- [x] Session regeneration on login (prevents session fixation)
- [x] Secure, HttpOnly, SameSite=Strict cookies in production

### Rate Limiting & Brute Force Protection
- [x] Failed login attempt tracking in database
- [x] IP-based rate limiting: 5 attempts per 15 minutes
- [x] Automatic IP lockout after threshold exceeded
- [x] Lockout duration: 15 minutes
- [x] Cloudflare IP detection supported (CF-Connecting-IP header)
- [x] Successful login clears failed attempts

### Input/Output
- [x] Prepared statements (PDO) for all database queries - prevents SQL injection
- [x] `htmlspecialchars()` on all output - prevents XSS
- [x] Input validation on forms

### HTTP Security Headers (.htaccess)
- [x] HTTPS redirect (skipped for .test/localhost)
- [x] HSTS: Strict-Transport-Security (1 year, includeSubDomains)
- [x] X-Frame-Options: SAMEORIGIN (prevents clickjacking)
- [x] X-Content-Type-Options: nosniff
- [x] X-XSS-Protection: 1; mode=block
- [x] Referrer-Policy: strict-origin-when-cross-origin
- [x] PHP version hidden (X-Powered-By removed)

### Other Security Measures
- [x] `noindex, nofollow` meta tags - keeps pages out of search engines
- [x] Directory listing disabled
- [x] Includes folder blocked from direct access
- [x] Hidden login URL (TreePlane)
- [x] Clean URLs (no .php extension visible)

---

## Still Pending

### Environment Variables for Database Credentials
Move database credentials out of code.

**Current:** Credentials are in `src/includes/db.php`

**Better:** Create a `.env` file (never commit to git):
```
DB_HOST=localhost
DB_NAME=adminability
DB_USER=your_user
DB_PASS=your_secure_password
```

---

## Future Security Enhancements

### Account Security
- [ ] Password strength requirements (min length, complexity)
- [ ] Password reset functionality (via email)
- [ ] Two-factor authentication (2FA)
- [ ] "Remember me" with secure tokens (not just session)

### Monitoring & Logging
- [ ] Log all login attempts (success/failure) with timestamps
- [ ] Log sensitive actions (user creation, deletion)
- [ ] Email alerts for suspicious activity (multiple lockouts)
- [ ] Admin-visible security dashboard

### Infrastructure
- [ ] Web Application Firewall (WAF) - Cloudflare free tier works well
- [ ] Regular automated backups of database
- [ ] Content Security Policy (CSP) header
- [ ] Disable unnecessary PHP functions

---

## Current Hosting

**Host:** BigScoots (shared hosting)
**Live URL:** https://adminability.ac

### Recommended Additions
- Consider Cloudflare (free tier) in front of BigScoots for:
  - DDoS protection
  - WAF (Web Application Firewall)
  - Additional SSL layer
  - Hides real server IP
  - Performance CDN
