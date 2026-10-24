#!/bin/bash
# Обновляет список IP Cloudflare, закрывает 80/443 для всех остальных и пишет nginx real_ip конфиг
set -e
V4=$(curl -fsS https://www.cloudflare.com/ips-v4); V6=$(curl -fsS https://www.cloudflare.com/ips-v6)
{ for ip in $V4 $V6; do echo "set_real_ip_from $ip;"; done; echo "real_ip_header CF-Connecting-IP;"; } > /etc/nginx/cloudflare.conf
ufw delete allow 'Nginx Full' 2>/dev/null || true
for ip in $V4 $V6; do ufw allow from $ip to any port 80,443 proto tcp; done
ufw allow OpenSSH; ufw --force enable; nginx -t && systemctl reload nginx
