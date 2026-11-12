<?php
require __DIR__.'/includes/bootstrap.php';
$rate=setting($pdo,'usd_rate','3000');
$category=trim((string)($_GET['category']??''));
if($category!==''){$stmt=$pdo->prepare('SELECT * FROM services WHERE active=1 AND category=? ORDER BY sort_order,name_ar');$stmt->execute([$category]);$services=$stmt->fetchAll();}
else{$stmt=$pdo->query('SELECT * FROM services WHERE active=1 ORDER BY sort_order,name_ar');$services=$stmt->fetchAll();}
app_header('الخدمات');
echo '<div class="section-head"><div><span class="eyebrow">HANI SOFTWARE CATALOG</span><h1>'.($category!==''?'خدمات '.h($category):'جميع الخدمات').'</h1><p>استكشف الخدمات المتاحة، وافتح صفحة أي خدمة لمعرفة التفاصيل وإرسال الطلب.</p></div><a class="text-link" href="/services.php">عرض الكل ←</a></div>';
echo '<p class="muted">سعر الدولار الحالي: <strong class="gold">'.h(money((float)$rate)).' ج.س</strong></p>';
if(current_user()) echo '<p>رصيدك الحالي: <strong class="gold">'.h(money(wallet_balance($pdo,(int)current_user()['id']))).' ج.س</strong> — <a href="/topup.php">شحن الرصيد</a></p>';
if (!$services) echo '<div class="card">لا توجد خدمات نشطة في هذا القسم حالياً. يمكن للإدارة إضافة الخدمات أو تفعيلها من لوحة الإدارة.</div>';
echo '<div class="grid">';
foreach($services as $s){$sdg=price_sdg($s,$rate);echo '<article class="card"><span class="pill">'.h($s['category']).'</span><h3>'.h($s['name_ar']).'</h3><p class="muted">'.h($s['description_ar']).'</p><div class="price">'.h(money($sdg)).' ج.س</div><p class="small muted">السعر الأصلي: '.h(money((float)$s['price'])).' '.($s['currency']==='USD'?'USD':'SDG').'</p><a class="btn btn-red" href="/service.php?slug='.rawurlencode($s['slug']).'">تفاصيل الخدمة ←</a></article>';}
echo '</div>'; app_footer();
?>
