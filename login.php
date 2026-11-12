<?php
require __DIR__.'/includes/bootstrap.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check(); $identifier=trim((string)($_POST['identifier']??''));$pass=(string)($_POST['password']??'');
 $st=$pdo->prepare('SELECT * FROM users WHERE email=? OR phone=? LIMIT 1');$st->execute([strtolower($identifier),preg_replace('/[\s\-()]+/','',$identifier)]);$u=$st->fetch();
 if($u && password_verify($pass,$u['password_hash'])){
  session_regenerate_id(true);$_SESSION['user']=['id'=>(int)$u['id'],'display_name'=>$u['display_name'],'role'=>$u['role']];
  header('Location: '.($u['role']==='admin'?'/admin/index.php':'/account.php'));exit;
 } $error='بيانات الدخول غير صحيحة.';
}
app_header('تسجيل الدخول');echo '<h1>تسجيل الدخول</h1>';if($error)echo '<div class="notice error">'.h($error).'</div>';
?>
<form method="post" class="card"><input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
<label>رقم الهاتف أو البريد الإلكتروني</label><input name="identifier" required autocomplete="username">
<label>كلمة المرور</label><input type="password" name="password" required autocomplete="current-password">
<button type="submit">دخول</button><p class="muted">ليس لديك حساب؟ <a href="/register.php">إنشاء حساب جديد</a></p></form>
<?php app_footer(); ?>
