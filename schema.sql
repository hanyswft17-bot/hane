-- SSSOINJ JTAG platform schema for Supabase
create extension if not exists pgcrypto;

create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  display_name text,
  role text not null default 'customer' check (role in ('customer','admin')),
  created_at timestamptz not null default now()
);
create table if not exists public.services (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  slug text not null unique,
  price numeric(12,2) not null default 0 check (price >= 0),
  currency text not null default 'SDG',
  description text not null default '',
  fields jsonb not null default '["IMEI","SN","موديل الجهاز"]'::jsonb,
  active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create table if not exists public.orders (
  id uuid primary key default gen_random_uuid(),
  order_number bigint generated always as identity unique,
  user_id uuid not null references auth.users(id) on delete cascade,
  service_id uuid references public.services(id) on delete set null,
  customer_name text not null,
  phone text not null,
  details jsonb not null default '{}'::jsonb,
  status text not null default 'new' check(status in ('new','reviewing','awaiting_payment','paid','in_progress','completed','rejected','cancelled')),
  price_at_order numeric(12,2) not null default 0,
  currency text not null default 'SDG',
  admin_reply text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
-- Always calculate the order price inside the database; never trust a browser-supplied price.
create or replace function public.set_order_price_from_service()
returns trigger language plpgsql security definer set search_path = public
as $$
declare
  service_row public.services%rowtype;
begin
  select * into service_row from public.services where id = new.service_id and active = true;
  if not found then
    raise exception 'Service is unavailable';
  end if;
  new.price_at_order := service_row.price;
  new.currency := service_row.currency;
  new.status := 'new';
  new.admin_reply := null;
  return new;
end;
$$;
drop trigger if exists set_order_price_before_insert on public.orders;
create trigger set_order_price_before_insert
before insert on public.orders
for each row execute function public.set_order_price_from_service();

create table if not exists public.payment_methods (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  account_number text not null,
  beneficiary text not null,
  instructions text not null default '',
  active boolean not null default true,
  created_at timestamptz not null default now()
);
create table if not exists public.payment_submissions (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references auth.users(id) on delete cascade,
  method_label text not null,
  amount numeric(12,2) not null check(amount > 0),
  reference_number text,
  receipt_path text not null,
  status text not null default 'pending' check(status in ('pending','approved','rejected')),
  admin_note text,
  created_at timestamptz not null default now(),
  reviewed_at timestamptz
);

create or replace function public.is_admin()
returns boolean language sql stable security definer
set search_path = public
as $$
  select exists(select 1 from public.profiles where id = auth.uid() and role = 'admin');
$$;
revoke all on function public.is_admin() from public;
grant execute on function public.is_admin() to authenticated;

create or replace function public.handle_new_user()
returns trigger language plpgsql security definer set search_path = public
as $$
begin
  insert into public.profiles(id, display_name, role)
  values(new.id, coalesce(new.raw_user_meta_data->>'display_name',''), 'customer')
  on conflict(id) do nothing;
  return new;
end;
$$;
drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users
for each row execute procedure public.handle_new_user();

alter table public.profiles enable row level security;
alter table public.services enable row level security;
alter table public.orders enable row level security;
alter table public.payment_methods enable row level security;
alter table public.payment_submissions enable row level security;

drop policy if exists "profiles read own or admin" on public.profiles;
create policy "profiles read own or admin" on public.profiles for select to authenticated using(id=auth.uid() or public.is_admin());
-- Users cannot change role: no profile update policy is intentionally created.
drop policy if exists "services public read active" on public.services;
create policy "services public read active" on public.services for select to anon, authenticated using(active=true or public.is_admin());
drop policy if exists "admin insert services" on public.services;
create policy "admin insert services" on public.services for insert to authenticated with check(public.is_admin());
drop policy if exists "admin update services" on public.services;
create policy "admin update services" on public.services for update to authenticated using(public.is_admin()) with check(public.is_admin());
drop policy if exists "admin delete services" on public.services;
create policy "admin delete services" on public.services for delete to authenticated using(public.is_admin());

drop policy if exists "customer create own orders" on public.orders;
create policy "customer create own orders" on public.orders for insert to authenticated with check(user_id=auth.uid() and status='new' and admin_reply is null);
drop policy if exists "read own orders or admin" on public.orders;
create policy "read own orders or admin" on public.orders for select to authenticated using(user_id=auth.uid() or public.is_admin());
drop policy if exists "admin update orders" on public.orders;
create policy "admin update orders" on public.orders for update to authenticated using(public.is_admin()) with check(public.is_admin());

drop policy if exists "read active payment methods" on public.payment_methods;
create policy "read active payment methods" on public.payment_methods for select to authenticated using(active=true or public.is_admin());
drop policy if exists "admin insert payment methods" on public.payment_methods;
create policy "admin insert payment methods" on public.payment_methods for insert to authenticated with check(public.is_admin());
drop policy if exists "admin update payment methods" on public.payment_methods;
create policy "admin update payment methods" on public.payment_methods for update to authenticated using(public.is_admin()) with check(public.is_admin());
drop policy if exists "admin delete payment methods" on public.payment_methods;
create policy "admin delete payment methods" on public.payment_methods for delete to authenticated using(public.is_admin());

drop policy if exists "customer submit own payment" on public.payment_submissions;
create policy "customer submit own payment" on public.payment_submissions for insert to authenticated with check(user_id=auth.uid() and status='pending');
drop policy if exists "read own payments or admin" on public.payment_submissions;
create policy "read own payments or admin" on public.payment_submissions for select to authenticated using(user_id=auth.uid() or public.is_admin());
drop policy if exists "admin review payments" on public.payment_submissions;
create policy "admin review payments" on public.payment_submissions for update to authenticated using(public.is_admin()) with check(public.is_admin());

insert into public.services(name,slug,price,currency,description,fields,active)
values
('خدمة JTAG عامة','jtag-general',0,'SDG','خدمة جيتاج عامة؛ عدّل الوصف والسعر حسب الخدمة الفعلية.','["IMEI","SN","موديل الجهاز","تفاصيل المشكلة"]'::jsonb,true),
('Samsung JTAG','samsung-jtag',0,'SDG','خدمة أجهزة Samsung. أضف شروط التنفيذ والسعر من لوحة الإدارة.','["IMEI","SN","موديل الجهاز","تفاصيل المشكلة"]'::jsonb,true),
('Xiaomi JTAG','xiaomi-jtag',0,'SDG','خدمة أجهزة Xiaomi. أضف شروط التنفيذ والسعر من لوحة الإدارة.','["IMEI","SN","موديل الجهاز","تفاصيل المشكلة"]'::jsonb,true),
('Huawei / Honor JTAG','huawei-honor-jtag',0,'SDG','خدمة Huawei وHonor. أضف شروط التنفيذ والسعر من لوحة الإدارة.','["IMEI","SN","موديل الجهاز","تفاصيل المشكلة"]'::jsonb,true)
on conflict(slug) do nothing;

-- Storage bucket for receipts: create a private bucket named "receipts" in Supabase Storage.
-- Suggested Storage policies: authenticated users may upload/read only within their own folder (folder name = auth.uid()).
-- Assign the first admin manually after creating their account, using the SQL Editor:
-- update public.profiles set role='admin' where id=(select id from auth.users where email='YOUR_ADMIN_EMAIL');
-- Never expose the service_role key in frontend code.
