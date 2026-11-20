# Nexagram

Nexagram is a PHP + MySQL social app (Laragon/XAMPP-style stack) with posts, friends, notifications, and direct/group messaging.

## Quick start (local)

1. Put this project in your web server root (e.g. `C:\laragon\www\nexagram-main`).
2. Start Apache and MySQL.
3. Create a MySQL database (default name: `nexa`).
4. Import `nexa.sql`.
5. Run migrations in `migrations/` if needed (e.g. `migrations/20261020_add_group_chats.sql` for group chats and `migrations/20261120_add_password_reset.sql` for password reset).
6. Configure DB credentials in `php/env.php` (see `php/env.example.php`).
7. Open the app in your browser (e.g. `http://localhost/nexagram-main/`).

## Chat with other users on other devices (same network / Laragon)

Nexagram messaging is database-backed and works across multiple devices as long as they access the same hosted app.

See **[LAN_SETUP.md](LAN_SETUP.md)** for step-by-step instructions to:
- find your host PC LAN IP,
- allow access through Laragon/Apache and Windows Firewall,
- open the app from another device on the same Wi‑Fi/LAN,
- troubleshoot common connectivity issues.

## Project structure

- `index.php` – entry redirect
- `php/` – application pages, APIs, helpers, config
- `js/`, `css/` – frontend assets
- `uploads/` – user-uploaded media
- `nexa.sql` – database schema/data dump
- `migrations/` – incremental schema updates
