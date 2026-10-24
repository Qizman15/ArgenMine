<?php
return [
 'db'=>['host'=>'127.0.0.1','name'=>'argenmine','user'=>'argen','pass'=>'CHANGE_ME'],
 'rcon'=>['host'=>'127.0.0.1','port'=>25575,'pass'=>'CHANGE_ME'],
 'mc'=>['host'=>'127.0.0.1','port'=>25565],
 // LiqPay (ПриватБанк): ключи из кабинета liqpay.ua. Только здесь, на сервере — никогда в index.html и не в чатах.
 'liqpay'=>['public'=>'','private'=>'','sandbox'=>true], // sandbox=true — тестовые платежи; для настоящих денег поставьте false и боевые ключи
 'rub_to_uah'=>0.45, // ПРОВЕРЬТЕ: курс пересчёта цен из ₽ в ₴ при оплате картой
 'site_url'=>'https://argenmine.net', // ваш домен с HTTPS (нужен для callback LiqPay)
 'mail'=>['from'=>'no-reply@argenmine.net','name'=>'ArgenMine','host'=>'smtp.example.com','port'=>587,'user'=>'','pass'=>''],
 'turnstile_secret'=>''
];
