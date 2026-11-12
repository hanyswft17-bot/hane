<?php
require __DIR__.'/includes/bootstrap.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: /services.php');exit;}
csrf_check();
$serviceId=(int)($_POST['service_id']??0);$note=trim((string)($_POST['customer_note']??''));
if(mb_strlen($note)>3000){http_response_code(400);exit('الملاحظات طويلة جداً.');}
$st=$pdo->prepare('SELECT * FROM services WHERE id=? AND active=1');$st->execute([$serviceId]);$s=$st->fetch();
if(!$s){http_response_code(404);exit('الخدمة غير متاحة.');}
$rate=(float)setting($pdo,'usd_rate','3000');$sdg=price_sdg($s,(string)$rate);$uid=(int)current_user()['id'];
try {
 $pdo->beginTransaction();
 $lock=$pdo->prepare('SELECT wallet_balance FROM users WHERE id=? FOR UPDATE');$lock->execute([$uid]);$balance=(float)$lock->fetchColumn();
 if($balance+0.00001<$sdg){$pdo->rollBack();app_header('الرصيد غير كافٍ');echo '<div class="notice error">رصيدك غير كافٍ لإتمام الطلب. الرصيد الحالي: '.h(money($balance)).' ج.س، وسعر الخدمة: '.h(money($sdg)).' ج.س.</div><a class="btn" href="/topup.php">طلب شحن رصيد</a> <a class="btn secondary" href="/services.php">العودة للخدمات</a>';app_footer();exit;}
 $newBalance=$balance-$sdg;
 $up=$pdo->prepare('UPDATE users SET wallet_balance=? WHERE id=?');$up->execute([$newBalance,$uid]);
 $ins=$pdo->prepare('INSERT INTO orders(user_id,service_id,customer_note,price_at_order,currency_at_order,usd_rate_at_order,price_sdg_at_order) VALUES(?,?,?,?,?,?,?) RETURNING id');
 $ins->execute([$uid,$serviceId,$note,$s['price'],$s['currency'],$rate,$sdg]);$orderId=(int)$ins->fetchColumn();
 $tx=$pdo->prepare('INSERT INTO wallet_transactions(user_id,order_id,transaction_type,amount,balance_after,description) VALUES(?,?,?,?,?,?)');
 $tx->execute([$uid,$orderId,'service_charge',-$sdg,$newBalance,'خصم مقابل طلب الخدمة: '.$s['name_ar']]);
 $pdo->commit();
} catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);exit('تعذر حفظ الطلب وخصم الرصيد. لم يتم إرسال أي رسالة. حاول مرة أخرى.');}
app_header('تم تسجيل الطلب');
echo '<div class="notice"><strong>تم تسجيل طلبك بنجاح.</strong><br>رقم الطلب: #'.(int)$orderId.'<br>تم خصم '.h(money($sdg)).' ج.س من رصيدك، والرصيد المتبقي '.h(money($newBalance)).' ج.س.<br>سيراجع الأدمن طلبك ويرد عليك داخل حسابك.</div><a class="btn" href="/account.php">متابعة الطلب</a> <a class="btn secondary" href="/services.php">خدمات أخرى</a>';
app_footer();
?>
