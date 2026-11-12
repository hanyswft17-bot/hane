-- Extended starter catalog for the approved all-reference scope.
-- Prices are intentionally 0 until Owner configures real supplier-backed prices.
INSERT INTO services (slug,category,name_ar,name_en,description_ar,description_en,price,currency,active,sort_order) VALUES
('samsung-frp','Samsung','فحص FRP سامسونج','Samsung FRP eligibility check','فحص حالة الجهاز وأهلية الخدمة للأجهزة المصرح بها.','Check device status and service eligibility for authorized devices.',0,'USD',1,101),
('xiaomi-mi-account-check','Xiaomi','فحص حساب شاومي','Xiaomi account status check','فحص معلومات الجهاز وحالة الحساب بمساعدة المالك.','Owner-assisted device and account status check.',0,'USD',1,102),
('huawei-firmware','Huawei & Honor','خدمات Firmware لهواوي','Huawei firmware services','ملفات وتحديثات النظام حسب موديل الجهاز.','Firmware files and updates by device model.',0,'USD',1,103),
('tecno-infinix-frp','Tecno / Infinix / itel','فحص FRP للأجهزة المدعومة','Supported device FRP check','فحص التوافق للأجهزة المدعومة والمصرح بها.','Compatibility check for supported authorized devices.',0,'USD',1,104),
('imei-network-check','IMEI & Network','فحص IMEI والشبكة','IMEI and network check','فحص معلومات الشبكة وحالة الجهاز وفق المصادر المتاحة.','Check network information and device status where available.',0,'USD',1,105),
('mdm-owner-support','MDM','مساعدة مالك الجهاز في MDM','Owner-assisted MDM support','مساعدة موثقة لمالك الجهاز أو الجهة المالكة فقط.','Documented assistance for the device owner or owning organization only.',0,'USD',1,106),
('firmware-files','Flashing & Firmware','ملفات Firmware','Firmware files','ملفات نظام وتحديثات مطابقة لموديل الجهاز وإصداره.','Firmware and update files matched to device model and version.',0,'USD',1,107),
('box-activation','Box & Dongle Activation','تفعيل بوكس ودونجل','Box and dongle activation','تراخيص وتفعيلات أصلية للأدوات المدعومة.','Genuine licenses and activations for supported tools.',0,'USD',1,108),
('software-license','Software Licenses','تراخيص برامج السوفت وير','Software licenses','تراخيص واشتراكات برامج السوفت وير.','Software licenses and subscriptions.',0,'USD',1,109),
('game-gift-cards','Games & Gift Cards','بطاقات الألعاب والهدايا','Games and gift cards','أكواد وبطاقات رقمية حسب المنطقة والمخزون.','Digital codes and cards subject to region and availability.',0,'USD',1,110),
('tool-rental','Tool Rental','تأجير الأدوات المدعومة','Supported tool rental','طلب وصول مؤقت لأداة متاحة وفق شروط المورد.','Request temporary access to an available tool subject to provider terms.',0,'USD',1,111),
('mobile-repair-diagnosis','Repair Services','تشخيص أعطال الهواتف','Mobile repair diagnostics','تشخيص مبدئي لمشكلات السوفت وير والأداء.','Initial diagnosis of software and performance issues.',0,'SDG',1,112),
('digital-subscriptions','Digital Subscriptions','اشتراكات رقمية','Digital subscriptions','اشتراكات رقمية وخدمات متاحة حسب المورد.','Digital subscriptions and services subject to provider availability.',0,'USD',1,113),
('custom-digital-order','Other Services','طلب خدمة رقمية أخرى','Other digital service request','أرسل تفاصيل طلبك ليتم مراجعته من الإدارة.','Submit your request details for admin review.',0,'SDG',1,114)
ON CONFLICT (slug) DO NOTHING;
