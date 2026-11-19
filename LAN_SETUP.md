# Nexagram LAN Setup (Laragon)

This guide helps you run Nexagram on one Windows PC with Laragon and let other users on the same local network chat from their own devices.

## How it works

- Nexagram stores chat messages in MySQL and the browser fetches/sends messages through `php/api_messages.php` (polling).
- As long as all devices can open the same Nexagram site hosted by Laragon, they can log in as different users and chat in real time (near real-time, refreshed every few seconds).

## 1. Prepare the host PC (the one running Laragon)

1. **Start Laragon services**
   - Start **Apache** and **MySQL** in Laragon.
2. **Place the project in Laragon web root**
   - Example: `C:\laragon\www\nexagram-main`
3. **Create/import the database**
   - Create a database named `nexa` (or match `php/env.php`).
   - Import `nexa.sql`.
   - If group chat tables are missing, also run `migrations/20261020_add_group_chats.sql`.
4. **Configure database credentials**
   - Copy/edit `php/env.php` with the correct local DB settings (Laragon default is usually `host=localhost`, `user=root`, empty password).

## 2. Allow other devices on the same network to access Laragon

1. **Find the host PC LAN IP address**
   - Open PowerShell and run:
     ```powershell
     ipconfig
     ```
   - Note the IPv4 address of your active network adapter (for example `192.168.1.25`).
2. **Make sure Apache is reachable from the network**
   - In Laragon, keep Apache running.
   - If you use Laragon's "Pretty URLs"/virtual hosts, you may need to access via IP + path (see below) or configure the virtual host to respond to the IP/hostname.
3. **Allow inbound HTTP traffic in Windows Firewall (if prompted/blocked)**
   - Allow Apache/`httpd.exe` (or TCP port 80/443, depending on your setup) for Private networks.
   - Only do this on trusted networks (home/school/lab LAN).

## 3. Open Nexagram from another device

On another phone/laptop connected to the **same Wi‑Fi/LAN**:

- Open a browser and go to one of:
  - `http://<HOST_LAN_IP>/nexagram-main/` (common when served from Laragon `www` root)
  - `http://<HOST_LAN_IP>/` (if Nexagram is mapped to the web root/vhost root)

Example:
- `http://192.168.1.25/nexagram-main/`

Each user should log in with their own account on their own device.

## 4. Chat requirements inside the app

- Users must be able to access the same database-backed app instance.
- Direct messages follow the app's existing rules (friends/requests/blocks as implemented).
- Uploaded images are stored under `uploads/` on the host PC; the `uploads/` directory must be writable by the web server.

## 5. Troubleshooting

- **Site not loading from other device**
  - Confirm both devices are on the same network.
  - Confirm host IP is correct (`ipconfig` can change on reconnect/DHCP).
  - Check Windows Firewall and that Apache is running.
  - Try temporarily disabling VPNs on either device.
- **Database connection error**
  - Verify `php/env.php` on the host PC.
  - `localhost` is correct for MySQL running on the same host PC as Apache.
- **Login/session issues**
  - Use the same base URL consistently (prefer the host LAN IP URL from all devices).
  - Clear cookies if switching between different hostnames/IPs.
- **Messages not updating**
  - Check browser devtools/network for errors calling `php/api_messages.php`.
  - Ensure system time is reasonable and PHP/MySQL are running.
- **Group chat errors/missing tables**
  - Run `migrations/20261020_add_group_chats.sql` against the `nexa` database.

## Notes

- This setup is intended for local/LAN use. For internet access you would need additional security hardening, HTTPS, proper host configuration, and deployment practices beyond this LAN guide.
