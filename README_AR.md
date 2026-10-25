# SSSOINJ — منصة خدمات JTAG

## الملفات
- `index.html`: الصفحة الرئيسية.
- `services.html`: قائمة الخدمات.
- `service.html?slug=samsung-jtag`: قالب صفحة خدمة مستقلة برابط خاص لكل خدمة.
- `login.html`: دخول العملاء وإنشاء الحساب.
- `payment.html`: بيانات الدفع وإرسال الإيصالات.
- `admin.html`: لوحة الإدارة للخدمات والطلبات وطرق الدفع.
- `schema.sql`: الجداول وسياسات الحماية في Supabase.
- `assets/config.js`: بيانات الاتصال العامة بمشروع Supabase.
- `assets/style.css`: تصميم أسود وذهبي وأحمر ومتجاوب.

## إعداد Supabase
1. أنشئ مشروعًا جديدًا في Supabase.
2. افتح SQL Editor وشغّل محتوى `schema.sql`.
3. من إعدادات API انسخ Project URL ومفتاح `anon/public` فقط إلى `assets/config.js`.
4. فعّل Email Auth من Authentication حسب رغبتك.
5. أنشئ حساب الأدمن من صفحة `login.html`، ثم في SQL Editor نفّذ الأمر التالي بعد استبدال البريد:
   ```sql
   update public.profiles
   set role='admin'
   where id=(select id from auth.users where email='YOUR_ADMIN_EMAIL');
   ```
6. أنشئ Storage bucket باسم `receipts` واجعله Private. أضف سياسات تخزين تسمح للمستخدم الموثق بالرفع داخل مجلد يحمل UUID الخاص به، وللأدمن بالمراجعة. لا تجعل الإيصالات عامة.
7. اختبر حساب عميل وحساب أدمن كلٌّ على حدة.

## النشر على GitHub Pages
1. ارفع محتويات هذا المجلد إلى جذر مستودع GitHub (تأكد أن `index.html` في الجذر).
2. افتح Settings → Pages.
3. اختر Deploy from a branch ثم الفرع `main` والمجلد `/ (root)` ثم Save.
4. انتظر رابط Pages وافتحه لاختبار الصفحات.

## ملاحظات أمنية مهمة
- لا تضع مفتاح `service_role` في أي ملف بالواجهة.
- لا تمنح دور الأدمن من المتصفح؛ الترقية تتم من SQL Editor أو من خادم موثوق.
- هذا مشروع واجهة أولية متصلة بـ Supabase؛ قبل الاستخدام التجاري راجع سياسات Storage، وأضف اختبارات أمنية، واحتفظ بنسخة احتياطية.
- حالات الدفع لا تعني شحن رصيد تلقائيًا؛ راجع التحويل فعليًا قبل التأكيد.
- الطلبات اليدوية هي الوضع الافتراضي. يمكن إضافة تكامل API لاحقًا لكل خدمة عند توفر مزوّد موثوق.
