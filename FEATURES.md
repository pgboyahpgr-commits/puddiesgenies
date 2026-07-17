# SmakAI — Complete Feature List & Implementation Plan

## Overview
Cartoon-themed smart restaurant ordering system for InfinityFree (PHP).
Features AI waiter, real-time chat, WebRTC calls, per-table stream control.

---

## 🏗️ Core Architecture

### Stack
| Layer | Technology |
|-------|-----------|
| Frontend | Tailwind CSS v4 (CDN) + Vanilla JS + GSAP (CDN) |
| Backend | PHP 8 (JSON flat-file storage) |
| Icons | IconBuddy CDN (`https://files.svgcdn.io/{set}/{icon}.svg`) |
| AI | g4f.dev (multi-model: `gpt-4.1` → `gpt-4o` → `deepseek-v3` → `gemini-1.5-pro`) |
| Video search | `ytapis.djalokyt27.workers.dev/search?q=` |
| Images | JSON Unsplash URLs + Wikimedia Commons lazy-upgrade |
| Voice calls | PeerJS (WebRTC P2P, free) |
| Storage | JSON flat-files (`data/*.json`) + localStorage (cart integrity) |

### Directory Structure
```
public_html/
├── index.php                 # QR → token → PHP session → menu
├── menu.php                  # Menu grid + cart
├── akinator.php              # AI waiter
├── checkout.php              # Cart + payment choice
├── invoice.php               # Bill
├── kitchen.php               # Per-table kitchen stream
├── chat.php                  # Table chat + call button
├── call.php                  # WebRTC voice call (PeerJS)
├── food-vlogs.php            # YouTube vlogs via worker
├── assets/
│   ├── style.css             # Cartoon theme + Tailwind
│   ├── script.js             # Cart logic + GSAP
│   ├── akinator.js           # Decision tree engine
│   └── chat.js               # Chat polling + call logic
├── includes/
│   ├── header.php            # CDN links, nav, GSAP init
│   ├── footer.php            # Footer + scripts
│   ├── auth.php              # Admin session check
│   └── image-fetcher.php     # Commons API handler
├── data/
│   ├── menu.json             # 1000+ dishes (from indian_menu.json)
│   ├── orders.json           # All orders
│   ├── tables.json           # Table tokens + stream/chat toggles
│   ├── admin.json            # Admin credentials + settings
│   ├── stream_config.json    # Stream URL + ON/OFF
│   ├── image_cache.json      # Commons image URLs
│   ├── call_requests.json    # Pending VoIP call requests
│   └── chat/                 # Per-table message files
│       ├── table_1.json
│       └── ...
└── admins/                   # Secret admin directory
    ├── index.php             # Login page
    ├── dashboard.php         # Overview
    ├── orders.php            # Orders CRUD
    ├── menu.php              # Menu CRUD
    ├── stream.php            # Stream URL + toggle
    ├── tables.php            # Table CRUD + toggles
    ├── chat.php              # Admin inbox + replies
    ├── call.php              # Accept WebRTC calls
    ├── update-images.php     # Batch Commons image fetcher
    ├── settings.php          # Change creds + clear data
    └── logout.php            # Destroy session
```

---

## 📋 Feature Modules

### 1. QR → Session System (`index.php`)
- Each table gets a QR code with a **secret token**: `/menu?t=x7k9m2a4`
- PHP reads token → resolves table number from `data/tables.json`
- Table number stored in **PHP session** (`$_SESSION['table']`)
- No visible `table=5` in URL
- Anti-tamper: localStorage checksum + server-authoritative table number

### 2. Smart Menu (`menu.php`)
- Reads `data/menu.json` — 1000+ items across 10 categories
- Category tabs + search bar + spice/veg filters
- Cart via localStorage with integrity checksum
- **Image system:**
  - Shows JSON image URL immediately + gradient overlay with dish name
  - Background JS fetches Wikimedia Commons for a better image
  - If found → silently swaps image src (cached forever in `data/image_cache.json`)
  - All dishes always show an image instantly

### 3. AI Akinator Waiter (`akinator.php` + `akinator.js`)
- 6 questions narrowing 1000+ dishes:
  ```
  Q1: Veg or Non-Veg?
  Q2: Spicy, Medium, or Mild?
  Q3: Main Course, Snack, Dessert, or Drink?
  Q4: Which category — Biryani, Curry, South Indian, etc?
  Q5: Region preference?
  Q6: Price range?
  ```
- Backend: g4f.dev (multi-model fallback chain)
- "Try Again" eliminates wrong dish, continues questioning
- Shows guessed dish + 4 related items + "Browse Menu" CTA

### 4. Checkout + Invoice (`checkout.php` + `invoice.php`)
- Cart review with quantities, prices, totals
- Custom instructions textarea per item
- Two payment buttons:
  - **Pay Online (UPI)** → simulated → invoice with **PAID ✓** badge
  - **Pay at Counter** → popup → invoice with **Pay at Counter** badge
- Order written to `data/orders.json` with table number, items, total, status, payment mode
- Anti-tamper: checksum validated at checkout time

### 5. Kitchen Stream (`kitchen.php` + `admins/stream.php`)
- Admin sets YouTube embed URL + toggle ON/OFF
- Admin can enable/disable stream **per table** (only tables with active orders see the stream)
- When OFF → iframe set to `about:blank` (fully stops video)
- Kitchen page checks `data/tables.json` for `stream_enabled` per session table
- Auto-refreshes every 2 seconds via JS fetch (no visible flicker)

### 6. Admin Panel (`admins/`)
| Page | Features |
|------|----------|
| **Login** | ID + password → PHP session (default: `admin` / `smak123`) |
| **Dashboard** | Orders today, pending count, revenue, stream status, recent 5 orders |
| **Orders** | Table of all orders — view, Mark Preparing, Mark Delivered, Delete |
| **Menu** | CRUD for 1000+ dishes — add/edit/delete with all fields |
| **Stream** | YouTube URL input + ON/OFF toggle + preview iframe |
| **Tables** | List all tables — add/remove, toggle stream & chat per table, view QR codes |
| **Chat** | Inbox with all table conversations + reply inline + see call requests |
| **Call** | Answer incoming WebRTC calls from tables |
| **Update Images** | One-click batch fetch Commons images for all dishes |
| **Settings** | Change admin credentials, clear orders, view system info |
| **Logout** | Destroy session → redirect to login |

### 7. Real-Time Chat (`chat.php` + `admins/chat.php`)
- **Per-table** JSON file: `data/chat/table_{id}.json`
- JS polls every **1 second** using `?after=last_msg_id` — only new messages
- No page refresh, no flicker — pure async DOM updates
- Customer sees: message history, text input, send button
- Admin sees: all tables listed, click to expand and reply
- Sound notification for new messages (optional)

### 8. Voice Call (WebRTC via PeerJS) (`call.php`)
- Customer clicks **"Call Restaurant"** button in chat UI
- Generates a PeerJS room ID → writes to `data/call_requests.json`
- Admin sees incoming call request in chat panel (polls)
- Admin clicks **Accept** → both redirected to `call.php?room=X`
- PeerJS handles P2P audio (browser-to-browser, completely free)
- No accounts needed — PeerJS Cloud handles signaling

### 9. Food Vlogs (`food-vlogs.php`)
- Uses worker: `https://ytapis.djalokyt27.workers.dev/search?q=<dish>+food+vlog`
- Auto-section: when a dish is ordered, shows related vlogs
- Manual search bar for any food query
- Grid view (thumbnail + title + author) + click to watch via iframe
- Same architecture as YT Hackthon reference

### 10. Image System (`includes/image-fetcher.php`)
- **Layer 1:** Show JSON image URL immediately with dish name overlay + category gradient tint
- **Layer 2:** Background JS calls `image-fetcher.php?dish=Name`
- **Layer 3:** PHP checks `data/image_cache.json` → if not found, calls Wikimedia Commons API
- **Layer 4:** Commons URL cached forever in `image_cache.json`
- **Fallback:** If Commons returns nothing, keep the original JSON URL

---

## 🎨 Cartoon Theme

| Element | Implementation |
|---------|---------------|
| Background | Cream `#FFF8F0` + animated SVG blobs |
| Cards | Glassmorphism `backdrop-filter: blur(12px)` |
| Buttons | Border-radius `100vw`, bouncy hover `cubic-bezier(0.68,-0.55,0.27,1.55)` |
| GSAP | Page load stagger, card hover scale, smooth scroll |
| Fonts | Fredoka (headings) + Nunito (body) via Google Fonts |
| Icons | IconBuddy CDN — `solar`, `mdi`, `twemoji`, `svg-spinners` sets |
| Spinners | `svg-spinners` for loading states |
| Colors | Coral `#FF6B6B`, Mint `#4ECDC4`, Yellow `#FFE66D`, Soft Black `#2D3436` |

---

## 🔒 Security

| Concern | Solution |
|---------|----------|
| Admin URL hidden | Secret path `/admins/` — returns 404 if unknown |
| Admin login | ID + password → PHP session |
| Table tampering | Table number stored in PHP session (not URL) |
| Cart tampering | localStorage checksum validated at checkout |
| Order integrity | Orders written server-side via PHP POST |

---

## 📦 Data Files (auto-generated defaults)

### `data/admin.json`
```json
{"id":"admin","password":"smak123"}
```

### `data/tables.json`
```json
{"tables":[{"number":1,"token":"x7k9m2a4","stream_enabled":false,"chat_enabled":true}]}
```

### `data/stream_config.json`
```json
{"video_url":"","video_status":"off","last_updated":""}
```

### `data/orders.json`
```json
[]
```

### `data/image_cache.json`
```json
{}
```

### `data/call_requests.json`
```json
[]
```

---

## 🚀 Build Order

| Step | Files | Est. Time |
|------|-------|-----------|
| 1 | Core structure — header, footer, style, script, data defaults | 45 min |
| 2 | Menu page + cart (localStorage + checksum) | 60 min |
| 3 | Checkout + invoice + order submission | 45 min |
| 4 | AI akinator waiter + decision tree | 45 min |
| 5 | Admin login + dashboard + orders | 60 min |
| 6 | Admin menu CRUD + tables CRUD | 45 min |
| 7 | Admin stream control + kitchen page | 30 min |
| 8 | Chat system + admin chat inbox | 45 min |
| 9 | Voice call with PeerJS | 30 min |
| 10 | Food vlogs + image fetcher + polish | 30 min |
| | **Total** | **~7 hours** |
