#!/bin/bash
set -e
DB_NAME="mtti_lms"
LMS_DIR="/var/www/html/mtti-lms"
BACKUP_DIR="/home/tele/mtti-backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="mtti-lms-backup-${TIMESTAMP}"
WORK_DIR="/tmp/${BACKUP_NAME}"
GREEN='\033[0;32m'; NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }

[ "$EUID" -ne 0 ] && echo "Run as root: sudo bash mtti-backup.sh" && exit 1

echo "=================================================="
echo "  M.T.T.I LMS — Full Backup  $(date '+%d %B %Y %H:%M')"
echo "=================================================="

mkdir -p "$WORK_DIR" "$BACKUP_DIR"

log "Dumping database..."
mysqldump --single-transaction --routines --triggers "$DB_NAME" > "$WORK_DIR/database.sql"
log "Database: $(du -sh $WORK_DIR/database.sql | cut -f1)"

log "Copying LMS files (9GB — please wait)..."
cp -r "$LMS_DIR" "$WORK_DIR/mtti-lms"
log "Files copied: $(du -sh $WORK_DIR/mtti-lms | cut -f1)"

log "Saving system info..."
cat > "$WORK_DIR/system-info.txt" << INFO
BACKUP DATE: $(date)
OS: $(lsb_release -d | cut -f2)
PHP: $(php -v | head -1)
MYSQL: $(mysql --version)
LMS SIZE: $(du -sh $LMS_DIR | cut -f1)
INFO

cat > "$WORK_DIR/RESTORE.sh" << 'RESTORE'
#!/bin/bash
set -e
[ "$EUID" -ne 0 ] && echo "Run: sudo bash RESTORE.sh" && exit 1
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_NAME="mtti_lms"
DB_USER="tele"
echo "Enter DB password to set:"
read -s -p "Password: " DB_PASS; echo ""
echo "Updating system and installing packages..."
apt-get update -qq
apt-get install -y -qq apache2 php php-mysql php-gd php-curl php-mbstring php-xml php-zip libapache2-mod-php mysql-server ffmpeg
systemctl enable apache2 mysql
systemctl start apache2 mysql
echo "Configuring PHP..."
for ini in /etc/php/*/apache2/php.ini /etc/php/*/cli/php.ini; do
  [ -f "$ini" ] && sed -i 's/upload_max_filesize.*/upload_max_filesize = 512M/;s/post_max_size.*/post_max_size = 512M/;s/memory_limit.*/memory_limit = 256M/;s/max_execution_time.*/max_execution_time = 300/' "$ini"
done
echo "Setting up database..."
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"
echo "Importing database..."
mysql "$DB_NAME" < "$SCRIPT_DIR/database.sql"
echo "Copying LMS files..."
rm -rf /var/www/html/mtti-lms
cp -r "$SCRIPT_DIR/mtti-lms" /var/www/html/mtti-lms
echo "Updating DB password in config..."
find /var/www/html/mtti-lms/includes/ -name "*.php" | xargs sed -i "s/'password'.*=>.*'[^']*'/'password' => '$DB_PASS'/g" 2>/dev/null || true
find /var/www/html/mtti-lms/includes/ -name "*.php" | xargs sed -i "s/define('DB_PASS'.*/define('DB_PASS', '$DB_PASS');/" 2>/dev/null || true
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/mtti-lms
find /var/www/html/mtti-lms -type f -exec chmod 644 {} \;
find /var/www/html/mtti-lms -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/html/mtti-lms/assets/uploads
a2enmod rewrite headers
MY_IP=$(hostname -I | awk '{print $1}')
find /var/www/html/mtti-lms/includes/ -name "*.php" | xargs sed -i "s|define('SITE_URL'.*|define('SITE_URL', 'http://$MY_IP/mtti-lms');|" 2>/dev/null || true
cat > /etc/apache2/sites-available/mtti-lms.conf << ACONF
<VirtualHost *:80>
    DocumentRoot /var/www/html
    <Directory /var/www/html/mtti-lms>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
ACONF
a2ensite mtti-lms.conf; a2dissite 000-default.conf 2>/dev/null || true
systemctl restart apache2 mysql
echo ""
echo "=========================================="
echo "  RESTORE COMPLETE!"
echo "  Access: http://$MY_IP/mtti-lms/login.php"
echo "  Admin:  admin@mtti.ac.ke / password"
echo "  NOTE:   Verify DB password in includes/config.php"
echo "=========================================="
RESTORE

chmod +x "$WORK_DIR/RESTORE.sh"

log "Creating archive (may take 10-15 min for 9GB)..."
cd /tmp
tar -czf "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" "$BACKUP_NAME"
rm -rf "$WORK_DIR"

ls -t "${BACKUP_DIR}"/mtti-lms-backup-*.tar.gz 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true

BACKUP_SIZE=$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)
echo ""
echo "=================================================="
echo -e "${GREEN}  BACKUP COMPLETE!${NC}"
echo "  File: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
echo "  Size: ${BACKUP_SIZE}"
echo ""
echo "  Copy to Desktop:"
echo "  cp ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz ~/Desktop/"
echo "=================================================="
