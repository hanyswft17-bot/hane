# هاني سوفت وير — نسخة Supabase + GitHub + Render

هذه النسخة تم تحويل اتصال PHP إلى PostgreSQL عبر PDO_PGSQL، وأضيف Dockerfile لتشغيل PHP على استضافة تدعم Docker مثل Render.

## 1) تجهيز Supabase
1. افتح مشروعك في Supabase ثم SQL Editor.
2. شغّل كامل ملف `database/schema_supabase.sql` في مشروع جديد/فارغ.
3. شغّل كامل ملف `database/seed_services_supabase.sql` لإضافة الخدمات الأولية.
4. من زر **Connect** في Supabase اختر **Session pooler** وانسخ بيانات الاتصال كما هي. لا تخمّن عنوان المضيف أو اسم المستخدم.
   - `DB_HOST`: عنوان المضيف الخاص بالـ Session pooler.
   - `DB_PORT`: `5432`.
   - `DB_NAME`: `postgres`.
   - `DB_USER`: اسم المستخدم الذي يعرضه Supabase، غالباً `postgres.<PROJECT_REF>` للـ Session pooler.
   - `DB_PASSWORD`: كلمة مرور قاعدة البيانات.
   - `DB_SSLMODE`: `require`.

## 2) رفع الملفات إلى GitHub
1. أنشئ مستودعاً جديداً باسم `hani-software` واجعله **Private** أثناء التطوير.
2. ارفع محتويات هذا المجلد إلى جذر المستودع (وليس ملف ZIP فقط).
3. لا ترفع `config.php` أو `.env` أو كلمات المرور أو مفاتيح Supabase السرية. ملف `.gitignore` يستثني هذه الملفات.

## 3) تشغيل الموقع على Render
GitHub يخزن الكود ولا يشغّل PHP، لذلك نستخدم استضافة PHP/Docker. مثال مناسب هو Render:
1. افتح Render وأنشئ **New > Web Service** ثم اربط مستودع GitHub.
2. اختر Runtime: **Docker**؛ سيستخدم `Dockerfile` الموجود في جذر المشروع.
3. في Environment أضف المتغيرات `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSLMODE`, `SITE_NAME`, `WHATSAPP_NUMBER` كما هو موضح أعلاه.
4. انشر الخدمة وافحص سجلات النشر. موارد الاستضافة المجانية قد تتوقف عند الخمول أو تتغير شروطها؛ راجع خطة Render الحالية.

## 4) إنشاء حساب الأدمن
1. بعد تشغيل الموقع، سجّل حساباً عادياً من صفحة إنشاء الحساب.
2. في Supabase SQL Editor نفّذ الاستعلام التالي مع تغيير البريد إلى بريدك المسجّل:

```sql
UPDATE public.users SET role = 'admin' WHERE email = 'YOUR_REGISTERED_EMAIL';
```

إذا سجلت برقم هاتف فقط، استخدم `WHERE phone = 'YOUR_REGISTERED_PHONE'` بدلاً من البريد. لا تترك الشرط دون تحديد مستخدم.

## تنبيهات أمنية
- لا تستخدم `service_role` أو `secret key` داخل المتصفح. هذا الموقع يتصل بقاعدة البيانات من الخادم عبر PDO.
- استخدم كلمة مرور قوية لقاعدة البيانات ولا تشاركها في المحادثات أو GitHub.
- شغّل ملف SQL على مشروع جديد/فارغ؛ لا تنفذه على قاعدة تحتوي بيانات مهمة دون نسخة احتياطية.
- هذه حزمة تجهيز وليست دليلاً على أن النشر نجح؛ يلزم وضع بيانات اتصالك ثم اختبار التسجيل والطلبات والمحفظة والشحن في بيئة فعلية قبل استقبال العملاء.

## إضافة أقسام الكتالوج الموسع
بعد تشغيل `database/schema_supabase.sql` و`database/seed_services_supabase.sql`، شغّل `database/seed_extended_catalog.sql`. ستضاف خدمات أولية بأسعار 0 حتى تضبط الأسعار الفعلية من لوحة الإدارة. لا تعتمد أسعاراً أو توفر منتجات رقمية قبل التأكد من المورد وشروطه.
