# طلبات الموقع (Website Orders) — نقطة الاستقبال العامة

المسار الجديد في الموقع (بدون تسجيل دخول) يجمع تفاصيل العقد ورقم واتساب العميل،
ثم يرسل الطلب من خادم الموقع (`/api/order`) إلى هذه النقطة ليُحفظ في قاعدة
بياناتك كمصدر أساسي، مستقلًّا عن قناتي تلقرام/الإيميل.

> ملاحظة تسمية: كلمة «orders» في اللوحة تعني عندنا **طلبات العقود** (وحدة
> Contracts). لذلك سُمّي هذا الكيان `website_orders` / `WebsiteOrder` لتمييزه
> عن طلبات العقود.

## النقاط (Endpoints)

- **عام (بدون تسجيل دخول):** `POST /api/v2/orders`
  - محدود بمعدّل `throttle:20,1` (٢٠ طلب/دقيقة لكل IP) ضد الإساءة.
  - سرّ مشترك اختياري: عيّن `ORDER_INTAKE_TOKEN` في `.env`؛ حينها يجب أن يرسل
    المتصل `Authorization: Bearer <token>` مطابقًا، وإلا 401. لو تركته فارغًا →
    النقطة عامة بدون تحقق.
  - منع التكرار: `order_number` فريد؛ الطلب المكرر يرجع السجل نفسه (200) دون
    إنشاء نسخة ثانية.
  - جسم الطلب: `{ orderNumber, contractType, whatsappNumber, sections:[{title,
    fields:[{label,value}]}], notes, source, createdAt }`.
  - الرد: `{ "ok": true, "id": <id> }` بكود 201 (أو 200 للمكرر).

- **اللوحة (موظف + صلاحية `users.view`):** `GET /api/admin/website-orders`
  - يدعم `?status=new&search=...&per_page=20` مع تلخيص (total / new).

## بعد النشر

شغّل الهجرة لإنشاء جدول `website_orders`:

```
php artisan migrate --force
```

## الملفات المضافة

- `database/migrations/2026_09_25_000100_create_website_orders_table.php`
- `app/Models/WebsiteOrder.php`
- `app/Modules/Leads/Controllers/WebsiteOrderController.php`
- `app/Modules/Leads/Routes/api_v2.php` (أضيف مسار `/orders` العام)
- `app/Modules/Leads/Routes/admin.php` (أضيف مسار `/website-orders`)
- `config/services.php` (كتلة `order_intake.token`)

يطابق هذا مواصفة الموقع في `docs/order-intake-api.md` (مستودع الفرونت-إند).
