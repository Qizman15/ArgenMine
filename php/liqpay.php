<?php
// Callback LiqPay (server_url). LiqPay присылает POST data+signature; подпись проверяем сами.
$cfg=require __DIR__.'/config.php';$d=$cfg['db'];
$pdo=new PDO("mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
require __DIR__.'/lib.php';
$data=$_POST['data']??'';$sig=$_POST['signature']??'';$k=$cfg['liqpay']['private'];
if($data===''||$k===''||!hash_equals(base64_encode(sha1($k.$data.$k,true)),$sig)){http_response_code(403);exit('bad signature');}
$j=json_decode(base64_decode($data),true);if(!is_array($j)){http_response_code(400);exit('bad data');}
$oid=(string)($j['order_id']??'');$st=(string)($j['status']??'');
$ok=$st==='success'||($st==='sandbox'&&!empty($cfg['liqpay']['sandbox'])); // 'sandbox' — тестовая оплата, принимаем только в тестовом режиме
$fail=in_array($st,['failure','error','reversed'],true);
$kop=(int)round(((float)($j['amount']??0))*100);$okCur=(($j['currency']??'')==='UAH');
if($oid!==''&&$oid[0]==='T'){ // пополнение баланса
 $t=$pdo->prepare("SELECT * FROM topups WHERE id=?");$t->execute([(int)substr($oid,1)]);$t=$t->fetch();
 if($t&&$t['status']==='pending'){
  if($ok){if(!$okCur||$kop!==(int)$t['amount_kop']){error_log("ArgenMine: сумма/валюта не совпали, topup {$t['id']}");exit('OK');}
   $pdo->beginTransaction();$u=$pdo->prepare("UPDATE topups SET status='paid' WHERE id=? AND status='pending'");$u->execute([$t['id']]);
   if($u->rowCount())$pdo->prepare("UPDATE users SET balance=balance+? WHERE id=?")->execute([$t['amount_rub'],$t['user_id']]);
   $pdo->commit();}
  elseif($fail)$pdo->prepare("UPDATE topups SET status='failed' WHERE id=? AND status='pending'")->execute([$t['id']]);}
}elseif($oid!==''&&$oid[0]==='O'){ // оплата заказа картой
 $batch=substr($oid,1);$r=$pdo->prepare("SELECT batch_kop FROM orders WHERE batch=? LIMIT 1");$r->execute([$batch]);$r=$r->fetch();
 if($r){
  if($ok){if(!$okCur||$kop!==(int)$r['batch_kop']){error_log("ArgenMine: сумма/валюта не совпали, order $batch");exit('OK');}
   $u=$pdo->prepare("UPDATE orders SET status='paid' WHERE batch=? AND status='pending'");$u->execute([$batch]);
   if($u->rowCount())deliver($pdo,$cfg,$batch);} // повторный callback ничего не выдаст второй раз
  elseif($fail)$pdo->prepare("UPDATE orders SET status='failed' WHERE batch=? AND status='pending'")->execute([$batch]);}}
echo 'OK';
