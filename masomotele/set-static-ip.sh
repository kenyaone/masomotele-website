#!/bin/bash
# Set Static IP for M.T.T.I LMS Server
# Usage: sudo ./set-static-ip.sh
RED='\033[0;31m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'; BOLD='\033[1m'
[ "$EUID" -ne 0 ] && echo -e "${RED}Run with: sudo ./set-static-ip.sh${NC}" && exit 1

# Detect current settings
IFACE=$(ip route | grep default | awk '{print $5}' | head -1)
CURRENT_IP=$(hostname -I | awk '{print $1}')
GATEWAY=$(ip route | grep default | awk '{print $3}' | head -1)
DNS="8.8.8.8,8.8.4.4"

echo -e "${BOLD}${CYAN}══ Set Static IP for M.T.T.I LMS ══${NC}"
echo -e "  Interface: ${BOLD}$IFACE${NC}"
echo -e "  Current IP: ${BOLD}$CURRENT_IP${NC}"
echo -e "  Gateway: ${BOLD}$GATEWAY${NC}"
echo ""
read -p "  Static IP to set [$CURRENT_IP]: " STATIC_IP
STATIC_IP=${STATIC_IP:-$CURRENT_IP}
read -p "  Gateway [$GATEWAY]: " GW
GW=${GW:-$GATEWAY}

# Create netplan config
NETPLAN_FILE="/etc/netplan/01-static-ip.yaml"
cat > "$NETPLAN_FILE" << EOF
network:
  version: 2
  renderer: networkd
  ethernets:
    $IFACE:
      dhcp4: false
      addresses:
        - ${STATIC_IP}/24
      routes:
        - to: default
          via: ${GW}
      nameservers:
        addresses: [8.8.8.8, 8.8.4.4, 1.1.1.1]
EOF

chmod 600 "$NETPLAN_FILE"

# Remove other netplan configs that might conflict
for f in /etc/netplan/*.yaml; do
    if [ "$f" != "$NETPLAN_FILE" ]; then
        mv "$f" "${f}.backup" 2>/dev/null
        echo -e "  Backed up: $(basename $f)"
    fi
done

echo ""
echo -e "  ${GREEN}Config written to $NETPLAN_FILE${NC}"
echo -e "  Applying..."
netplan apply 2>/dev/null

# Update LMS config with the static IP
LMS_CONFIG="/var/www/html/mtti-lms/config/app.php"
if [ -f "$LMS_CONFIG" ]; then
    sed -i "s|define('SITE_URL', 'http://[^']*');|define('SITE_URL', 'http://${STATIC_IP}/mtti-lms');|" "$LMS_CONFIG"
    echo -e "  ${GREEN}LMS config updated to: http://${STATIC_IP}/mtti-lms${NC}"
fi

echo ""
echo -e "${GREEN}${BOLD}✅ Static IP set to: $STATIC_IP${NC}"
echo -e "  LMS URL: ${BOLD}http://${STATIC_IP}/mtti-lms${NC}"
echo ""
echo -e "  ${CYAN}Other PCs on the same network can access the LMS by opening:"
echo -e "  ${BOLD}http://${STATIC_IP}/mtti-lms${NC} in any browser"
echo ""
echo -e "  ${CYAN}To connect student PCs:${NC}"
echo -e "  1. Connect them to the same WiFi/LAN"
echo -e "  2. Open browser and go to http://${STATIC_IP}/mtti-lms"
echo -e "  3. Click Register to create a student account"
echo ""
