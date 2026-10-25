<?php
// enot_callback.php — Обработчик уведомления платежей Enot

$merchant_id = "ВАШ_MERCHANT_ID";
$secret_word = "ВАШ_СЕКРЕТНЫЙ_ПАРОЛЬ";

$merchant_id_req = $_POST['merchant'] ?? '';
$amount = $_POST['amount'] ?? '';
$merchant_enc = $_POST['merchant_enc'] ?? '';
$order_id = $_POST['merchant_order_id'] ?? '';
$sign = $_POST['sign_2'] ?? '';

// Проверка цифровой подписи (Security Check)
$sign_check = md5($merchant_id_req . ':' . $amount . ':' . $secret_word . ':' . $order_id);

if ($sign !== $sign_check) {
    die('ERROR: Invalid signature');
}

// Успешная оплата — тут сохраняем заказ в БД или обновляем статус
file_put_contents('payments_log.txt', date('Y-m-d H:i:s') . " - Оплачен заказ #{$order_id} на сумму {$amount}\n", FILE_APPEND);

echo 'YES';