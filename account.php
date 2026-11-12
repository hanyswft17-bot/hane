<?php
require __DIR__.'/includes/bootstrap.php';require_login();
$uid=(int)current_user()['id'];$wallet=wallet_balance($pdo,$uid);
$st=$pdo->prepare('SELECT o.*,s.name_ar FROM orders o JOIN services s ON s.id=o.service_id WHERE o.user_id=? ORDER BY o.created_at DESC');$st->execute([$uid]);$orders=$st->fetchAll();
$st=$pdo->prepare('SELECT * FROM topup_requests WHERE user_id=? ORDER BY created_at DESC');$st->execute([$uid]);$topups=$st->fetchAll();
$st=$pdo->prepare('SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 100');$st->execute([$uid]);$txs=$st->fetchAll();
$labels=['new'=>'جديد','processing'=>'قيد التنفيذ','completed'=>'مكتمل','rejected'=>'مرفوض'];$toplabels=['pending'=>'بانتظار المراجعة','approved'=>'تم الاعتماد','rejected'=>'مرفوض'];
app_header('حسابي');
echo '<h1>مرحباً '.h(current_user()['display_name']).'</h1><div class="grid"><div class="card"><p class="muted">رصيدك الحالي</p><div class="price">'.h(money($wallet)).' ج.س</div><a class="btn" href="/topup.php">شحن الرصيد</a></div><div class="card"><p class="muted">طلب خدمة</p><p>اختر الخدمة وسيتم الخصم من رصيدك بعد تأكيد الطلب.</p><a class="btn secondary" href="/services.php">استعراض الخدمات</a></div></div>';
echo '<h2 class="section-title">طلباتي</h2>';
if(!$orders)echo '<div class="card">لا توجد طلبات خدمات حتى الآن.</div>';else{echo '<div class="table-wrap"><table><thead><tr><th>رقم</th><th>الخدمة</th><th>السعر</th><th>الحالة ورد الأدمن</th><th>التاريخ</th></tr></thead><tbody>';
foreach($orders as $o)echo '<tr><td>#'.(int)$o['id'].'</td><td>'.h($o['name_ar']).'</td><td>'.h(money((float)$o['price_sdg_at_order'])).' ج.س</td><td>'.h($labels[$o['status']]??$o['status']).($o['admin_reply']?'<br><strong>رد الأدمن:</strong> '.nl2br(h($o['admin_reply'])):'').'</td><td>'.h($o['created_at']).'</td></tr>';
echo '</tbody></table></div>';}
echo '<h2 class="section-title">طلبات الشحن</h2>';
if(!$topups)echo '<div class="card">لا توجد طلبات شحن.</div>';else{echo '<div class="table-wrap"><table><thead><tr><th>رقم الشحن</th><th>المبلغ</th><th>الحالة</th><th>رد الأدمن</th><th>التاريخ</th></tr></thead><tbody>';
foreach($topups as $t)echo '<tr><td>#'.(int)$t['id'].'</td><td>'.h(money((float)$t['amount_sdg'])).' ج.س</td><td>'.h($toplabels[$t['status']]??$t['status']).'</td><td>'.h($t['admin_reply']??'—').'</td><td>'.h($t['created_at']).'</td></tr>';
echo '</tbody></table></div>';}
echo '<h2 class="section-title">سجل حركة الرصيد</h2>';
if(!$txs)echo '<div class="card">لا توجد حركات رصيد بعد.</div>';else{echo '<div class="table-wrap"><table><thead><tr><th>العملية</th><th>المبلغ</th><th>الرصيد بعد العملية</th><th>التفاصيل</th><th>التاريخ</th></tr></thead><tbody>';
foreach($txs as $t)echo '<tr><td>'.h($t['transaction_type']).'</td><td>'.h(money((float)$t['amount'])).' ج.س</td><td>'.h(money((float)$t['balance_after'])).' ج.س</td><td>'.h($t['description']).'</td><td>'.h($t['created_at']).'</td></tr>';
echo '</tbody></table></div>';}
app_footer();
?>
