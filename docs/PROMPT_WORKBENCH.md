# Промт для MySQL Workbench / ИИ-помощника
Создай базу MySQL 8 `argenmine` (utf8mb4) для Minecraft донат-магазина и форума на русском языке.
Таблицы: users(nick, email, pass_hash, role: player/moderator/admin/main_admin/creator), password_resets(token_hash, expires, used),
products(id, name, category, price, image, commands JSON, bonuses JSON, give_cmd, take_cmd), orders(nick, product_id, amount, status pending/paid/delivered/failed),
topics, chat, friends, scores. Донаты: GRIEFER 19, GHAST 698, KRAKEN 629, WITHER 649, DRAGON 779, STINGER 1024, ETERNITY 1788, DEMON 2430, D. ADMIN 5349 (руб.).
Выдача: `lp user %username% parent add <группа>`, снятие: `lp user %username% parent remove <группа>`.
Создатель — Qizman (creator), главный администратор — FOGR (main_admin). Добавь индексы и внешние ключи.

# Как подключить (пошагово)
1. Workbench → «+» рядом с MySQL Connections → Hostname = IP VPS (или 127.0.0.1 через SSH-туннель), Port 3306, User = ваш → Test Connection.
2. File → Open SQL Script → `sql/argenmine_workbench.sql` → молния (Execute).
3. Создайте отдельного пользователя вместо root: 
   `CREATE USER 'argen'@'localhost' IDENTIFIED BY 'СЛОЖНЫЙ_ПАРОЛЬ'; GRANT SELECT,INSERT,UPDATE,DELETE ON argenmine.* TO 'argen'@'localhost';`
4. Впишите данные в `php/config.php`. Порт 3306 наружу не открывайте — подключайтесь к VPS по SSH-туннелю (Workbench: Standard TCP/IP over SSH).
