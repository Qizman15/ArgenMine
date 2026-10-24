<?php
// Общие функции: RCON (выдача на Minecraft-сервере), LiqPay (ПриватБанк), выдача заказа
function rcon($h,$port,$pass,$cmd){$s=@fsockopen($h,$port,$e,$m,3);if(!$s)return false;stream_set_timeout($s,3);
 $send=function($i,$t,$b)use($s){$x=pack('VV',$i,$t).$b."\0\0";fwrite($s,pack('V',strlen($x)).$x);};
 $read=function()use($s){$l=unpack('V',fread($s,4))[1];return substr(fread($s,$l),8,-2);};
 $send(1,3,$pass);$read();$send(2,2,$cmd);$r=$read();fclose($s);return $r;}
// Форма оплаты LiqPay: data = base64(json), signature = base64(sha1(private + data + private))
function liqpay_form($cfg,$p){$k=$cfg['liqpay']['private'];$p['public_key']=$cfg['liqpay']['public'];if(!empty($cfg['liqpay']['sandbox']))$p['sandbox']=1;
 $d=base64_encode(json_encode($p,JSON_UNESCAPED_UNICODE));
 return ['action'=>'https://www.liqpay.ua/api/3/checkout','data'=>$d,'signature'=>base64_encode(sha1($k.$d.$k,true))];}
// Выдать все оплаченные позиции заказа (batch) через RCON; если RCON недоступен — останутся «оплачен», выдать можно из админ-панели
function deliver($pdo,$cfg,$batch){$q=$pdo->prepare("SELECT o.id,o.nick,p.give_cmd FROM orders o JOIN products p ON p.id=o.product_id WHERE o.batch=? AND o.status='paid'");$q->execute([$batch]);
 foreach($q->fetchAll() as $o){$r=rcon($cfg['rcon']['host'],$cfg['rcon']['port'],$cfg['rcon']['pass'],str_replace('%username%',$o['nick'],$o['give_cmd']));
  if($r!==false)$pdo->prepare("UPDATE orders SET status='delivered' WHERE id=?")->execute([$o['id']]);}}
