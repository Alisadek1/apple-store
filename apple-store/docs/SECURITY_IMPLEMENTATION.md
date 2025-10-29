# Security Implementation for Diagnostic Tools

## Overview

This document describes the comprehensive security validation and access control system implemented for the password verification diagnostic tools.

## Security Features Implemented

### 1. Admin-Only Access Control

**Implementation**: `SecurityManager::validateAdminAccess()`

- **Session Validation**: Verifies active admin session
- **Role Verification**: Ensures user has 'admin' role
- **Session Integrity**: Validates session fingerprint and timeout
- **Hijacking Detection**: Monitors for suspicious session activity

**Features**:
- Session timeout after 30 minutes of inactivity
- Session fingerprinting based on User-Agent, IP, and browser characteristics
- Automatic session regeneration every 15 minutes
- Detection of rapid IP changes and unusual request patterns

### 2. CSRF Protection

**Implementation**: `SecurityManager::generateCSRFToken()` and `validateCSRFToken()`

- **Token Generation**: Creates unique tokens for each action
- **Database Storage**: Stores tokens with expiration and usage tracking
- **Action-Specific**: Different tokens for different operations
- **One-Time Use**: Tokens are invalidated after use

**Protected Actions**:
- `test_verification` - Password verification testing
- `analyze_hash` - Hash analysis operations
- `check_database` - Database integrity checks
- `generate_repair_script` - Repair script generation
- `test_specific_user` - User-specific testing
- `real_time_verification` - Real-time verification
- `step_by_step_analysis` - Step-by-step analysis
- `automated_repair_recommendation` - Repair recommendations
- `system_health_check` - System health monitoring
- `bulk_user_analysis` - Bulk user analysis
- `execute_repair` - Hash repair operations

### 3. Rate Limiting

**Implementation**: `SecurityManager::checkRateLimit()`

**Rate Limits by Action Type**:
- **Diagnostic Tests**: 10 requests per 5 minutes
- **Repair Operations**: 3 requests per 10 minutes
- **Bulk Analysis**: 5 requests per 15 minutes
- **Hash Generation**: 20 requests per 5 minutes

**Features**:
- IP-based and user-based tracking
- Sliding window implementation
- Automatic cleanup of expired records
- Graceful error messages with retry timing

### 4. Session Validation

**Implementation**: `AdminSecurityMiddleware::validateAdminSession()`

**Validation Checks**:
- Active session verification
- Admin role confirmation
- Session timeout enforcement
- Fingerprint validation
- Permission-based access control

**Security Measures**:
- Automatic session regeneration
- Session hijacking detection
- Request pattern analysis
- IP address monitoring

### 5. Security Audit Logging

**Implementation**: `security_audit_log` database table

**Logged Events**:
- Admin access attempts (allowed/denied)
- CSRF token validation failures
- Rate limit violations
- Suspicious session activity
- Security policy violations

**Log Fields**:
- User ID and IP address
- Action and resource accessed
- Status (allowed/denied/suspicious)
- Reason for decision
- User agent and session ID
- Timestamp

## Database Tables

### security_rate_limits
Tracks rate limiting for different action types per IP/user.

### security_audit_log
Comprehensive security event logging for monitoring and analysis.

### csrf_tokens
Stores CSRF tokens with expiration and usage tracking.

## Usage Examples

### Basic Admin Access Validation
```php
$security = new SecurityManager();
$access = $security->validateAdminAccess('password_diagnostics');
if (!$access['valid']) {
    // Handle access denied
}
```

### CSRF Token Protection
```php
// Generate token
$token = $security->generateCSRFToken('test_verification');

// Validate token
if (!$security->validateCSRFToken($_POST['csrf_token'], 'test_verification')) {
    // Handle invalid token
}
```

### Rate Limit Checking
```php
$rate_limit = $security->checkRateLimit('diagnostic_test');
if (!$rate_limit['allowed']) {
    // Handle rate limit exceeded
}
```

## Frontend Integration

### JavaScript CSRF Token Handling
```javascript
// CSRF tokens are embedded in page
const CSRF_TOKENS = <?php echo json_encode($csrf_tokens); ?>;

// Include in AJAX requests
formData.append('csrf_token', CSRF_TOKENS[action]);
```

### Error Handling
- 403 responses trigger page refresh with access denied message
- 429 responses show rate limit exceeded warnings
- Automatic retry suggestions with timing information

## Security Monitoring

### Statistics Available
- Events by status (allowed/denied/suspicious)
- Unique IP addresses and users
- Top suspicious IP addresses
- Event counts over time periods

### Monitoring Dashboard
The auth monitoring dashboard (`admin/auth-monitoring.php`) provides:
- Real-time security statistics
- Recent security events
- Rate limit status
- Suspicious activity alerts

## Configuration

### Rate Limits
Rate limits can be adjusted in `SecurityManager::$rate_limits`:
```php
private $rate_limits = [
    'diagnostic_test' => ['limit' => 10, 'window' => 300],
    'repair_operation' => ['limit' => 3, 'window' => 600],
    // ...
];
```

### Session Settings
- Session timeout: 30 minutes (1800 seconds)
- Session regeneration: 15 minutes (900 seconds)
- Fingerprint validation: Enabled
- Hijacking detection: 2+ suspicious indicators

## Security Best Practices

1. **Regular Token Cleanup**: Expired CSRF tokens are automatically cleaned up
2. **Rate Limit Monitoring**: Monitor for unusual patterns in rate limit hits
3. **Audit Log Review**: Regularly review security audit logs for suspicious activity
4. **Session Security**: Sessions use secure fingerprinting and timeout mechanisms
5. **Input Validation**: All inputs are sanitized and validated
6. **Error Handling**: Security errors are logged but don't expose sensitive information

## Testing

Run the security validation test suite:
```bash
php test-security-validation.php
```

This tests:
- Security manager initialization
- Admin access validation
- CSRF token generation and validation
- Rate limiting functionality
- Session security features
- Input sanitization
- Security statistics
- Database table creation

## Compliance

This implementation addresses the following security requirements:

- **Requirement 4.1**: Admin-only access for diagnostic tools ✓
- **Requirement 4.3**: CSRF protection and secure logging ✓
- **Requirement 4.5**: Rate limiting and session validation ✓

The security system provides comprehensive protection against:
- Unauthorized access attempts
- Cross-Site Request Forgery (CSRF) attacks
- Session hijacking and fixation
- Brute force attacks through rate limiting
- Privilege escalation attempts