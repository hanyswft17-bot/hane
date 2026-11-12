<?php
require __DIR__.'/includes/bootstrap.php';require_login();
$error='';$topupId=0;$amount=0;
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();$amount=(float)($_POST['amount_sdg']??0);$note=trim((string)($_POST['customer_note']??''));$ref=trim((string)($_POST['payment_reference']??''));$targetUsername=trim((string)($_POST['target_username']??''));$targetEmail=trim((string)($_POST['target_email']??''));
 if($amount<1||$amount>100000000){$error='أدخل مبلغاً صحيحاً للشحن.';}
 elseif(mb_strlen($note)>1500||mb_strlen($ref)>190||mb_strlen($targetUsername)>190||mb_strlen($targetEmail)>190||($targetEmail!==''&&!filter_var($targetEmail,FILTER_VALIDATE_EMAIL))){$error='راجع البريد الإلكتروني والبيانات المدخلة.';}
 elseif($targetUsername===''&&$targetEmail===''){$error='أدخل اسم المستخدم أو البريد الإلكتروني للحساب المطلوب شحنه.';}
 else{
  $st=$pdo->prepare('INSERT INTO topup_requests(user_id,amount_sdg,target_username,target_email,payment_reference,customer_note) VALUES(?,?,?,?,?,?) RETURNING id');
  $st->execute([current_user()['id'],$amount,$targetUsername?:null,$targetEmail?:null,$ref?:null,$note?:null]);$topupId=(int)$st->fetchColumn();
 }
}
app_header('شحن الرصيد');
echo '<h1>شحن رصيد الحساب</h1><p class="muted">طلبات الشحن فقط هي التي تستخدم واتساب. لا يتم إضافة الرصيد تلقائياً؛ الأدمن يراجع الطلب ثم يعتمده.</p>';
if($error)echo '<div class="notice error">'.h($error).'</div>';
if($topupId){
 $wa=preg_replace('/\D/','',(string)($config['whatsapp_number']??'84562812172'));
 $msg="طلب شحن رصيد Hani Software%0Aرقم طلب الشحن: #".$topupId."%0Aالعميل: ".rawurlencode(current_user()['display_name'])."%0Aاسم المستخدم المراد شحنه: ".rawurlencode($targetUsername)."%0Aالبريد الإلكتروني المراد شحنه: ".rawurlencode($targetEmail)."%0Aالمبلغ المطلوب: ".rawurlencode(money($amount).' SDG');
 echo '<div class="notice">تم حفظ طلب الشحن رقم #'.(int)$topupId.' في الموقع. انتظر مراجعة الأدمن لاعتماد الرصيد.</div><a class="btn" target="_blank" rel="noopener" href="https://wa.me/'.h($wa).'?text='.$msg.'">إرسال طلب الشحن عبر واتساب</a><p><a href="/account.php">العودة إلى حسابي</a></p>';
}else{
 echo '<form class="card" method="post"><input type="hidden" name="csrf" value="'.h(csrf_token()).'"><label>المبلغ المطلوب بالجنيه السوداني</label><input type="number" min="1" max="100000000" step="1" name="amount_sdg" required><label>اسم المستخدم للحساب المراد شحنه</label><input name="target_username" maxlength="190" placeholder="أدخل اسم المستخدم"><label>البريد الإلكتروني للحساب المراد شحنه</label><input type="email" name="target_email" maxlength="190" placeholder="name@example.com"><p class="muted small">أدخل اسم المستخدم أو البريد الإلكتروني على الأقل. لا ترسل كلمة المرور.</p><label>رقم مرجع التحويل (اختياري)</label><input name="payment_reference" maxlength="190"><label>ملاحظات (اختياري)</label><textarea name="customer_note" rows="3" maxlength="1500"></textarea><button>حفظ طلب الشحن والمتابعة إلى واتساب</button></form>';
}
app_footer();
?>
