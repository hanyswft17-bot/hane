<?php
require __DIR__.'/../includes/bootstrap.php';require_admin();$msg='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();$action=$_POST['action']??'';
 try{
  if($action==='rate'){
   $rate=(float)($_POST['usd_rate']??0);if($rate<=0||$rate>100000000)throw new RuntimeException('سعر الدولار غير صالح.');
   $st=$pdo->prepare("INSERT INTO settings(setting_key,setting_value) VALUES('usd_rate',?) ON CONFLICT (setting_key) DO UPDATE SET setting_value=EXCLUDED.setting_value");$st->execute([(string)$rate]);$msg='تم تحديث سعر الدولار.';
  }elseif($action==='service'){
   $id=(int)($_POST['id']??0);$slug=trim((string)($_POST['slug']??''));$category=trim((string)($_POST['category']??''));$ar=trim((string)($_POST['name_ar']??''));$en=trim((string)($_POST['name_en']??''));$desc=trim((string)($_POST['description_ar']??''));$price=(float)($_POST['price']??0);$currency=($_POST['currency']??'USD')==='SDG'?'SDG':'USD';$active=isset($_POST['active'])?1:0;
   if(!preg_match('/^[a-z0-9-]{2,100}$/',$slug)||$ar===''||$en===''||$price<0)throw new RuntimeException('راجع بيانات الخدمة والسعر.');
   if($id){$st=$pdo->prepare('UPDATE services SET slug=?,category=?,name_ar=?,name_en=?,description_ar=?,price=?,currency=?,active=? WHERE id=?');$st->execute([$slug,$category,$ar,$en,$desc,$price,$currency,$active,$id]);}
   else{$st=$pdo->prepare('INSERT INTO services(slug,category,name_ar,name_en,description_ar,price,currency,active) VALUES(?,?,?,?,?,?,?,?)');$st->execute([$slug,$category,$ar,$en,$desc,$price,$currency,$active]);}
   $msg='تم حفظ الخدمة والسعر.';
  }elseif($action==='status'){
   $id=(int)($_POST['order_id']??0);$status=(string)($_POST['status']??'');$reply=trim((string)($_POST['admin_reply']??''));
   if(!in_array($status,['new','processing','completed','rejected'],true))throw new RuntimeException('حالة الطلب غير صالحة.');
   if(mb_strlen($reply)>3000)throw new RuntimeException('رد الأدمن طويل جداً.');
   $pdo->beginTransaction();
   $st=$pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$st->execute([$id]);$order=$st->fetch();
   if(!$order)throw new RuntimeException('الطلب غير موجود.');
   $up=$pdo->prepare('UPDATE orders SET status=?,admin_reply=? WHERE id=?');$up->execute([$status,$reply?:null,$id]);
   // Refund exactly once when the order first transitions to rejected.
   if($status==='rejected' && $order['status']!=='rejected'){
     $uid=(int)$order['user_id'];$amount=(float)$order['price_sdg_at_order'];
     $lock=$pdo->prepare('SELECT wallet_balance FROM users WHERE id=? FOR UPDATE');$lock->execute([$uid]);$bal=(float)$lock->fetchColumn();$new=$bal+$amount;
     $upb=$pdo->prepare('UPDATE users SET wallet_balance=? WHERE id=?');$upb->execute([$new,$uid]);
     $tx=$pdo->prepare("INSERT INTO wallet_transactions(user_id,order_id,transaction_type,amount,balance_after,description) VALUES(?,?,'refund',?,?,?)");
     $tx->execute([$uid,$id,$amount,$new,'إرجاع قيمة الطلب المرفوض رقم #'.$id]);
   }
   $pdo->commit();$msg='تم تحديث الطلب والرد على العميل.'.($status==='rejected'?' وتم إرجاع قيمة الطلب إلى رصيد العميل.':'');
  }elseif($action==='topup'){
   $id=(int)($_POST['topup_id']??0);$status=(string)($_POST['status']??'');$reply=trim((string)($_POST['admin_reply']??''));
   if(!in_array($status,['approved','rejected'],true))throw new RuntimeException('حالة الشحن غير صالحة.');
   if(mb_strlen($reply)>2000)throw new RuntimeException('رد الأدمن طويل جداً.');
   $pdo->beginTransaction();
   $st=$pdo->prepare('SELECT * FROM topup_requests WHERE id=? FOR UPDATE');$st->execute([$id]);$top=$st->fetch();
   if(!$top)throw new RuntimeException('طلب الشحن غير موجود.');
   if($top['status']!=='pending')throw new RuntimeException('تمت معالجة طلب الشحن من قبل.');
   $up=$pdo->prepare('UPDATE topup_requests SET status=?,admin_reply=?,processed_by=?,processed_at=CURRENT_TIMESTAMP WHERE id=?');$up->execute([$status,$reply?:null,current_user()['id'],$id]);
   if($status==='approved'){
     $uid=(int)$top['user_id'];$amount=(float)$top['amount_sdg'];
     $lock=$pdo->prepare('SELECT wallet_balance FROM users WHERE id=? FOR UPDATE');$lock->execute([$uid]);$bal=(float)$lock->fetchColumn();$new=$bal+$amount;
     $upb=$pdo->prepare('UPDATE users SET wallet_balance=? WHERE id=?');$upb->execute([$new,$uid]);
     $tx=$pdo->prepare("INSERT INTO wallet_transactions(user_id,topup_id,transaction_type,amount,balance_after,description) VALUES(?,?,'topup',?,?,?)");
     $tx->execute([$uid,$id,$amount,$new,'اعتماد طلب شحن رقم #'.$id]);
   }
   $pdo->commit();$msg=$status==='approved'?'تم اعتماد الشحن وإضافة الرصيد.':'تم رفض طلب الشحن.';
  }
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'تعذر الحفظ. راجع البيانات وحاول مرة أخرى.';}
}
$rate=setting($pdo,'usd_rate','3000');$services=$pdo->query('SELECT * FROM services ORDER BY sort_order,id DESC')->fetchAll();
$orders=$pdo->query('SELECT o.*,u.display_name,u.phone,u.email,s.name_ar FROM orders o JOIN users u ON u.id=o.user_id JOIN services s ON s.id=o.service_id ORDER BY o.created_at DESC LIMIT 100')->fetchAll();
$topups=$pdo->query('SELECT t.*,u.display_name,u.phone,u.email FROM topup_requests t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 100')->fetchAll();
$users=$pdo->query('SELECT id,display_name,phone,email,role,created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
$edit=null;if(isset($_GET['edit'])){$st=$pdo->prepare('SELECT * FROM services WHERE id=?');$st->execute([(int)$_GET['edit']]);$edit=$st->fetch()?:null;}
app_header('لوحة الأدمن');
echo '<h1>لوحة تحكم الأدمن</h1>';if($msg)echo '<div class="notice">'.h($msg).'</div>';if($error)echo '<div class="notice error">'.h($error).'</div>';
echo '<section class="card"><h2>سعر الدولار</h2><p class="muted">يؤثر على الخدمات المسعّرة بالدولار فقط. أسعار الجنيه تبقى كما هي.</p><form method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="rate"><label>كم جنيه سوداني يساوي الدولار الواحد؟</label><input type="number" min="0.01" step="0.01" name="usd_rate" value="'.h($rate).'" required><button>حفظ سعر الدولار</button></form></section>';
echo '<h2 class="section-title">'.($edit?'تعديل خدمة':'إضافة خدمة').'</h2><form class="card" method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="service"><input type="hidden" name="id" value="'.(int)($edit['id']??0).'"><label>رابط الخدمة (slug إنجليزي)</label><input name="slug" pattern="[a-z0-9-]{2,100}" required value="'.h($edit['slug']??'').'"><label>القسم</label><input name="category" required value="'.h($edit['category']??'').'"><label>اسم الخدمة بالعربي</label><input name="name_ar" required value="'.h($edit['name_ar']??'').'"><label>اسم الخدمة بالإنجليزي</label><input name="name_en" required value="'.h($edit['name_en']??'').'"><label>وصف الخدمة بالعربي</label><textarea name="description_ar" rows="3">'.h($edit['description_ar']??'').'</textarea><label>السعر</label><input type="number" min="0" step="0.01" name="price" required value="'.h((string)($edit['price']??0)).'"><label>عملة السعر</label><select name="currency"><option value="USD"'.(($edit['currency']??'USD')==='USD'?' selected':'').'>دولار USD</option><option value="SDG"'.(($edit['currency']??'USD')==='SDG'?' selected':'').'>جنيه سوداني SDG</option></select><label><input type="checkbox" name="active" style="width:auto"'.((!$edit||($edit['active']??1))?' checked':'').'> الخدمة ظاهرة للعملاء</label><button>حفظ الخدمة</button></form>';
echo '<h2 class="section-title">إدارة الخدمات والأسعار</h2><div class="table-wrap"><table><thead><tr><th>الخدمة</th><th>السعر الأصلي</th><th>المقابل بالجنيه</th><th>الحالة</th><th></th></tr></thead><tbody>';
foreach($services as $s)echo '<tr><td>'.h($s['name_ar']).'<br><small class="muted">'.h($s['slug']).'</small></td><td>'.h(money((float)$s['price'])).' '.h($s['currency']).'</td><td>'.h(money(price_sdg($s,$rate))).' ج.س</td><td>'.($s['active']?'نشطة':'مخفية').'</td><td><a href="/admin/index.php?edit='.(int)$s['id'].'">تعديل</a></td></tr>';
echo '</tbody></table></div>';
echo '<h2 class="section-title">طلبات العملاء</h2><div class="table-wrap"><table><thead><tr><th>الطلب</th><th>العميل</th><th>الخدمة والسعر</th><th>الحالة</th><th>تغيير الحالة</th></tr></thead><tbody>';
$statuses=['new'=>'جديد','processing'=>'قيد التنفيذ','completed'=>'مكتمل','rejected'=>'مرفوض'];
foreach($orders as $o){echo '<tr><td>#'.(int)$o['id'].'<br><small>'.h($o['created_at']).'</small></td><td>'.h($o['display_name']).'<br>'.h($o['phone']?:$o['email']).'</td><td>'.h($o['name_ar']).'<br>'.h(money((float)$o['price_sdg_at_order'])).' ج.س</td><td>'.h($statuses[$o['status']]).'<br><small>'.h($o['admin_reply']??'').'</small></td><td><form method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="'.(int)$o['id'].'"><select name="status">';
foreach($statuses as $k=>$v)echo '<option value="'.h($k).'"'.($o['status']===$k?' selected':'').'>'.h($v).'</option>';
echo '</select><textarea name="admin_reply" rows="2" maxlength="3000" placeholder="رد يظهر للعميل">'.h($o['admin_reply']??'').'</textarea><button>حفظ الحالة والرد</button></form></td></tr>';}
echo '</tbody></table></div>';
echo '<h2 class="section-title">طلبات شحن الرصيد</h2><div class="table-wrap"><table><thead><tr><th>رقم الشحن</th><th>العميل</th><th>الحساب المراد شحنه</th><th>المبلغ</th><th>المرجع والملاحظات</th><th>الحالة</th><th>المعالجة</th></tr></thead><tbody>';
foreach($topups as $t){
 echo '<tr><td>#'.(int)$t['id'].'<br><small>'.h($t['created_at']).'</small></td><td>'.h($t['display_name']).'<br>'.h($t['phone']?:$t['email']).'</td><td>اسم المستخدم: '.h($t['target_username']??'—').'<br>البريد: '.h($t['target_email']??'—').'</td><td>'.h(money((float)$t['amount_sdg'])).' ج.س</td><td>'.h($t['payment_reference']??'—').'<br>'.h($t['customer_note']??'').'</td><td>'.h(['pending'=>'بانتظار المراجعة','approved'=>'معتمد','rejected'=>'مرفوض'][$t['status']]??$t['status']).'<br>'.h($t['admin_reply']??'').'</td><td>';
 if($t['status']==='pending'){
  echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><input type="hidden" name="action" value="topup"><input type="hidden" name="topup_id" value="'.(int)$t['id'].'"><textarea name="admin_reply" rows="2" maxlength="2000" placeholder="رد للعميل (اختياري)"></textarea><button name="status" value="approved">اعتماد وإضافة الرصيد</button> <button class="danger" name="status" value="rejected">رفض</button></form>';
 }else echo 'تمت المعالجة';
 echo '</td></tr>';
}
echo '</tbody></table></div>';
echo '<h2 class="section-title">حسابات العملاء</h2><div class="table-wrap"><table><thead><tr><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>الصلاحية</th><th>تاريخ التسجيل</th></tr></thead><tbody>';
foreach($users as $u)echo '<tr><td>'.h($u['display_name']).'</td><td>'.h($u['phone']).'</td><td>'.h($u['email']).'</td><td>'.h($u['role']).'</td><td>'.h($u['created_at']).'</td></tr>';
echo '</tbody></table></div>';app_footer();
?>
