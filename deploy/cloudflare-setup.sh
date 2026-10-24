#!/bin/bash
# Запускать на VPS от root: оставляет доступ к 80/443 только с серверов Cloudflare и включает реальный IP посетителей в nginx.
set -e
ufw allow OpenSSH
echo "limit_req_zone \$binary_remote_addr zone=auth:10m rate=10r/m;" > /etc/nginx/conf.d/ratelimit.conf
: > /etc/nginx/conf.d/cloudflare-realip.conf
for f in ips-v4 ips-v6; do
  for ip in $(curl -s https://www.cloudflare.com/$f); do
    echo "set_real_ip_from $ip;" >> /etc/nginx/conf.d/cloudflare-realip.conf
    ufw allow proto tcp from $ip to any port 80,443
  done
done
echo "real_ip_header CF-Connecting-IP;" >> /etc/nginx/conf.d/cloudflare-realip.conf
ufw --force enable && nginx -t && systemctl reload nginx && echo "Готово"
