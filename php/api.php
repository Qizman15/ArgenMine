<?php
session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Lax']); session_start();
header('Content-Type: application/json; charset=utf-8');
$cfg=require __DIR__.'/config.php'; $d=$cfg['db'];
$pdo=new PDO("mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$in=json_decode(file_get_contents('php://input'),true)??[];
function out($x,$c=200){http_response_code($c);echo json_encode($x,JSON_UNESCAPED_UNICODE);exit;}
function me(){return $_SESSION['uid']??null;} function need(){if(!me())out(['error'=>'Нужен вход'],401);}
function q($s,$p=[]){global $pdo;$st=$pdo->prepare($s);$st->execute($p);return $st;}
function turnstile($tok){global $cfg;$sec=$cfg['turnstile_secret'];if(!$sec)return true;
 $ip=$_SERVER['HTTP_CF_CONNECTING_IP']??$_SERVER['REMOTE_ADDR'];
 $c=curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
 curl_setopt_array($c,[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>5,CURLOPT_POSTFIELDS=>['secret'=>$sec,'response'=>$tok,'remoteip'=>$ip]]);
 $r=json_decode(curl_exec($c),true);return !empty($r['success']);}
function sendmail($to,$subj,$html){global $cfg;$m=$cfg['mail'];
 if(class_exists('PHPMailer\PHPMailer\PHPMailer')){$x=new PHPMailer\PHPMailer\PHPMailer(true);$x->isSMTP();$x->Host=$m['host'];$x->Port=$m['port'];$x->SMTPAuth=true;$x->Username=$m['user'];$x->Password=$m['pass'];$x->SMTPSecure='tls';$x->CharSet='UTF-8';
  $x->setFrom($m['from'],$m['name']);$x->addAddress($to);$x->isHTML(true);$x->Subject=$subj;$x->Body=$html;return $x->send();}
 return mail($to,'=?UTF-8?B?'.base64_encode($subj).'?=',$html,"MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: {$m['name']} <{$m['from']}>");}
require __DIR__.'/lib.php';
if(file_exists(__DIR__.'/../vendor/autoload.php'))require __DIR__.'/../vendor/autoload.php';
function admin(){global $pdo;need();$r=q("SELECT role FROM users WHERE id=?",[me()])->fetch();if(!$r||!in_array($r['role'],['creator','main_admin']))out(['error'=>'Нет доступа'],403);}
$a=$_GET['a']??'';if(strpos($a,'admin_')===0)admin();
if(in_array($a,['register','login','forgot','reset'])&&!turnstile($in['cf']??''))out(['error'=>'Проверка Cloudflare не пройдена'],403);
switch($a){
case 'register':
 $n=$in['nick']??'';$e=strtolower(trim($in['email']??''));$p=$in['pass']??'';
 if(!preg_match('/^[A-Za-z0-9_]{3,16}$/',$n)||!filter_var($e,FILTER_VALIDATE_EMAIL)||strlen($p)<6)out(['error'=>'Неверные данные'],400);
 if(q("SELECT 1 FROM users WHERE nick=? OR email=?",[$n,$e])->fetch())out(['error'=>'Ник или e-mail уже заняты'],409);
 q("INSERT INTO users(nick,email,pass_hash) VALUES(?,?,?)",[$n,$e,password_hash($p,PASSWORD_DEFAULT)]);
 session_regenerate_id(true);$_SESSION['uid']=$pdo->lastInsertId();out(['nick'=>$n]);
case 'login':
 $l=trim($in['login']??'');$u=q("SELECT * FROM users WHERE nick=? OR email=?",[$l,strtolower($l)])->fetch();
 if(!$u||!password_verify($in['pass']??'',$u['pass_hash'])){usleep(500000);out(['error'=>'Неверный ник или пароль'],401);}
 session_regenerate_id(true);$_SESSION['uid']=$u['id'];out(['nick'=>$u['nick'],'role'=>$u['role'],'balance'=>(int)$u['balance']]);
case 'logout':session_destroy();out(['ok'=>1]);
case 'forgot': // всегда одинаковый ответ, чтобы не раскрывать, какие e-mail есть в базе
 $u=q("SELECT id,nick FROM users WHERE email=?",[strtolower(trim($in['email']??''))])->fetch();
 if($u){$t=bin2hex(random_bytes(32));
  q("INSERT INTO password_resets(user_id,token_hash,expires) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 1 HOUR))",[$u['id'],hash('sha256',$t)]);
  $link=$cfg['site_url'].'/#/reset/'.$t;
  sendmail(strtolower($in['email']),'Восстановление пароля ArgenMine',"<p>Здравствуйте, {$u['nick']}!</p><p>Чтобы задать новый пароль, перейдите по ссылке (действует 1 час):</p><p><a href=\"$link\">$link</a></p><p>Если вы не запрашивали сброс — просто проигнорируйте это письмо.</p>");}
 out(['ok'=>1]);
case 'reset':
 $p=$in['pass']??'';if(strlen($p)<6)out(['error'=>'Пароль слишком короткий'],400);
 $r=q("SELECT * FROM password_resets WHERE token_hash=? AND used=0 AND expires>NOW()",[hash('sha256',$in['token']??'')])->fetch();
 if(!$r)out(['error'=>'Ссылка недействительна или устарела'],400);
 q("UPDATE users SET pass_hash=? WHERE id=?",[password_hash($p,PASSWORD_DEFAULT),$r['user_id']]);q("UPDATE password_resets SET used=1 WHERE id=?",[$r['id']]);out(['ok'=>1]);
case 'me':need();out(q("SELECT nick,role,balance FROM users WHERE id=?",[me()])->fetch());
case 'topup':need();$r=(int)($in['amount']??0);if($r<10||$r>50000)out(['error'=>'Сумма от 10 до 50 000 ₽'],400);
 if(empty($cfg['liqpay']['public'])||empty($cfg['liqpay']['private']))out(['error'=>'Оплата ещё не настроена'],503);
 $kop=(int)round($r*$cfg['rub_to_uah']*100);if($kop<100)out(['error'=>'Сумма слишком мала'],400);
 q("INSERT INTO topups(user_id,amount_rub,amount_kop) VALUES(?,?,?)",[me(),$r,$kop]);$tid=$pdo->lastInsertId();
 out(liqpay_form($cfg,['version'=>3,'action'=>'pay','amount'=>$kop/100,'currency'=>'UAH','description'=>'Пополнение баланса ArgenMine','order_id'=>'T'.$tid,'language'=>'ru','result_url'=>$cfg['site_url'].'/#/balance','server_url'=>$cfg['site_url'].'/php/liqpay.php']));
case 'products':out(q("SELECT id,name,price,image FROM products WHERE active=1")->fetchAll());
case 'recent':out(q("SELECT o.nick,p.name,DATE_FORMAT(o.created,'%d.%m %H:%i') created FROM orders o JOIN products p ON p.id=o.product_id WHERE o.status IN('paid','delivered') ORDER BY o.created DESC LIMIT 10")->fetchAll());
case 'promo':$c=q("SELECT percent FROM promo_codes WHERE code=? AND active=1",[strtoupper($in['code']??'')])->fetch();if(!$c)out(['error'=>'Промокод не найден'],404);out(['percent'=>(int)$c['percent']]);
case 'online':$m=$cfg['mc'];$s=@fsockopen($m['host'],$m['port'],$e,$er,2);if(!$s)out(['online'=>0,'max'=>0,'up'=>false]);stream_set_timeout($s,2);
 $vi=function($n){$o='';do{$b=$n&0x7F;$n>>=7;if($n)$b|=0x80;$o.=chr($b);}while($n);return $o;};
 $rv=function()use($s){$n=0;$i=0;do{$b=ord(fread($s,1));$n|=($b&0x7F)<<(7*$i++);}while($b&0x80&&$i<6);return $n;};
 $pk="\x00".$vi(0xFFFFFFFF).$vi(strlen($m['host'])).$m['host'].pack('n',$m['port']).$vi(1);fwrite($s,$vi(strlen($pk)).$pk."\x01\x00");
 $rv();$rv();$len=$rv();$j='';while(strlen($j)<$len&&!feof($s)){$c=fread($s,$len-strlen($j));if($c===''||$c===false)break;$j.=$c;}fclose($s);
 $r=json_decode($j,true);out(['online'=>(int)($r['players']['online']??0),'max'=>(int)($r['players']['max']??0),'up'=>true]);
case 'player': // заходил ли игрок на сервер (через RCON + LuckPerms)
 $n=$in['nick']??'';if(!preg_match('/^[A-Za-z0-9_]{3,16}$/',$n))out(['error'=>'Неверный ник'],400);
 $r=rcon($cfg['rcon']['host'],$cfg['rcon']['port'],$cfg['rcon']['pass'],"lp user $n info");
 out(['seen'=>$r===false?true:stripos($r,'could not be found')===false]);
case 'order':
 $n=$in['nick']??'';if(!preg_match('/^[A-Za-z0-9_]{3,16}$/',$n))out(['error'=>'Неверный ник'],400);
 $e=trim($in['email']??'');if(!filter_var($e,FILTER_VALIDATE_EMAIL))out(['error'=>'Неверный e-mail'],400);
 $c=q("SELECT percent FROM promo_codes WHERE code=? AND active=1",[strtoupper($in['promo']??'')])->fetch();$pct=(int)($c['percent']??0);
 $ids=array_values(array_unique((array)($in['items']??[])));if(!$ids)out(['error'=>'Корзина пуста'],400);
 $rows=[];$sum=0;foreach($ids as $id){$p=q("SELECT id,price FROM products WHERE id=? AND active=1",[$id])->fetch();if(!$p)out(['error'=>'Товар не найден'],404);
  $p['amt']=(int)round($p['price']*(100-$pct)/100);$sum+=$p['amt'];$rows[]=$p;} // цена считается на сервере, а не берётся из браузера
 $batch=bin2hex(random_bytes(8));$ins="INSERT INTO orders(nick,email,product_id,amount,method,promo,batch,batch_kop,status) VALUES(?,?,?,?,?,?,?,?,?)";
 $promo=$pct?strtoupper($in['promo']):null;
 if(($in['method']??'')==='balance'){need();
  $pdo->beginTransaction();$b=(int)q("SELECT balance FROM users WHERE id=? FOR UPDATE",[me()])->fetch()['balance'];
  if($b<$sum){$pdo->rollBack();out(['error'=>'Недостаточно средств на балансе'],402);}
  q("UPDATE users SET balance=balance-? WHERE id=?",[$sum,me()]);
  foreach($rows as $p)q($ins,[$n,$e,$p['id'],$p['amt'],'balance',$promo,$batch,0,'paid']);
  $pdo->commit();deliver($pdo,$cfg,$batch);out(['ok'=>1,'balance'=>$b-$sum]);}
 if(empty($cfg['liqpay']['public'])||empty($cfg['liqpay']['private']))out(['error'=>'Оплата ещё не настроена'],503);
 $kop=(int)round($sum*$cfg['rub_to_uah']*100);if($kop<100)out(['error'=>'Сумма слишком мала'],400);
 foreach($rows as $p)q($ins,[$n,$e,$p['id'],$p['amt'],'card',$promo,$batch,$kop,'pending']);
 out(liqpay_form($cfg,['version'=>3,'action'=>'pay','amount'=>$kop/100,'currency'=>'UAH','description'=>'Донат ArgenMine, ник '.$n,'order_id'=>'O'.$batch,'language'=>'ru','result_url'=>$cfg['site_url'].'/#/paid','server_url'=>$cfg['site_url'].'/php/liqpay.php']));
case 'users':out(q("SELECT nick,role FROM users ORDER BY id DESC LIMIT 200")->fetchAll());
case 'staff':out(q("SELECT nick,role FROM users WHERE role IN('creator','main_admin') ORDER BY FIELD(role,'creator','main_admin')")->fetchAll());
case 'topics':out(q("SELECT t.id,t.title,t.body,t.created,u.nick FROM topics t JOIN users u ON u.id=t.user_id WHERE t.section=? ORDER BY t.id DESC LIMIT 100",[$_GET['s']??''])->fetchAll());
case 'topic_new':need();$t=trim($in['title']??'');if($t==='')out(['error'=>'Пустое название'],400);
 q("INSERT INTO topics(section,title,body,user_id) VALUES(?,?,?,?)",[substr($in['section']??'',0,16),mb_substr($t,0,80),mb_substr($in['body']??'',0,5000),me()]);out(['ok'=>1]);
case 'chat':out(array_reverse(q("SELECT c.msg,u.nick FROM chat c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC LIMIT 50")->fetchAll()));
case 'chat_send':need();$m=trim($in['msg']??'');if($m==='')out(['error'=>'Пусто'],400);q("INSERT INTO chat(user_id,msg) VALUES(?,?)",[me(),mb_substr($m,0,200)]);out(['ok'=>1]);
case 'friends':need();out(q("SELECT u.nick FROM friends f JOIN users u ON u.id=f.friend_id WHERE f.user_id=?",[me()])->fetchAll());
case 'friend_add':need();$f=q("SELECT id FROM users WHERE nick=?",[$in['nick']??''])->fetch();if(!$f||$f['id']==me())out(['error'=>'Игрок не найден'],404);q("INSERT IGNORE INTO friends VALUES(?,?)",[me(),$f['id']]);out(['ok'=>1]);
case 'search':$k='%'.str_replace(['%','_'],['\%','\_'],$_GET['q']??'').'%';out(q("SELECT t.title,t.body,u.nick FROM topics t JOIN users u ON u.id=t.user_id WHERE t.title LIKE ? OR t.body LIKE ? OR u.nick LIKE ? LIMIT 50",[$k,$k,$k])->fetchAll());
case 'admin_stats':out(['users'=>(int)q("SELECT COUNT(*) c FROM users")->fetch()['c'],'orders'=>(int)q("SELECT COUNT(*) c FROM orders")->fetch()['c'],'revenue'=>(int)q("SELECT COALESCE(SUM(amount),0) c FROM orders WHERE status IN('paid','delivered')")->fetch()['c'],'topics'=>(int)q("SELECT COUNT(*) c FROM topics")->fetch()['c'],'msgs'=>(int)q("SELECT COUNT(*) c FROM chat")->fetch()['c']]);
case 'admin_orders':out(q("SELECT o.id,o.nick,p.name product,o.amount,o.status,o.method,DATE_FORMAT(o.created,'%d.%m %H:%i') created FROM orders o JOIN products p ON p.id=o.product_id ORDER BY o.id DESC LIMIT 200")->fetchAll());
case 'admin_order_status':if(!in_array($in['status']??'',['pending','paid','delivered','failed']))out(['error'=>'Неверный статус'],400);q("UPDATE orders SET status=? WHERE id=?",[$in['status'],(int)$in['id']]);out(['ok'=>1]);
case 'admin_redeliver':$o=q("SELECT o.nick,p.give_cmd FROM orders o JOIN products p ON p.id=o.product_id WHERE o.id=? AND o.status='paid'",[(int)($in['id']??0)])->fetch();if(!$o)out(['error'=>'Заказ не найден'],404);
 if(rcon($cfg['rcon']['host'],$cfg['rcon']['port'],$cfg['rcon']['pass'],str_replace('%username%',$o['nick'],$o['give_cmd']))===false)out(['error'=>'RCON недоступен'],502);q("UPDATE orders SET status='delivered' WHERE id=?",[(int)$in['id']]);out(['ok'=>1]);
case 'admin_products':out(q("SELECT id,name,price,active FROM products")->fetchAll());
case 'admin_product_save':if((int)($in['price']??0)<1)out(['error'=>'Неверная цена'],400);q("UPDATE products SET price=?,active=? WHERE id=?",[(int)$in['price'],empty($in['active'])?0:1,$in['id']??'']);out(['ok'=>1]);
case 'admin_users':out(q("SELECT nick,email,role,balance FROM users ORDER BY id DESC LIMIT 500")->fetchAll());
case 'admin_user_role':if(!in_array($in['role']??'',['player','moderator','admin','main_admin','creator']))out(['error'=>'Неверная роль'],400);
 q("UPDATE users SET role=? WHERE nick=? AND id<>?",[$in['role'],$in['nick']??'',me()]);out(['ok'=>1]); // себе роль менять нельзя
case 'admin_user_balance':$dl=(int)($in['delta']??0);if(!$dl)out(['error'=>'Укажите сумму'],400);q("UPDATE users SET balance=GREATEST(0,balance+?) WHERE nick=?",[$dl,$in['nick']??'']);out(['ok'=>1]);
case 'admin_user_delete':$u=q("SELECT id FROM users WHERE nick=? AND id<>?",[$in['nick']??'',me()])->fetch();if(!$u)out(['error'=>'Нельзя удалить'],400);
 foreach(['topics','chat','friends','scores'] as $t)q("DELETE FROM $t WHERE user_id=?",[$u['id']]);q("DELETE FROM users WHERE id=?",[$u['id']]);out(['ok'=>1]);
case 'admin_promos':out(q("SELECT code,percent FROM promo_codes WHERE active=1")->fetchAll());
case 'admin_promo_save':$pc=(int)($in['percent']??0);if(!preg_match('/^[A-Z0-9]{3,20}$/',$in['code']??'')||$pc<1||$pc>90)out(['error'=>'Неверные данные'],400);q("REPLACE INTO promo_codes(code,percent,active) VALUES(?,?,1)",[$in['code'],$pc]);out(['ok'=>1]);
case 'admin_promo_delete':q("DELETE FROM promo_codes WHERE code=?",[$in['code']??'']);out(['ok'=>1]);
case 'admin_topics':out(q("SELECT t.id,t.title,u.nick FROM topics t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 100")->fetchAll());
case 'admin_topic_delete':q("DELETE FROM topics WHERE id=?",[(int)($in['id']??0)]);out(['ok'=>1]);
case 'admin_chat':out(q("SELECT c.id,c.msg,u.nick FROM chat c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC LIMIT 100")->fetchAll());
case 'admin_chat_delete':q("DELETE FROM chat WHERE id=?",[(int)($in['id']??0)]);out(['ok'=>1]);
default:out(['error'=>'not found'],404);}
