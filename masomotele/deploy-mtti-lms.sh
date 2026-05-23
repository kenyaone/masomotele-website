#!/bin/bash
# ============================================================
# M.T.T.I LMS - Complete Deployment Script
# For Ubuntu 22.04 / 24.04 LTS
# Run as: sudo bash deploy-mtti-lms.sh
# ============================================================

set -e

echo "╔══════════════════════════════════════════════╗"
echo "║   M.T.T.I LMS - Full Deployment Script      ║"
echo "║   Masomotele Technical Training Institute    ║"
echo "╚══════════════════════════════════════════════╝"
echo ""

# Check root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Run as root: sudo bash deploy-mtti-lms.sh"
    exit 1
fi

# ============================================================
# CONFIGURATION - EDIT THESE FOR EACH SCHOOL
# ============================================================
read -p "🏫 School/Location name (e.g. Sagaas Center, Eldoret): " SCHOOL_LOCATION
read -p "📋 Project name (e.g. World Possible Kenya): " PROJECT_NAME
read -p "🌐 Server IP address (e.g. 192.168.0.63): " SERVER_IP
read -p "📧 Admin email (e.g. admin@mtti.ac.ke): " ADMIN_EMAIL
read -p "🔑 Admin password (min 6 chars): " ADMIN_PASS
read -p "👤 Admin name: " ADMIN_NAME
read -p "📱 Admin phone: " ADMIN_PHONE

DB_NAME="mtti_lms"
DB_USER="mtti_user"
DB_PASS="MttiLms2025!"
SITE_URL="http://${SERVER_IP}/mtti-lms"
GITHUB_REPO="kenyaone/mtti-lms"

echo ""
echo "📦 Installing system packages..."
echo "================================"

# Update system
apt update -y
apt upgrade -y

# Install Apache, PHP, MariaDB
apt install -y apache2 mariadb-server php php-mysql php-curl php-json php-mbstring php-xml php-zip php-gd libapache2-mod-php unzip wget git poppler-utils

# Enable Apache modules
a2enmod rewrite headers ssl
systemctl enable apache2
systemctl start apache2
systemctl enable mariadb
systemctl start mariadb

echo ""
echo "🗄️ Setting up database..."
echo "========================="

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Create all tables
mysql ${DB_NAME} << 'SQLEOF'
-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') DEFAULT 'student',
    status ENUM('active','suspended') DEFAULT 'active',
    photo VARCHAR(255),
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Classes
CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    project_name VARCHAR(255),
    instructor_id INT UNSIGNED,
    status ENUM('active','draft','archived') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Lessons
CREATE TABLE IF NOT EXISTS lessons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    content_html LONGTEXT,
    sort_order INT DEFAULT 0,
    status ENUM('published','draft') DEFAULT 'published',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Lesson Files
CREATE TABLE IF NOT EXISTS lesson_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255),
    filename VARCHAR(255),
    filepath VARCHAR(500),
    filetype VARCHAR(50),
    filesize BIGINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Enrolments
CREATE TABLE IF NOT EXISTS enrolments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, class_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Completions
CREATE TABLE IF NOT EXISTS completions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Quizzes
CREATE TABLE IF NOT EXISTS quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT 30,
    max_attempts INT DEFAULT 2,
    pass_mark DECIMAL(5,2) DEFAULT 50,
    shuffle_questions TINYINT(1) DEFAULT 0,
    show_answers TINYINT(1) DEFAULT 1,
    status ENUM('active','draft') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Questions
CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    type ENUM('mcq','true_false','short_answer','essay','fill_blank','drag_drop','matching','scenario') DEFAULT 'mcq',
    question_text TEXT NOT NULL,
    options_json TEXT,
    correct_answer TEXT,
    points DECIMAL(5,2) DEFAULT 1,
    explanation TEXT,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Quiz Attempts
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    score DECIMAL(5,2) DEFAULT 0,
    total_points DECIMAL(5,2) DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    passed TINYINT(1) DEFAULT 0,
    answers_json LONGTEXT,
    started_at DATETIME,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Badges
CREATE TABLE IF NOT EXISTS badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'star',
    color VARCHAR(20) DEFAULT 'primary',
    criteria_type VARCHAR(50),
    criteria_value INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User Badges
CREATE TABLE IF NOT EXISTS user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_id INT UNSIGNED NOT NULL,
    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Forum Topics
CREATE TABLE IF NOT EXISTS forum_topics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    pinned TINYINT(1) DEFAULT 0,
    locked TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Forum Posts
CREATE TABLE IF NOT EXISTS forum_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    teacher_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Analytics (DataPost)
CREATE TABLE IF NOT EXISTS analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    page_url VARCHAR(500),
    class_id INT UNSIGNED,
    lesson_id INT UNSIGNED,
    time_spent INT DEFAULT 0,
    event_type VARCHAR(50) DEFAULT 'pageview',
    device_info VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    synced TINYINT(1) DEFAULT 0,
    synced_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Courier Sync Log
CREATE TABLE IF NOT EXISTS courier_sync_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_info VARCHAR(255),
    action VARCHAR(50),
    entries_count INT DEFAULT 0,
    emails_count INT DEFAULT 0,
    destination VARCHAR(100),
    status VARCHAR(20) DEFAULT 'success',
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Certificates
CREATE TABLE IF NOT EXISTS certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    certificate_number VARCHAR(50) UNIQUE,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQLEOF

echo "✅ Database tables created"

echo ""
echo "📥 Downloading LMS from GitHub..."
echo "================================="

# Clone from GitHub
cd /tmp
rm -rf mtti-lms
git clone https://github.com/${GITHUB_REPO}.git mtti-lms

# Copy to web root
rm -rf /var/www/html/mtti-lms
cp -R mtti-lms /var/www/html/mtti-lms

# Create upload directories
mkdir -p /var/www/html/mtti-lms/assets/uploads/{videos,files,images,html}
mkdir -p /var/www/html/mtti-lms/backups

# Download Bootstrap locally
mkdir -p /var/www/html/mtti-lms/assets/{css,js,fonts}
wget -q -O /var/www/html/mtti-lms/assets/css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css
wget -q -O /var/www/html/mtti-lms/assets/css/bootstrap-icons.min.css https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css
wget -q -O /var/www/html/mtti-lms/assets/js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js
wget -q -O /var/www/html/mtti-lms/assets/fonts/bootstrap-icons.woff2 https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2
wget -q -O /var/www/html/mtti-lms/assets/fonts/bootstrap-icons.woff https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff

# Fix font path in CSS
sed -i 's|url("./fonts/|url("../fonts/|g' /var/www/html/mtti-lms/assets/css/bootstrap-icons.min.css

echo "✅ Files downloaded"

echo ""
echo "⚙️ Configuring LMS..."
echo "====================="

# Update config
CONFIG_FILE="/var/www/html/mtti-lms/config/app.php"
if [ -f "$CONFIG_FILE" ]; then
    sed -i "s|define('SITE_URL', '.*')|define('SITE_URL', '${SITE_URL}')|" "$CONFIG_FILE"
    sed -i "s|define('DB_HOST', '.*')|define('DB_HOST', 'localhost')|" "$CONFIG_FILE"
    sed -i "s|define('DB_NAME', '.*')|define('DB_NAME', '${DB_NAME}')|" "$CONFIG_FILE"
    sed -i "s|define('DB_USER', '.*')|define('DB_USER', '${DB_USER}')|" "$CONFIG_FILE"
    sed -i "s|define('DB_PASS', '.*')|define('DB_PASS', '${DB_PASS}')|" "$CONFIG_FILE"
    echo "✅ Config updated"
else
    echo "⚠️ Config file not found, creating..."
    mkdir -p /var/www/html/mtti-lms/config
    cat > "$CONFIG_FILE" << CONFIGEOF
<?php
// Site
define('SITE_NAME', 'M.T.T.I Learning Management System');
define('SITE_MOTTO', 'Start Learning, Start Earning');
define('SITE_URL', '${SITE_URL}');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');

// Session
define('SESSION_LIFETIME', 86400);

// Upload limits
define('MAX_VIDEO_SIZE', 524288000);
define('MAX_FILE_SIZE', 52428800);
define('MAX_IMAGE_SIZE', 5242880);
define('MAX_HTML_SIZE', 10485760);

// Allowed extensions
define('ALLOWED_VIDEO', 'mp4,webm,ogg,avi,mkv');
define('ALLOWED_FILES', 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip');
define('ALLOWED_IMAGES', 'jpg,jpeg,png,gif,webp');
define('ALLOWED_HTML', 'html,htm');
CONFIGEOF
    echo "✅ Config created"
fi

# Create admin user
ADMIN_HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_DEFAULT);")
mysql ${DB_NAME} -e "INSERT IGNORE INTO users (name, email, phone, password, role, status, created_at) VALUES ('${ADMIN_NAME}', '${ADMIN_EMAIL}', '${ADMIN_PHONE}', '${ADMIN_HASH}', 'admin', 'active', NOW());"
echo "✅ Admin user created"

# Create version file
cat > /var/www/html/mtti-lms/version.json << VEOF
{
    "version": "2.7.0",
    "updated_at": "$(date '+%Y-%m-%d %H:%M:%S')",
    "description": "Fresh deployment at ${SCHOOL_LOCATION}",
    "school_location": "${SCHOOL_LOCATION}",
    "project_name": "${PROJECT_NAME}"
}
VEOF

echo ""
echo "🔧 Configuring Apache..."
echo "========================"

# Set permissions
chown -R www-data:www-data /var/www/html/mtti-lms
chmod -R 755 /var/www/html/mtti-lms

# Enable AllowOverride
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Create .htaccess with CORS
cat > /var/www/html/mtti-lms/.htaccess << 'HTEOF'
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type"
HTEOF

# Increase PHP limits
PHP_INI=$(php -r "echo php_ini_loaded_file();")
if [ -f "$PHP_INI" ]; then
    sed -i 's/max_execution_time = 30/max_execution_time = 300/' "$PHP_INI"
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 512M/' "$PHP_INI"
    sed -i 's/post_max_size = 8M/post_max_size = 512M/' "$PHP_INI"
    sed -i 's/memory_limit = 128M/memory_limit = 256M/' "$PHP_INI"
fi
# Also fix Apache PHP ini
for ini in /etc/php/*/apache2/php.ini; do
    if [ -f "$ini" ]; then
        sed -i 's/max_execution_time = 30/max_execution_time = 300/' "$ini"
        sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 512M/' "$ini"
        sed -i 's/post_max_size = 8M/post_max_size = 512M/' "$ini"
        sed -i 's/memory_limit = 128M/memory_limit = 256M/' "$ini"
    fi
done

systemctl restart apache2

echo "✅ Apache configured"

echo ""
echo "🌐 Configuring static IP..."
echo "==========================="

# Set static IP with NetworkManager
CURRENT_CONN=$(nmcli -t -f NAME,DEVICE con show --active | grep -v lo | head -1 | cut -d: -f1)
if [ -n "$CURRENT_CONN" ]; then
    nmcli con mod "$CURRENT_CONN" ipv4.addresses "${SERVER_IP}/24"
    nmcli con mod "$CURRENT_CONN" ipv4.method manual
    nmcli con mod "$CURRENT_CONN" ipv4.gateway "$(echo $SERVER_IP | cut -d. -f1-3).1"
    nmcli con mod "$CURRENT_CONN" ipv4.dns "8.8.8.8,8.8.4.4"
    echo "✅ Static IP set to ${SERVER_IP}"
    echo "⚠️ Run 'sudo nmcli con up \"${CURRENT_CONN}\"' to apply"
else
    echo "⚠️ No active connection found. Set static IP manually."
fi

echo ""
echo "🤖 Installing Ollama AI (optional)..."
echo "======================================"
read -p "Install Ollama for AI quiz generation? (y/n): " INSTALL_AI
if [ "$INSTALL_AI" = "y" ]; then
    curl -fsSL https://ollama.ai/install.sh | sh
    systemctl enable ollama
    systemctl start ollama
    sleep 3
    ollama pull phi3:mini
    echo "✅ Ollama + phi3:mini installed"
else
    echo "⏭️ Skipped AI installation"
fi

echo ""
echo "🔧 Initializing Git..."
echo "======================"
cd /var/www/html/mtti-lms
git config --global --add safe.directory /var/www/html/mtti-lms
git config --global user.email "${ADMIN_EMAIL}"
git config --global user.name "${ADMIN_NAME}"
git init 2>/dev/null || true
echo "assets/uploads/" > .gitignore
echo "backups/" >> .gitignore
git add -A
git commit -m "v2.7 - Fresh deployment at ${SCHOOL_LOCATION}" 2>/dev/null || true
echo "✅ Git initialized"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║         ✅ DEPLOYMENT COMPLETE!              ║"
echo "╠══════════════════════════════════════════════╣"
echo "║                                              ║"
echo "║  🌐 LMS URL: ${SITE_URL}"
echo "║  👤 Admin: ${ADMIN_EMAIL}"
echo "║  🔑 Password: ${ADMIN_PASS}"
echo "║  🏫 Location: ${SCHOOL_LOCATION}"
echo "║  📋 Project: ${PROJECT_NAME}"
echo "║                                              ║"
echo "║  📱 Courier App:                             ║"
echo "║  ${SITE_URL}/courier-standalone.html"
echo "║                                              ║"
echo "║  📊 DataPost Dashboard:                      ║"
echo "║  ${SITE_URL}/datapost.php"
echo "║                                              ║"
echo "╠══════════════════════════════════════════════╣"
echo "║  NEXT STEPS:                                 ║"
echo "║  1. Open ${SITE_URL} in browser     ║"
echo "║  2. Login with admin credentials             ║"
echo "║  3. Create classes and add lessons           ║"
echo "║  4. Open courier app on phone for sync       ║"
echo "║  5. Set up Google Sheets webhook             ║"
echo "╚══════════════════════════════════════════════╝"
echo ""
echo "📋 To set up Google Sheets:"
echo "   1. Create a Google Sheet"
echo "   2. Extensions → Apps Script"
echo "   3. Paste the doPost/doGet code"
echo "   4. Deploy as Web App → Anyone"
echo "   5. Paste URL in courier app settings"
echo ""
echo "🔄 To connect to GitHub for remote updates:"
echo "   cd /var/www/html/mtti-lms"
echo "   sudo git remote add origin https://TOKEN@github.com/${GITHUB_REPO}.git"
echo "   sudo git push -u origin main"
