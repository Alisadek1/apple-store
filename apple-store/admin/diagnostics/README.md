# Password Diagnostic Tool

## Overview

The Password Diagnostic Tool is a comprehensive web-based interface for troubleshooting password verification issues in the Apple Store application. It provides interactive testing capabilities, hash analysis, database integrity checks, and repair script generation.

## Features

### 1. Verification Test
- Test password verification against specific hashes
- Load the problematic hash for quick testing
- Detailed diagnostic output with success/failure indicators
- Hash format validation and failure analysis

### 2. Hash Analysis
- Detailed analysis of hash format and validity
- Character encoding checks
- Whitespace and null byte detection
- PHP environment compatibility testing

### 3. Database Check
- Password column specification validation
- Database charset and collation verification
- Analysis of all admin user password hashes
- Detection of truncation and corruption issues

### 4. Repair Script Generation
- Generate SQL scripts for hash repair
- Automatic backup creation before modifications
- Support for manual hash replacement or password regeneration
- User-specific repair targeting

### 5. User Testing
- Test specific user credentials
- Load admin user for quick testing
- Complete verification workflow with diagnostics
- User information display with hash preview

## Usage

### Accessing the Tool

1. Log in to the admin panel
2. Navigate to "Password Diagnostics" in the sidebar menu
3. The tool will open with multiple tabs for different functions

### Testing the Problematic Hash

1. Go to the "Verification Test" tab
2. Click "Load Problem Hash" to automatically fill:
   - Password: `admin123`
   - Hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
3. Click "Run Test" to see detailed diagnostic results

### Analyzing Hash Issues

1. Go to the "Hash Analysis" tab
2. Paste the hash you want to analyze
3. Click "Analyze" for detailed format validation and diagnosis

### Checking Database Integrity

1. Go to the "Database Check" tab
2. Click "Run Database Check"
3. Review column specifications and hash analysis for all admin users

### Generating Repair Scripts

1. Go to the "Repair" tab
2. Enter the User ID (typically 1 for admin)
3. Optionally enter a new password for automatic hash generation
4. Click "Generate Script" to create SQL repair commands
5. Use "Copy Script" to copy the generated SQL to clipboard

### Testing Specific Users

1. Go to the "User Test" tab
2. Enter User ID and password
3. Click "Load Admin" for quick admin user testing
4. Click "Test User" for complete verification analysis

## Technical Details

### Hash Format Requirements

- Length: Exactly 60 characters
- Prefix: Must start with `$2y$10$`
- Character Set: Base64 alphabet plus `./`
- No leading/trailing whitespace
- UTF-8 encoding without BOM

### Database Requirements

- Column Type: VARCHAR(255) or larger
- Collation: utf8mb4_unicode_ci recommended
- Character Set: utf8mb4
- No truncation or corruption

### PHP Environment Requirements

- PHP 5.5+ (for password_hash/password_verify functions)
- BCrypt support enabled
- Sufficient memory for hash operations

## Troubleshooting Common Issues

### Hash Format Issues
- **Invalid Length**: Check for database truncation
- **Wrong Prefix**: Verify hash generation method
- **Invalid Characters**: Check for encoding corruption
- **Whitespace**: Trim hash values during retrieval

### Verification Failures
- **Environment Issues**: Verify PHP password functions
- **Database Corruption**: Check character encoding
- **Hash Corruption**: Regenerate hash with known password
- **Encoding Problems**: Validate UTF-8 consistency

### Database Issues
- **Column Too Small**: Increase VARCHAR length
- **Wrong Collation**: Use utf8mb4_unicode_ci
- **Character Set**: Ensure utf8mb4 support
- **Connection Issues**: Verify database connectivity

## Security Considerations

- Tool requires admin authentication
- Sensitive hash information is masked in logs
- Repair operations create backups before changes
- CSRF protection on all diagnostic operations
- Rate limiting prevents abuse of testing features

## Files Structure

```
admin/diagnostics/
├── password-test.php     # Main diagnostic interface
└── README.md            # This documentation

includes/
└── auth-functions.php   # Enhanced authentication functions

test-diagnostic-tool.php # Command-line test script
```

## API Endpoints

The diagnostic tool provides AJAX endpoints for:

- `test_verification`: Test password against hash
- `analyze_hash`: Detailed hash analysis
- `check_database`: Database integrity check
- `generate_repair_script`: SQL repair script generation
- `test_specific_user`: User-specific testing

All endpoints return JSON responses with success/error status and detailed results.