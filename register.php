<?php
require __DIR__.'/includes/bootstrap.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check();
 $name=trim((string)($_POST['display_name']??'')); $identifier=trim((string)($_POST['identifier']??'')); $pass=(string)($_POST['password']??'');
 $phone=null;$email=null;
 if(filter_var($identifier,FILTER_VALIDATE_EMAIL)) $email=strtolower($identifier); else $phone=preg_replace('/[\s\-()]+/','',$identifier);
 if(mb_strlen($name)<2 || strlen($pass)<10 || (!$email && !preg_match('/^\+?[0-9]{7,20}$/',$phone??''))){
  $error='أدخل اسماً صحيحاً، ورقم هاتف أو بريداً إلكترونياً صالحاً، وكلمة مرور من 10 أحرف على الأقل.';
 } else {
  try{
   $st=$pdo->prepare('INSERT INTO users(display_name,phone,email,password_hash,role) VALUES(?,?,?,?,?) RETURNING id');
   $st->execute([$name,$phone,$email,password_hash($pass,PASSWORD_DEFAULT),'customer']);
   $newUserId=(int)$st->fetchColumn();
   session_regenerate_id(true);
   $_SESSION['user']=['id'=>$newUserId,'display_name'=>$name,'role'=>'customer'];
   header('Location: /account.php');exit;
  }catch(PDOException $e){$error='هذا الرقم أو البريد مستخدم بالفعل، أو تعذر إنشاء الحساب.';}
 }
}
app_header('إنشاء حساب');
echo '<h1>إنشاء حساب عميل</h1>'; if($error)echo '<div class="notice error">'.h($error).'</div>';
?>
<form method="post" class="card"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
<label>الاسم</label><input name="display_name" required maxlength="120" autocomplete="name">
<label>رقم الهاتف أو البريد الإلكتروني</label><input name="identifier" required autocomplete="username">
<label>كلمة المرور (10 أحرف على الأقل)</label><input type="password" name="password" minlength="10" required autocomplete="new-password">
<button type="submit">إنشاء الحساب</button><p class="muted">لديك حساب؟ <a href="/login.php">تسجيل الدخول</a></p></form>
<?php app_footer(); ?>
