# Cloudflare-защита для ArgenMine
Я не могу подключить Cloudflare к вашему аккаунту сам — это делается в вашем кабинете. Шаги:
1. cloudflare.com → Add a site → домен → бесплатный план. Смените NS у регистратора домена на выданные Cloudflare.
2. DNS: A-запись `@` и `www` на IP VPS, облачко **оранжевое (Proxied)**.
3. SSL/TLS → режим **Full (strict)** (на VPS нужен сертификат: Let's Encrypt или Cloudflare Origin Certificate). Включите Always Use HTTPS.
4. Security → Settings: Security Level **Medium**, включите Bot Fight Mode.
5. Security → WAF → Rate limiting: правило для `/php/api.php` — например 20 запросов/минуту с одного IP → Block/Challenge.
6. **Turnstile** (капча для входа/регистрации/сброса): Turnstile → Add site → получите Site key и Secret key.
   Site key впишите в index.html: `CFG.TURNSTILE='...'`; Secret key — в php/config.php: `turnstile_secret`.
7. Скройте VPS от прямых атак: `sudo bash docs/cf-firewall.sh` — открывает 80/443 только для IP Cloudflare.
8. Реальные IP посетителей в nginx: `include /etc/nginx/cloudflare.conf;` (файл создаёт cf-firewall.sh).
9. Minecraft-сервер (порт 25565) через бесплатный Cloudflare не проксируется — для защиты игрового порта нужен отдельный анти-DDoS (хостинг/туннель).
10. Для оплаты: правило WAF Skip на путь `/php/liqpay.php` (callback от LiqPay не должен попадать под Bot Fight Mode/капчу).
