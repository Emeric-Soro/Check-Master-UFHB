# Security Policy

## Reporting Security Vulnerabilities

If you discover a security vulnerability in this project, please report it by emailing the maintainers. Do not create public GitHub issues for security vulnerabilities.

## Security Measures Implemented

### 1. HTTP Security Headers

The application implements the following security headers on all responses:

- **Content-Security-Policy**: Restricts resource loading to prevent XSS attacks
- **X-Content-Type-Options**: Prevents MIME type sniffing
- **X-Frame-Options**: Prevents clickjacking attacks
- **Strict-Transport-Security**: Enforces HTTPS connections
- **Referrer-Policy**: Controls referrer information
- **X-XSS-Protection**: Additional XSS protection

### 2. Authentication Security

- **Rate Limiting**: Maximum 5 login attempts per session
- **Lockout Period**: 5-minute lockout after exceeding rate limit
- **Password Hashing**: All passwords are hashed using bcrypt
- **Session Security**: Secure session cookies with httpOnly and sameSite flags

### 3. Input Validation

All user inputs are validated with:
- Type checking
- Format validation (regex patterns)
- Length restrictions
- Whitelist validation for enums

### 4. Database Security

- **Prepared Statements**: All queries use prepared statements to prevent SQL injection
- **Environment Variables**: Database credentials stored in environment variables
- **Least Privilege**: Database user has minimal required permissions

### 5. Dependency Security

- Regular security audits using `composer audit` and `npm audit`
- Dependencies kept up-to-date
- Only necessary dependencies included

### 6. Configuration Security

- **Secrets Management**: All sensitive data (passwords, API keys) stored in environment variables
- **Production Config**: Separate configuration for production with security hardening
- **Error Handling**: Error messages do not expose sensitive information in production

### 7. Code Security

- **XSS Prevention**: Output escaping in all views
- **CSRF Protection**: CSRF tokens for form submissions
- **Query Optimization**: N+1 query problems eliminated
- **Debug Code Removed**: All debug statements removed from production code

## Production Deployment Checklist

Before deploying to production:

- [ ] Set `APP_ENV=production` environment variable
- [ ] Configure all environment variables in `.env` file
- [ ] Use strong, unique passwords for database and SMTP
- [ ] Enable HTTPS/TLS
- [ ] Review and adjust Content-Security-Policy for your domain
- [ ] Set up regular backups
- [ ] Configure log monitoring
- [ ] Test rate limiting functionality
- [ ] Verify all security headers are present
- [ ] Run security scans (composer audit, npm audit)

## Security Best Practices for Developers

When contributing to this project:

1. **Never commit secrets**: Use environment variables for all sensitive data
2. **Validate all inputs**: Always validate and sanitize user input
3. **Use prepared statements**: Never concatenate SQL queries
4. **Escape output**: Always escape data before displaying in HTML
5. **Keep dependencies updated**: Regularly update and audit dependencies
6. **Follow secure coding standards**: Use OWASP guidelines
7. **Test security features**: Include security tests in your code
8. **Remove debug code**: Never leave debug code in production

## Known Security Considerations

### Session Management

Sessions are managed by PHP's default session handler. For high-security applications, consider:
- Using Redis or Memcached for session storage
- Implementing additional session validation (IP address, User-Agent)
- Shorter session timeouts

### File Uploads

If file upload functionality is added:
- Validate file types strictly
- Store files outside web root
- Scan uploaded files for malware
- Limit file sizes

### API Endpoints

If API endpoints are exposed:
- Implement API authentication (tokens, OAuth)
- Add API rate limiting
- Use HTTPS only
- Validate all API inputs

### Content Security Policy (CSP)

The current CSP implementation includes `unsafe-inline` and `unsafe-eval` for script sources to maintain compatibility with existing inline scripts and dynamic code evaluation. This is a known limitation that reduces XSS protection effectiveness.

**Recommended improvements:**
- Refactor all inline scripts to external JavaScript files
- Remove inline event handlers (onclick, onload, etc.)
- Use CSP nonces or hashes for remaining inline scripts
- Remove `unsafe-eval` by refactoring dynamic script evaluation

## Security Updates

This project undergoes regular security audits. Security updates are prioritized and released as soon as possible.

Last Security Audit: October 2025
