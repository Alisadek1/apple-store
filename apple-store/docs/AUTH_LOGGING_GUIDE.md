# Comprehensive Authentication Logging System

## Overview

The comprehensive authentication logging system provides secure logging with error categorization, monitoring capabilities, and sensitive data masking for authentication failures and security events.

## Features

### 1. Error Categorization
- **FORMAT**: Hash format validation errors
- **VERIFICATION**: Password verification failures
- **DATABASE**: Database encoding and retrieval issues
- **CORRUPTION**: Hash corruption detection
- **LOGIN_FAILED**: Failed login attempts
- **LOGIN_SUCCESS**: Successful logins
- **SECURITY**: Security events and alerts
- **REPAIR**: Hash repair operations
- **SYSTEM**: System-level errors

### 2. Log Levels
- **DEBUG**: Detailed debugging information
- **INFO**: General information events
- **WARNING**: Warning conditions
- **ERROR**: Error conditions
- **CRITICAL**: Critical conditions requiring immediate attention

### 3. Sensitive Data Masking
- Password hashes are masked (only first 10 characters shown)
- Passwords are completely masked
- Email addresses are partially masked
- IP addresses are partially masked
- Session IDs are partially masked

## Usage

### Basic Logging

```php
// Include the logger
require_once __DIR__ . '/includes/auth-logger.php';

// Log an authentication event
logAuthenticationEvent('AUTH_VERIFICATION_FAILED', $user_id, [
    'hash' => $stored_hash,
    'password_length' => strlen($password),
    'failure_reason' => 'hash_format_invalid'
], 'ERROR');
```

### Using the Wrapper Function

```php
// The existing logAuthEvent function now uses the comprehensive logger
logAuthEvent('LOGIN_FAILED_AUTH', $user_id, [
    'email' => $user['email'],
    'failure_reason' => 'verification_failed'
], 'WARNING');
```

## Log Files

The system creates multiple log files in the `/logs` directory:

- `auth.log` - Main authentication log (all events)
- `auth_format.log` - Hash format validation errors
- `auth_verification.log` - Password verification events
- `auth_database.log` - Database-related issues
- `auth_corruption.log` - Hash corruption events
- `auth_login_failed.log` - Failed login attempts
- `auth_security.log` - Security events
- `auth_errors.log` - All WARNING, ERROR, and CRITICAL events
- `auth_YYYY-MM-DD.log` - Daily log files

## Database Storage

Important events are also stored in the `auth_logs` database table for monitoring and analysis:

```sql
CREATE TABLE auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    category ENUM('FORMAT', 'VERIFICATION', 'DATABASE', 'CORRUPTION', 'LOGIN_FAILED', 'LOGIN_SUCCESS', 'SECURITY', 'REPAIR', 'SYSTEM') NOT NULL,
    level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL,
    user_id INT NULL,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_uri VARCHAR(255),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Monitoring and Alerts

### Automatic Threshold Monitoring

The system automatically monitors for:

1. **Multiple Failed Logins**: 5+ failed attempts from same IP in 15 minutes
2. **Hash Corruption**: 3+ corruption events in 1 hour
3. **Critical Errors**: 3+ critical errors in 30 minutes
4. **Security Events**: Any emergency access usage

### Getting Statistics

```php
// Get authentication failure statistics
$stats = getAuthFailureStats('24h'); // Options: 1h, 24h, 7d, 30d

// Returns:
// - total_events: Total number of events
// - by_category: Events grouped by category
// - by_level: Events grouped by severity level
// - top_ips: Top IPs with failed login attempts
```

### Monitoring Dashboard

Access the monitoring dashboard at `/admin/auth-monitoring.php` (admin access required):

- Real-time statistics and charts
- Recent authentication events
- Top IPs with failed attempts
- Event details and analysis

## Event Types

### Hash Format Errors
```php
logAuthenticationEvent('AUTH_HASH_FORMAT_INVALID', $user_id, [
    'hash' => $invalid_hash,
    'hash_length' => strlen($invalid_hash),
    'format_issues' => ['length_invalid', 'prefix_missing']
], 'ERROR');
```

### Verification Failures
```php
logAuthenticationEvent('AUTH_VERIFICATION_FAILED', $user_id, [
    'hash' => $stored_hash,
    'password_length' => strlen($password),
    'failure_analysis' => $diagnostic_data
], 'WARNING');
```

### Database Issues
```php
logAuthenticationEvent('AUTH_DATABASE_ENCODING', $user_id, [
    'encoding_issues' => ['utf8_invalid'],
    'whitespace_issues' => ['trailing_whitespace'],
    'hash' => $corrupted_hash
], 'ERROR');
```

### Security Events
```php
logAuthenticationEvent('EMERGENCY_ACCESS_USED', $user_id, [
    'email' => $user['email'],
    'access_method' => 'emergency_password',
    'warning' => 'Should be disabled in production'
], 'CRITICAL');
```

## Maintenance

### Log Cleanup

```php
// Clean up old log entries (keep last 90 days)
$result = cleanupOldAuthLogs(90);
```

### Log Rotation

Daily log files are automatically created. Implement log rotation using system tools like logrotate:

```bash
/path/to/logs/auth*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
}
```

## Security Considerations

1. **Sensitive Data**: All sensitive information is automatically masked in logs
2. **Access Control**: Log files should have restricted permissions (644 or 600)
3. **Database Security**: The auth_logs table contains masked data but should still be secured
4. **Log Retention**: Implement appropriate log retention policies
5. **Monitoring Access**: Restrict access to monitoring dashboard to admin users only

## Integration with Existing Code

The system is designed to be backward compatible. Existing `logAuthEvent()` calls will automatically use the new comprehensive logging system.

### Migration Steps

1. Include `auth-logger.php` in your authentication files
2. Update existing `logAuthEvent()` calls to use appropriate log levels
3. Add error categorization where needed
4. Set up monitoring dashboard access
5. Configure log rotation and cleanup

## Troubleshooting

### Common Issues

1. **Log Directory Permissions**: Ensure the `/logs` directory is writable
2. **Database Connection**: Verify database connection for table creation
3. **File Locking**: Multiple concurrent requests may cause temporary file locking
4. **Disk Space**: Monitor disk space usage for log files

### Debug Mode

Enable debug logging for troubleshooting:

```php
logAuthenticationEvent('DEBUG_EVENT', null, [
    'debug_info' => 'Additional debugging information'
], 'DEBUG');
```

## Performance Considerations

- Database logging is selective (only important events)
- File logging is asynchronous where possible
- Log rotation prevents excessive disk usage
- Monitoring queries are optimized with proper indexes