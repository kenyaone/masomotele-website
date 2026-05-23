<?php
/**
 * MTTI MIS Database Repair Script
 * Run this file once to fix the missing unit_id column issue
 * 
 * HOW TO USE:
 * 1. Upload this file to your WordPress root directory (same level as wp-config.php)
 * 2. Access it via browser: https://yoursite.com/mtti-database-repair.php
 * 3. Delete this file after running successfully
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access. You must be an administrator to run this script.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MTTI MIS Database Repair</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        .success {
            background: #00a32a;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error {
            background: #d63638;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .warning {
            background: #dba617;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #2271b1;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .step {
            background: #f6f7f7;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #2271b1;
        }
        pre {
            background: #1d2327;
            color: #50fa7b;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .button {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .button:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 MTTI MIS Database Repair Tool</h1>
        
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'mtti_exam_results';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if (!$table_exists) {
            echo '<div class="error">❌ Error: The exam_results table does not exist. Please activate the MTTI MIS plugin first.</div>';
            echo '<p><a href="' . admin_url('plugins.php') . '" class="button">Go to Plugins</a></p>';
        } else {
            // Check current columns
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
            $column_names = array();
            foreach ($columns as $column) {
                $column_names[] = $column->Field;
            }
            
            echo '<div class="info">ℹ️ <strong>Table Found:</strong> ' . $table_name . '</div>';
            
            // Check if unit_id exists
            if (in_array('unit_id', $column_names)) {
                echo '<div class="success">✅ <strong>All Good!</strong> The unit_id column already exists in your database.</div>';
                echo '<div class="step">';
                echo '<h3>Current Columns:</h3>';
                echo '<pre>' . implode("\n", $column_names) . '</pre>';
                echo '</div>';
                echo '<p>Your database is properly configured. If you\'re still experiencing errors, please check:</p>';
                echo '<ul>';
                echo '<li>Clear your browser cache</li>';
                echo '<li>Deactivate and reactivate the plugin</li>';
                echo '<li>Check for other PHP errors in your error logs</li>';
                echo '</ul>';
            } else {
                echo '<div class="warning">⚠️ <strong>Issue Detected:</strong> The unit_id column is missing from the exam_results table.</div>';
                
                if (isset($_GET['fix']) && $_GET['fix'] == 'true') {
                    echo '<h2>Running Repair...</h2>';
                    
                    // Add the unit_id column
                    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN unit_id bigint(20) NULL AFTER exam_id");
                    
                    if ($result === false) {
                        echo '<div class="error">❌ <strong>Error:</strong> Failed to add unit_id column.<br>';
                        echo 'MySQL Error: ' . $wpdb->last_error . '</div>';
                    } else {
                        echo '<div class="success">✅ Successfully added unit_id column!</div>';
                        
                        // Add index
                        $wpdb->query("ALTER TABLE {$table_name} ADD KEY unit_id (unit_id)");
                        echo '<div class="success">✅ Added index for unit_id column!</div>';
                        
                        // Update plugin version
                        update_option('mtti_mis_db_version', '3.6.2');
                        echo '<div class="success">✅ Updated database version to 3.6.2</div>';
                        
                        echo '<div class="step">';
                        echo '<h3>Repair Complete! Updated Columns:</h3>';
                        $new_columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
                        $new_column_names = array();
                        foreach ($new_columns as $column) {
                            $new_column_names[] = $column->Field;
                        }
                        echo '<pre>' . implode("\n", $new_column_names) . '</pre>';
                        echo '</div>';
                        
                        echo '<div class="info">🎉 <strong>Success!</strong> Your database has been repaired. You can now delete this file and return to using the plugin normally.</div>';
                        echo '<p><a href="' . admin_url('admin.php?page=mtti-mis-certificates') . '" class="button">Go to Certificates</a></p>';
                    }
                } else {
                    echo '<div class="step">';
                    echo '<h3>Current Columns (missing unit_id):</h3>';
                    echo '<pre>' . implode("\n", $column_names) . '</pre>';
                    echo '</div>';
                    
                    echo '<h3>What will this repair do?</h3>';
                    echo '<ol>';
                    echo '<li>Add the <code>unit_id</code> column to the exam_results table</li>';
                    echo '<li>Create an index for the new column for better performance</li>';
                    echo '<li>Update the database version to 3.6.2</li>';
                    echo '</ol>';
                    
                    echo '<div class="warning">⚠️ <strong>Important:</strong> This will modify your database. It\'s recommended to backup your database before proceeding.</div>';
                    
                    echo '<p><a href="?fix=true" class="button">Run Repair Now</a></p>';
                }
            }
        }
        
        // Show header output warning fix if applicable
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>About the Header Warnings</h2>';
        echo '<div class="step">';
        echo '<p>If you\'re seeing "Cannot modify header information" warnings, this is caused by output being sent before headers. This is now fixed in the updated plugin code.</p>';
        echo '<p><strong>The fix:</strong> Updated the certificate generation code to properly clear output buffers before sending PDF headers.</p>';
        echo '</div>';
        ?>
        
        <hr style="margin: 30px 0;">
        <h2>Next Steps</h2>
        <ol>
            <li>If repair was successful, <strong>delete this file</strong> from your server for security</li>
            <li>Replace the plugin files with the updated versions provided</li>
            <li>Test the certificates and transcript generation</li>
            <li>If issues persist, check your PHP error logs</li>
        </ol>
        
        <h2>Files to Update</h2>
        <ul>
            <li><code>includes/class-mtti-mis-upgrader.php</code> - Updated with migration for unit_id</li>
            <li><code>admin/class-mtti-mis-admin-certificates.php</code> - Fixed transcript generation method</li>
        </ul>
        
        <p><strong>Support:</strong> If you need assistance, contact your system administrator.</p>
    </div>
</body>
</html>
