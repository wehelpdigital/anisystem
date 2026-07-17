# AniSystem — Cropping Schedule Manager SaaS

AniSystem is the client-facing SaaS version of the AniSenso **Schedule Manager** that lives in the
mother super-admin system (**btc-check**, http://btc-check.test). Farmers sign up, pay via **GCash**
(manual verification), and manage their own cropping schedules in a mobile-first web app at
**http://anisystem.test**.

Built from scratch with **Laravel 12 + Tailwind CSS v4 + Alpine.js** (no admin template) — the Skote
template remains exclusive to the mother system.

---

## How the two systems fit together

Both apps share **one MySQL database** (`onmartph_axis`). There is no API between them — integration
is at the database level, following the same pattern the `anisenso-course` site already uses.

| Concern | Where it lives |
|---|---|
| SaaS users / plans / subscriptions | `anisystem_users`, `anisystem_plans`, `anisystem_subscriptions` (new tables) |
| Orders + GCash payment proof | `ecom_orders`, `ecom_order_items`, `ecom_order_audit_logs` (existing mother tables, store **AniSystem**, order numbers `ANI-YYYYMMDD-XXXX`) |
| Payment screenshots | Written into `btc-check/public/images/payment-screenshots/` (path in `BTC_CHECK_PUBLIC_PATH`) so the admin UI can render them |
| Cropping schedules + all sub-modules | The same `as_*` tables the mother uses; client-owned rows carry `as_cropping_schedules.anisystemUserId` |
| SMTP settings + email templates | `as_mail_smtp_settings`, `as_email_templates` (grouped by `AniSystem`), managed in the mother under **Ani-Senso → Mail Settings** |

### Subscription lifecycle

1. Client signs up → chooses a plan → sends GCash payment → uploads screenshot / reference number.
2. A `pending` order appears in the mother's **/ecom-orders**; a `pending` subscription row is created.
3. Admin verifies the payment in /ecom-orders → the mother-side hook (and/or AniSystem's throttled
   sync in the `subscription` middleware) activates the subscription (`expiresAt = startsAt + durationDays`;
   renewals stack on the current expiry) and the client gets the *payment approved* email.
4. Rejection / order cancellation → subscription `rejected` / `cancelled`. Expiry → locked out of the
   app (account + renewal pages stay reachable).
5. The mother's **Ani-Senso → Clients** page lists all AniSystem clients with their subscriptions and
   Suspend / Unsuspend / Cancel actions (cancel also cancels an unverified linked order).

Client-created schedules appear in the mother's **/anisenso-schedule-manager** list with an
**AniSystem Client** badge + owner name; admins can open and manage them.

## Feature map (client app)

- Public site: Home, About, Tutorial (with FAQ), Contact (stored + emailed).
- Auth: login, signup, forgot/reset password (emails via the AniSystem template group).
- Purchase/renewal: plan cards → GCash instructions (reads the store's payment settings from the
  shared DB) → proof upload → thank-you/status page.
- Account: profile, change password, subscription status/history, renew, refresh status.
- Schedule manager (per schedule, hub + module pages): Settings (+ default groupings), Lots,
  Workers (+ off-day rules), Materials, Services, Documentation (protocol, rich-text introduction,
  attachments, critical rules), **Activities** (timeline with versions, drafts, hidden, Day-0/DAS,
  date notes, progress markers, drag-drop reorder, undo, labor expense summary), Irrigation
  (DAS or date ranges, task types, priorities), printable documents (Export, Card Viewer, Worker
  Presentation). The admin-only Generate / Calendar / Reports features are intentionally excluded.

## Local setup

```bash
composer install
cp .env.example .env        # then set the values below
php artisan key:generate
php artisan migrate         # shared DB — tables are guarded with hasTable checks
php artisan storage:link
npm install && npm run build   # needs Node 20+ (nvm use 23.6.0 on this machine)
```

Key `.env` values:

```
DB_*                     # the shared btc-check MySQL database
BTC_CHECK_URL=http://btc-check.test
BTC_CHECK_PUBLIC_PATH="C:/xampp/htdocs/btc-check/public"
ANISYSTEM_STORE_ID=5     # ecom_product_stores row 'AniSystem'
ANISYSTEM_ORDER_USERS_ID=1
```

Apache vhost `anisystem.test → public/` is already in XAMPP's `httpd-vhosts.conf`
(**restart Apache once** so it loads). Sessions/cache use the `file` drivers — the remote DB is
only hit for business data.

### Scheduled maintenance

`php artisan anisystem:check-subscriptions` (scheduled daily 06:00) syncs pending subscriptions
against order decisions, persists expirations, and sends the *expiring in 7 days* notices. Point a
cron / Task Scheduler entry at `php artisan schedule:run` every minute in production.

### Mail

All outbound email uses the SMTP settings + templates from **Ani-Senso → Mail Settings** in the
mother app (group **AniSystem**). Until SMTP is configured & activated there, emails are written to
`storage/logs/laravel.log` (log fallback) so flows never fail in development.

## Mother-app additions (in the btc-check codebase)

- **Ani-Senso → Clients** (`/anisenso-clients`): all AniSystem clients + latest subscription,
  suspend/unsuspend/cancel (wired to ecom-orders), details modal with history and linked orders.
- **Ani-Senso → Mail Settings** (`/anisenso-mail-settings`): per-group SMTP (test-send included) and
  email templates grouped by `AniSystem` / `AniSenso` with `{{tag}}` merge fields.
- Schedule Manager list: **Owner** column (AniSystem Client badge + name); admins see their own +
  all client schedules; setup page shows a client-owned banner.
- `OrdersController` verify/reject/cancel now notify `AnisystemSubscriptionService` to activate or
  cancel the matching subscription and send the templated email.

## Demo data

A verified demo client exists: `e2e-test@anisystem.test` / `secret1234` (active subscription, one
schedule "E2E Test Season — Rice") — visible in /ecom-orders, /anisenso-clients and the schedule list.
