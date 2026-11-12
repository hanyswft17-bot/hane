<?php
require __DIR__.'/includes/bootstrap.php';
$slug = $serviceSlug ?? basename(__FILE__, '.php');
$stmt=$pdo->prepare('SELECT * FROM services WHERE slug=? AND active=1 LIMIT 1'); $stmt->execute([$slug]); $service=$stmt->fetch();
if(!$service){http_response_code(404);app_header('الخدمة غير موجودة');echo '<h1>الخدمة غير متاحة</h1><a class="btn" href="/services.php">رجوع للخدمات</a>';app_footer();exit;}
$rate=setting($pdo,'usd_rate','3000');
app_header($service['name_ar']);
echo '<span class="pill">'.h($service['category']).'</span><h1>'.h($service['name_ar']).'</h1><p class="muted">'.h($service['description_ar']).'</p><div class="card"><p>السعر الأصلي: <strong>'.h(money((float)$service['price'])).' '.($service['currency']==='USD'?'USD':'SDG').'</strong></p><p class="price">'.h(money(price_sdg($service,$rate))).' ج.س</p><p class="muted small">سعر الدولار المعتمد: '.h(money((float)$rate)).' ج.س</p>';
if(current_user()){
 $wallet=wallet_balance($pdo,(int)current_user()['id']);
 echo '<p>رصيدك الحالي: <strong class="price">'.h(money($wallet)).' ج.س</strong></p>';
 echo '<form method="post" action="/order.php"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><input type="hidden" name="service_id" value="'.(int)$service['id'].'"><label>ملاحظات للطلب (اختياري)</label><textarea name="customer_note" rows="4" maxlength="3000" placeholder="اكتب تفاصيل جهازك أو طلبك هنا"></textarea><button type="submit">تأكيد الطلب من الرصيد</button></form>';
 echo '<p class="muted small">سيتم حفظ الطلب داخل الموقع ولن تُرسل طلبات الخدمات إلى واتساب.</p>';
} else echo '<p>لطلب الخدمة من رصيدك، يرجى <a href="/login.php">تسجيل الدخول</a> أو <a href="/register.php">إنشاء حساب</a>.</p>';
echo '</div><p><a href="/services.php">← العودة إلى الخدمات</a></p>'; app_footer();
?>
