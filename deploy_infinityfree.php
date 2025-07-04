<?php
// deploy_infinityfree.php - Deployment preparation script for InfinityFree hosting

/**
 * InfinityFree Deployment Preparation Script
 * 
 * This script prepares your DepEd BAC Tracking System for deployment on InfinityFree hosting.
 * It handles URL encryption limitations and ensures compatibility with shared hosting.
 */

require_once 'config.php';

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>InfinityFree Deployment Preparation</title>\n</head>\n<body>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .step { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<h1>🚀 InfinityFree Deployment Preparation</h1>\n";

// Step 1: Check current configuration
echo "<div class='step'>\n";
echo "<h2>Step 1: Current Configuration Check</h2>\n";

$deploymentConfig = require 'deployment_config.php';

echo "<h3>Current Settings:</h3>\n";
echo "<ul>\n";
echo "<li>Environment: " . $deploymentConfig['environment'] . "</li>\n";
echo "<li>URL Encryption: " . ($deploymentConfig['url_encryption']['enabled'] ? 'Enabled' : 'Disabled') . "</li>\n";
echo "<li>URL Obfuscation: " . ($deploymentConfig['url_encryption']['use_obfuscation'] ? 'Enabled' : 'Disabled') . "</li>\n";
echo "<li>Password Hashing: " . ($deploymentConfig['security']['password_hashing'] ? 'Enabled' : 'Disabled') . "</li>\n";
echo "</ul>\n";

// Check OpenSSL availability
if (function_exists('openssl_encrypt')) {
    echo "<div class='success'>✅ OpenSSL is available on this server</div>\n";
} else {
    echo "<div class='warning'>⚠️ OpenSSL is NOT available - will use basic obfuscation</div>\n";
}

echo "</div>\n";

// Step 2: Password Migration Check
echo "<div class='step'>\n";
echo "<h2>Step 2: Password Security Check</h2>\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM tbluser");
    $totalUsers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as hashed_users FROM tbluser WHERE password LIKE '$2y$%'");
    $hashedUsers = $stmt->fetchColumn();
    
    $plainTextUsers = $totalUsers - $hashedUsers;
    
    echo "<p><strong>Password Status:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Total Users: {$totalUsers}</li>\n";
    echo "<li>Hashed Passwords: {$hashedUsers}</li>\n";
    echo "<li>Plain Text Passwords: {$plainTextUsers}</li>\n";
    echo "</ul>\n";
    
    if ($plainTextUsers > 0) {
        echo "<div class='warning'>⚠️ {$plainTextUsers} user(s) still have plain text passwords. Run the password migration first!</div>\n";
        echo "<p><a href='migrate_passwords.php' target='_blank' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔗 Run Password Migration Now</a></p>\n";
    } else {
        echo "<div class='success'>✅ All passwords are properly hashed and secure</div>\n";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error checking passwords: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

echo "</div>\n";

// Step 3: InfinityFree Configuration
echo "<div class='step'>\n";
echo "<h2>Step 3: Configure for InfinityFree</h2>\n";

$infinityFreeConfig = [
    'environment' => 'shared_hosting',
    'url_encryption' => [
        'enabled' => false,
        'use_obfuscation' => true,
        'use_base64' => true
    ],
    'security' => [
        'debug' => false,
        'session_security' => 'medium',
        'force_https' => false,
        'password_hashing' => true
    ],
    'database' => [
        'use_env_config' => false,
        'production' => [
            'host' => 'localhost',
            'dbname' => 'your_production_db',
            'username' => 'your_production_user',
            'password' => 'your_production_password'
        ]
    ],
    'features' => [
        'session_timeout' => true,
        'audit_logging' => false,
        'file_upload_security' => true
    ]
];

if (isset($_POST['apply_infinityfree_config'])) {
    try {
        $configContent = "<?php\n// deployment_config.php - Configuration for different hosting environments\n\n";
        $configContent .= "// Deployment environment configuration\n";
        $configContent .= "return " . var_export($infinityFreeConfig, true) . ";\n";
        $configContent .= "?>";
        
        file_put_contents('deployment_config.php', $configContent);
        echo "<div class='success'>✅ InfinityFree configuration applied successfully!</div>\n";
        
        // Also update session config for production
        $sessionConfig = require 'session_config.php';
        $sessionConfig['session_timeout'] = 30 * 60; // 30 minutes
        $sessionConfig['warning_time'] = 5 * 60;     // 5 minutes
        
        $sessionConfigContent = "<?php\n// session_config.php - Session timeout configuration\n\n";
        $sessionConfigContent .= "// Session timeout configuration\n";
        $sessionConfigContent .= "return " . var_export($sessionConfig, true) . ";\n";
        $sessionConfigContent .= "?>";
        
        file_put_contents('session_config.php', $sessionConfigContent);
        echo "<div class='success'>✅ Session configuration updated for production</div>\n";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error applying configuration: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    }
} else {
    echo "<p>This will configure your system for InfinityFree hosting:</p>\n";
    echo "<ul>\n";
    echo "<li>✅ Disable OpenSSL URL encryption</li>\n";
    echo "<li>✅ Enable basic URL obfuscation</li>\n";
    echo "<li>✅ Set production security settings</li>\n";
    echo "<li>✅ Configure session timeout for production</li>\n";
    echo "</ul>\n";
    
    echo "<form method='post'>\n";
    echo "<button type='submit' name='apply_infinityfree_config' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Apply InfinityFree Configuration</button>\n";
    echo "</form>\n";
}

echo "</div>\n";

// Step 4: Database Configuration
echo "<div class='step'>\n";
echo "<h2>Step 4: Database Configuration for Production</h2>\n";

echo "<div class='info'>\n";
echo "<h3>📝 Update config.php for InfinityFree:</h3>\n";
echo "<p>Replace your database configuration in <code>config.php</code> with your InfinityFree database details:</p>\n";
echo "<pre>\n";
echo htmlspecialchars('<?php
date_default_timezone_set(\'Asia/Singapore\');    
$host = \'your_infinityfree_host\';        // Usually sql000.infinityfree.com or similar
$db   = \'your_database_name\';           // Your InfinityFree database name
$user = \'your_database_username\';       // Your InfinityFree database username
$pass = \'your_database_password\';       // Your InfinityFree database password
$charset = \'utf8mb4\';

// Rest of the file remains the same...');
echo "</pre>\n";
echo "</div>\n";

echo "</div>\n";

// Step 5: Files to Upload
echo "<div class='step'>\n";
echo "<h2>Step 5: Files to Upload to InfinityFree</h2>\n";

echo "<div class='info'>\n";
echo "<h3>📁 Upload these files and folders:</h3>\n";
echo "<ul>\n";
echo "<li>✅ All <code>.php</code> files</li>\n";
echo "<li>✅ <code>assets/</code> folder (CSS, JS, images)</li>\n";
echo "<li>✅ <code>view/</code> folder</li>\n";
echo "<li>✅ <code>sql/</code> folder (for database setup)</li>\n";
echo "<li>✅ <code>.htaccess</code> file</li>\n";
echo "</ul>\n";

echo "<h3>❌ Do NOT upload:</h3>\n";
echo "<ul>\n";
echo "<li>❌ <code>test_session.php</code> (if it exists)</li>\n";
echo "<li>❌ <code>migrate_passwords.php</code> (delete after running)</li>\n";
echo "<li>❌ <code>deploy_infinityfree.php</code> (this file)</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";

// Step 6: Final Checklist
echo "<div class='step'>\n";
echo "<h2>Step 6: Deployment Checklist</h2>\n";

echo "<div class='info'>\n";
echo "<h3>✅ Pre-deployment Checklist:</h3>\n";
echo "<ol>\n";
echo "<li>Run password migration script</li>\n";
echo "<li>Apply InfinityFree configuration (above)</li>\n";
echo "<li>Update config.php with production database details</li>\n";
echo "<li>Import your database to InfinityFree using phpMyAdmin</li>\n";
echo "<li>Upload all necessary files</li>\n";
echo "<li>Test login functionality</li>\n";
echo "<li>Test session timeout (optional)</li>\n";
echo "<li>Delete sensitive files (migrate_passwords.php, deploy_infinityfree.php)</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "</div>\n";

echo "<div class='success'>\n";
echo "<h3>🎉 Ready for Deployment!</h3>\n";
echo "<p>Your DepEd BAC Tracking System is now configured for InfinityFree hosting. The system will work without OpenSSL encryption and use basic URL obfuscation instead.</p>\n";
echo "</div>\n";

echo "</body>\n</html>";
?>
