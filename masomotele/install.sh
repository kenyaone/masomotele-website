#!/bin/bash
# M.T.T.I LMS v2 - Complete Installer (Ubuntu 24.04 LTS)
# Usage: sudo ./install.sh
set -e
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; RED='\033[0;31m'; NC='\033[0m'; BOLD='\033[1m'
ok() { echo -e "  ${GREEN}✅ $1${NC}"; }

DB_NAME="mtti_lms"; DB_USER="mtti_user"; DB_PASS='MttiLms2025!'
ADMIN_EMAIL="admin@mtti.ac.ke"; ADMIN_PHONE="0712464936"; ADMIN_PASS='Admin@2025'
SERVER_IP=$(hostname -I | awk '{print $1}')
LMS="/var/www/html/mtti-lms"
DIR="$(cd "$(dirname "$0")" && pwd)"

[ "$EUID" -ne 0 ] && echo -e "${RED}Run with: sudo ./install.sh${NC}" && exit 1

echo -e "\n${BOLD}${CYAN}══ M.T.T.I LMS v2 INSTALLER ══${NC}"
echo "  IP: $SERVER_IP | DB: $DB_NAME | Admin: $ADMIN_EMAIL / $ADMIN_PASS"
read -p "  Continue? (y/n): " -n1 -r; echo
[[ ! $REPLY =~ ^[Yy]$ ]] && exit 0

echo -e "\n${BOLD}[1/7] Removing Moodle${NC}"
rm -rf /var/www/html/moodle /var/moodledata /opt/moodle 2>/dev/null || true
mysql -e "DROP DATABASE IF EXISTS moodle;" 2>/dev/null || true
mysql -e "DROP USER IF EXISTS 'moodleuser'@'localhost';" 2>/dev/null || true
a2dissite moodle.conf 2>/dev/null || true; rm -f /etc/apache2/sites-available/moodle.conf 2>/dev/null || true
ok "Moodle removed"

echo -e "\n${BOLD}[2/7] Installing LAMP${NC}"
apt-get update -qq
apt-get install -y apache2 mariadb-server mariadb-client php php-mysql php-curl php-mbstring php-xml php-zip php-gd php-intl libapache2-mod-php unzip curl -qq 2>/dev/null
systemctl enable apache2 mariadb 2>/dev/null; systemctl start apache2 mariadb
ok "LAMP ready"

echo -e "\n${BOLD}[3/7] Configuring PHP${NC}"
PHP_INI=$(find /etc/php -name "php.ini" -path "*/apache2/*" 2>/dev/null | head -1)
if [ -f "$PHP_INI" ]; then
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' "$PHP_INI"
    sed -i 's/post_max_size = .*/post_max_size = 512M/' "$PHP_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    sed -i 's/memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
fi
a2enmod rewrite 2>/dev/null; a2ensite 000-default.conf 2>/dev/null; systemctl restart apache2
ok "PHP configured"

echo -e "\n${BOLD}[4/7] Creating Database${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "DROP USER IF EXISTS '$DB_USER'@'localhost';"
mysql -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"
mysql "$DB_NAME" < "$DIR/schema.sql"
ok "Database + 21 tables created"

# Create admin user
HASH=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")
mysql "$DB_NAME" -e "INSERT INTO users (name,email,phone,password,role,status,created_at) VALUES ('Admin','$ADMIN_EMAIL','$ADMIN_PHONE','$HASH','admin','active',NOW()) ON DUPLICATE KEY UPDATE name='Admin';"
ok "Admin account created"

echo -e "\n${BOLD}[5/7] Deploying LMS${NC}"
rm -rf "$LMS"
cp -R "$DIR" "$LMS"
# Write correct config
cat > "$LMS/config/app.php" << CONF
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME', 'M.T.T.I Learning Management System');
define('SITE_URL', 'http://$SERVER_IP/mtti-lms');
define('SITE_MOTTO', 'Start Learning, Start Earning');
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);
define('MAX_HTML_SIZE', 10 * 1024 * 1024);
define('ALLOWED_VIDEO', 'mp4,webm,ogg,avi,mkv');
define('ALLOWED_FILES', 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip');
define('ALLOWED_IMAGES', 'jpg,jpeg,png,gif,webp');
define('ALLOWED_HTML', 'html,htm');
define('SESSION_LIFETIME', 3600 * 8);
date_default_timezone_set('Africa/Nairobi');
CONF
ok "Config written with IP: $SERVER_IP"

echo -e "\n${BOLD}[6/7] Setting Permissions${NC}"
chown -R www-data:www-data "$LMS"
chmod -R 755 "$LMS"
chmod -R 775 "$LMS/assets/uploads"
ok "Permissions set"

echo -e "\n${BOLD}[7/7] Installing Ollama AI${NC}"
if command -v ollama &>/dev/null; then
    ok "Ollama already installed"
else
    curl -fsSL https://ollama.com/install.sh | sh 2>/dev/null && ok "Ollama installed" || echo -e "  ${YELLOW}Ollama install failed - install later${NC}"
fi
systemctl enable ollama 2>/dev/null || true; systemctl start ollama 2>/dev/null || true
sleep 2
if curl -s http://localhost:11434/ >/dev/null 2>&1; then
    ok "Ollama running"
    echo "  Downloading AI model (~2.3GB)..."
    ollama pull phi3:mini 2>/dev/null && ok "AI model ready" || echo -e "  ${YELLOW}Download failed - retry: ollama pull phi3:mini${NC}"
fi

echo -e "\n${GREEN}${BOLD}══════════════════════════════════════${NC}"
echo -e "${GREEN}${BOLD}  ✅ M.T.T.I LMS v2 INSTALLED!${NC}"
echo -e "${GREEN}${BOLD}══════════════════════════════════════${NC}"
echo ""
echo -e "  ${BOLD}URL:${NC}    http://$SERVER_IP/mtti-lms"
echo -e "  ${BOLD}Email:${NC}  $ADMIN_EMAIL"
echo -e "  ${BOLD}Pass:${NC}   $ADMIN_PASS"
echo -e "  ${BOLD}AI:${NC}     $(curl -s http://localhost:11434/ >/dev/null 2>&1 && echo 'Running ✅' || echo 'Not running')"
echo ""
