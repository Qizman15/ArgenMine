CREATE DATABASE IF NOT EXISTS argenmine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE argenmine;

CREATE TABLE IF NOT EXISTS users(
  id INT AUTO_INCREMENT PRIMARY KEY,
  nick VARCHAR(16) UNIQUE NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  pass_hash VARCHAR(255) NOT NULL,
  role ENUM('player','admin','owner') DEFAULT 'player',
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires DATETIME NOT NULL,
  used TINYINT DEFAULT 0,
  INDEX(token_hash),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products(
  id VARCHAR(32) PRIMARY KEY,
  name VARCHAR(64),
  price INT,
  category VARCHAR(64),
  image VARCHAR(255),
  commands JSON,
  bonuses JSON,
  give_cmd VARCHAR(255),
  take_cmd VARCHAR(255),
  active TINYINT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS orders(
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch VARCHAR(32) NOT NULL,
  nick VARCHAR(16) NOT NULL,
  product_id VARCHAR(32) NOT NULL,
  amount INT NOT NULL,
  status ENUM('pending','paid','delivered','failed') DEFAULT 'pending',
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(batch),
  FOREIGN KEY(product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS topics(
  id INT AUTO_INCREMENT PRIMARY KEY,
  section VARCHAR(16),
  title VARCHAR(80),
  body TEXT,
  user_id INT,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS chat(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  msg VARCHAR(200),
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS friends(
  user_id INT,
  friend_id INT,
  PRIMARY KEY(user_id,friend_id)
);

CREATE TABLE IF NOT EXISTS scores(
  user_id INT,
  game VARCHAR(16),
  best INT,
  PRIMARY KEY(user_id,game)
);

INSERT IGNORE INTO products(id,name,price,category,image,commands,bonuses,give_cmd,take_cmd) VALUES
('griefer','griefer',19,'Обычные донаты','img/griefer.jpg','["Посмотреть текущее время - /time", "Открыть ручной верстак - /workbench", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Ваш префикс - %player_name%", "Вам доступно 2 точки дома", "Вы можете заприватить 2 региона", "Вы можете выставить на рынок 2 предмета"]','lp user %username% parent add griefer','lp user %username% parent remove griefer'),
('ghast','GHAST',698,'Обычные донаты','img/ghast.jpg','["Покушать - /feed", "Получить голову игрока - /skull ник", "Игнорировать игрока - /ignore ник", "Открыть ручной эндер-сундук - /ec", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вам доступно 3 точки дома", "Вы можете заприватить 3 региона", "Вы можете выставить на рынок 5 предметов"]','lp user %username% parent add ghast','lp user %username% parent remove ghast'),
('kraken','KRAKEN',658,'Обычные донаты','img/kraken.jpg','["Установить личное время - /ptime", "Надеть предмет на голову - /hat", "Посмотреть баланс другого игрока - /balance ник", "Узнать информацию о предмете - /itemdb", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс KRAKEN %player_name%", "Вам доступно 5 точек дома", "Вы можете заприватить 5 регионов", "Вы можете выставить на рынок 8 предметов"]','lp user %username% parent add kraken','lp user %username% parent remove kraken'),
('wither','WITHER',699,'Обычные донаты','img/wither.jpg','["Посмотреть чужой инвентарь - /invsee ник", "Посмотреть игроков поблизости - /near", "Включить режим шпиона - /socialspy", "Запустить фейерверк - /firework", "Установить ясно - /sun", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вам доступно 7 точек дома", "Вы можете заприватить 7 регионов", "Вы можете выставить на рынок 10 предметов"]','lp user %username% parent add wither','lp user %username% parent remove wither'),
('dragon','DRAGON',779,'Обычные донаты','img/dragon.jpg','["Посмотреть чужой инвентарь с броней - /invsee ник armour", "Узнать реальный ник - /realname ник", "Установить день - /day", "Установить ночь - /night", "Установить ясно - /sun", "Писать объявления - /bc текст", "Установить варп - /setwarp", "Удалить свой варп - /delwarp", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вы можете создать 5 варпов", "Вам доступно 10 точек дома", "Вы можете заприватить 10 регионов", "Вы можете выставить на рынок 15 предметов"]','lp user %username% parent add dragon','lp user %username% parent remove dragon'),
('stinger','STINGER',1024,'Обычные донаты','img/stinger.jpg','["Выдать донат другому игроку - /grand give ник донат", "GRIEFER (2ШТ.), GHAST (1ШТ.), KRAKEN (1ШТ.)", "Телепортироваться около игроков - /rtp near", "Установить фейковый ник - /nick ник", "Починить вещь - /repair", "Запретить телепортацию к себе - /tptoggle", "Накормить игрока - /feed ник", "Установить варп - /setwarp", "Удалить свой варп - /delwarp", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вы можете создать 3 варпа", "Вам доступно 15 точек дома", "Вы можете заприватить 15 регионов", "Вы можете выставить на рынок 25 предметов"]','lp user %username% parent add stinger','lp user %username% parent remove stinger'),
('eternity','ETERNITY',1788,'Обычные донаты','img/eternity.jpg','["Выдать донат другому игроку - /grand give ник донат", "KRAKEN (2ШТ.), WITHER (1ШТ.), DRAGON (1ШТ.)", "Починить вещь (с зачарованием) - /repair", "Починить все вещи в инвентаре - /repair all", "Отключить входящие сообщения - /msgtoggle", "Исцелиться - /heal", "Поджечь игрока - /burn ник", "Установить варп - /setwarp", "Удалить свой варп - /delwarp", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вы можете создать 5 варпов", "Вам доступно 20 точек дома", "Вы можете заприватить 20 регионов", "Вы можете выставить на рынок 25 предметов"]','lp user %username% parent add eternity','lp user %username% parent remove eternity'),
('demon','DEMON',2430,'Обычные донаты','img/demon.jpg','["Выдать донат другому игроку - /grand give ник донат", "WITHER (2ШТ.), DRAGON (1ШТ.), STINGER (1ШТ.)", "Режим полёта - /fly", "Режим бога - /god", "Починить все вещи в инвентаре - /repair all (с зачарованиями)", "Исцелить игрока - /heal ник", "Кикнуть игрока - /kick ник", "Установить варп - /setwarp", "Удалить свой варп - /delwarp", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вы можете создать 10 варпов", "Вам доступно 50 точек дома", "Вы можете заприватить 50 регионов", "Вы можете выставить на рынок 40 предметов"]','lp user %username% parent add demon','lp user %username% parent remove demon'),
('dadmin','D. ADMIN',5349,'Обычные донаты','img/dadmin.jpg','["Выдать донат другому игроку - /grand give ник донат", "WITHER (3ШТ.), DRAGON (2ШТ.), STINGER (1ШТ.), ETERNITY (1ШТ.)", "Режим невидимки в любом мире - /vanish", "Телепортировать игрока на спавн - /spawn ник", "Кикнуть игрока - /kick ник", "Установить варп - /setwarp", "Удалить свой варп - /delwarp", "Ежедневный бонус - /salary", "Личный набор - /kits"]','["Уникальный префикс %player_name%", "Вы можете создать 215 варпов", "Вам доступно 250 точек дома", "Вы можете заприватить 260 регионов", "Вы можете выставить на рынок 250 предметов"]','lp user %username% parent add dadmin','lp user %username% parent remove dadmin');

-- После регистрации Qizman и FOGR выдайте роли:
-- UPDATE users SET role='*' WHERE nick='Qizman';
-- UPDATE users SET role='Создатель' WHERE nick='FOGR';

-- code --install-extension Oracle.mysql-shell-for-vs-code --
