# SmakAI — Smart Restaurant Ordering System

A cartoon-themed, AI-powered restaurant ordering system for a hackathon. Runs entirely on InfinityFree (PHP 8, no MySQL) with flat-file JSON storage.

**Live site:** [https://puddisgenies.ct.ws/](https://puddisgenies.ct.ws/)  
**Admin panel:** [https://puddisgenies.ct.ws/admins/](https://puddisgenies.ct.ws/admins/) (login: `admin` / `smak123`)

---

## Features

### For Customers
- **Digital Menu** — Browse 865+ dishes across 33 categories, fetched from GitHub Gist (client-side), with images and tags
- **AI Waiter** (Akinator-style) — Answer questions and get dish recommendations via multi-model AI (GPT-4.1 → DeepSeek → Gemini)
- **Cart & Checkout** — Add items to cart (localStorage with integrity checksum), add instructions, pay online (UPI) or at counter
- **Table Chat** — Real-time messaging with restaurant staff (table-specific chat rooms)
- **WebRTC Calls** — One-click call request to restaurant, P2P video/audio via PeerJS
- **Kitchen Stream** — Live YouTube stream embed so customers can watch their food being prepared
- **Food Vlogs** — YouTube search and watch food-related videos
- **Invoice** — Order confirmation with payment status

### For Admin (`/admins/`)
- **Dashboard** — Today's orders, pending count, revenue, stream status
- **Order Management** — View, filter, update status (pending → preparing → delivered), delete
- **Menu Editor** — Full CRUD on menu items, saves back to GitHub Gist
- **Table Manager** — Add/delete tables, generate tokens, toggle stream/chat per table, **QR code generation** with print
- **Chat Inbox** — View and reply to customer chat messages (auto-refresh every 2s)
- **Call Requests** — Answer incoming WebRTC call requests
- **Stream Config** — Set YouTube live stream URL for kitchen page
- **Settings** — Change admin credentials, clear orders, sync menu from Gist, batch update dish images

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Hosting | InfinityFree (Apache + PHP 8) |
| Backend | PHP 8 (no framework, flat files) |
| Frontend | Vanilla JS + Tailwind CSS (CDN) |
| Animations | GSAP (ScrollTrigger, back.out easing) |
| Fonts | Fredoka (headings) + Nunito (body) via Google Fonts |
| Icons | SVG CDN (`https://files.svgcdn.io/solar/*.svg`) |
| Menu Data | GitHub Gist (public JSON) |
| AI | `g4f.dev` API (multi-model fallback) |
| WebRTC | PeerJS (free P2P) |
| QR Codes | `api.qrserver.com` |
| YouTube | Cloudflare Worker `ytapis.djalokyt27.workers.dev` |
| Images | Wikimedia Commons API (batch upgrade) |

---

## File Structure

```
public_html/
├── .htaccess                    # Empty (all 403 issues fixed)
├── index.php                    # Landing portal with 6 buttons
├── menu.php                     # Client-side menu (fetches from Gist)
├── akinator.php                 # AI waiter recommendation
├── checkout.php                 # Cart review + place order
├── invoice.php                  # Order confirmation
├── kitchen.php                  # Kitchen live stream
├── chat.php                     # Customer chat interface
├── call.php                     # WebRTC call page
├── food-vlogs.php               # YouTube food vlog search
│
├── assets/
│   ├── style.css                # Glassmorphism + cartoon theme
│   ├── script.js                # Cart, toast, GSAP, image upgrade
│   ├── chat.js                  # Chat polling + send + call request
│   ├── akinator.js              # AI waiter UI logic
│   └── favicon/                 # Site icons
│
├── includes/
│   ├── header.php               # Session start, table token, nav bar
│   ├── footer.php               # Footer + floating cart button + GSAP
│   ├── auth.php                 # Admin auth + loadJSON/saveJSON helpers
│   ├── read-json.php            # Triple-fallback file reader
│   ├── config.php               # GitHub token + Gist URL constants
│   ├── menu-loader.php          # Gist fetch/cache/update functions
│   └── image-fetcher.php        # Wikimedia Commons image search
│
├── data/
│   ├── menu.json                # Full menu (865 dishes, local cache)
│   ├── tables.json              # Table config + tokens
│   ├── orders.json              # Order history
│   ├── admin.json               # Admin credentials
│   ├── stream_config.json       # YouTube stream URL + status
│   ├── image_cache.json         # Cached dish images
│   ├── call_requests.json       # Pending call requests
│   ├── save-order.php           # Order save endpoint
│   └── chat/
│       ├── send.php             # Chat message send endpoint
│       ├── read.php             # Chat message read endpoint
│       ├── request-call.php     # Call request endpoint
│       └── table_X.json         # Per-table chat messages
│
└── admins/
    ├── index.php                # Admin login page
    ├── dashboard.php            # Stats dashboard
    ├── orders.php               # Order CRUD
    ├── menu.php                 # Menu editor (Gist-backed)
    ├── tables.php               # Table manager + QR codes
    ├── chat.php                 # Admin chat inbox
    ├── call.php                 # Admin call answer
    ├── stream.php               # Kitchen stream config
    ├── stream-status.php        # Stream API endpoint
    ├── update-images.php        # Batch image fetch
    ├── settings.php             # Credentials + data management
    ├── sync-menu.php            # Force Gist sync
    └── logout.php               # Admin logout
```

---

## Setup

### 1. Clone / Upload
Copy the entire `public_html/` folder contents into InfinityFree's `htdocs/` directory via **FTP (FileZilla)**.

### 2. Permissions
```bash
# Data directories must be writable
chmod -R 755 public_html/data/
chmod -R 755 public_html/data/chat/

# PHP files should be 644
find public_html -name "*.php" -exec chmod 644 {} \;
```

### 3. Configuration
Edit `includes/config.php`:
```php
define('GIST_URL', 'https://gist.githubusercontent.com/.../raw/menu.json');
define('GITHUB_TOKEN', 'ghp_your_token_here');  // For admin menu edits
```

### 4. Initial Data
Ensure these files exist in `data/`:
- `admin.json` — `{"id":"admin","password":"smak123"}`
- `tables.json` — `{"tables":[{"number":1,"token":"your_token","stream_enabled":false,"chat_enabled":true}]}`
- `orders.json` — `[]`
- `stream_config.json` — `{"streams":[],"youtube_enabled":true}`

### 5. Visit
- Customer: `https://yoursite.ct.ws/`
- Admin: `https://yoursite.ct.ws/admins/`

---

## Table Token System

Each table gets a **secret URL token** (not a simple number). This prevents customers from accessing other tables' data.

Table URLs look like: `/menu?t=x7k9m2a4`

The token is processed in `header.php` on **every page** — so the session persists across menu, checkout, chat, etc.

To generate new tokens: **Admin → Tables** → click **New Token** (old QR codes stop working immediately).

---

## Menu Source

The menu is stored on **GitHub Gist** (public JSON). The customer-facing menu page fetches it **client-side** via browser `fetch()` — no PHP file I/O needed.

Admin menu edits use a PHP proxy (`menu-loader.php`) that reads/writes the Gist via the GitHub API with a personal access token.

**Local fallback:** `data/menu.json` is used if the Gist is unreachable.

---

## Chat System

- Chat files are stored as `data/chat/table_X.json` (one per table + `table_0.json` for guests)
- PHP proxy files (`send.php`, `read.php`) handle reads/writes — no direct JSON access
- Client polls every 1.5 seconds for new messages
- Admin panel auto-refreshes every 2 seconds
- Guest/non-token users share `table_0.json`

---

## WebRTC Calls

1. Customer clicks **Call Restaurant** in chat
2. POST to `request-call.php` creates a call request with a room ID
3. Admin sees pending call in **Chat Inbox** with **Answer** link
4. Call opens via PeerJS P2P connection in `call.php?room=...`

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| **403 Forbidden** | Delete all `.htaccess` files from server; re-upload clean files |
| **Menu not loading** | Check Gist URL in `config.php`; browser console shows fetch errors |
| **Chat not working** | Ensure `data/chat/` is writable (755); check `send.php` response in DevTools |
| **Admin login fails** | Verify `data/admin.json` exists with correct credentials |
| **file_get_contents blocked** | InfinityFree blocks external URLs; `read-json.php` uses triple fallback |
| **Blank white page** | PHP syntax error — check InfinityFree error log in cPanel |

---

## License

Built for a hackathon project. Free to use and modify.
