# Omnes MarketPlace

An e-commerce platform for the Omnes Education community built with native PHP, MySQL, HTML5, CSS3, and Vanilla JavaScript. Supports three purchase modes: **Buy It Now**, **Seller–Buyer Negotiation**, and **Best Offer (Auction)**.

---

## Demo Credentials

| Role            | Email                       | Password    |
|-----------------|-----------------------------|-----------  |
| Administrator   | <admin@omnesmp.com>         | Admin@1234  |
| Seller          | <seller@omnesmp.com>        | Seller@1234 |
| Buyer           | <buyer@omnesmp.com>         | Buyer@1234  |

> You can also create your own buyer account from the Register page.

---

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)
- A local server environment: [XAMPP](https://www.apachefriends.org/), [WAMP](https://www.wampserver.com/), [MAMP](https://www.mamp.info/), or [Laragon](https://laragon.org/)

---

## Getting Started

### 1. Place the project folder

Copy the `omnes_mp/` folder into your server's web root:

| Environment | Web root path                |
|-------------|------------------------------|
| XAMPP       | `C:/xampp/htdocs/`           |
| WAMP        | `C:/wamp64/www/`             |
| MAMP        | `/Applications/MAMP/htdocs/` |
| Laragon     | `C:/laragon/www/`            |

### 2. Create the database

1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Click **New** in the left sidebar
3. Name the database `omnes_marketplace` and click **Create**
4. Select the new database, go to the **Import** tab
5. Click **Choose File**, select `omnes_mp/includes/schema.sql`
6. Click **Go** — this creates all tables and seeds the 45 demo products

### 3. Configure the database connection

Open `omnes_mp/includes/db.php` and update the credentials if needed:

```php
define('DB_HOST', 'localhost');   // usually localhost
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password (blank by default in XAMPP)
define('DB_NAME', 'omnes_marketplace');
```

### 4. Open the site

Visit `http://localhost/omnes_mp/` in your browser. That's it.

---

## Folder Structure

```text
omnes_mp/
│
├── index.php               # Homepage: hero banner, all 3 category showcases, live auctions
├── products.php            # Browse All — filterable by category and sale type
├── product.php             # Single product detail page with purchase actions
├── login.php               # Login page
├── register.php            # Buyer self-registration
├── cart.php                # Shopping cart (Buy It Now items)
├── checkout.php            # Delivery info + payment form
├── account.php             # User profile, order history, bid tracker
├── notifications.php       # Notification feed + item alert preferences
├── admin.php               # Admin dashboard: sellers, auctions, negotiations, all listings
├── seller.php              # Seller dashboard: list items, respond to buyer offers
│
├── assets/
│   ├── images/             # Logos and UI icons (add your own here)
│   ├── uploads/            # Product images uploaded by sellers
│   ├── styles/
│   │   └── main.css        # Full site stylesheet (purple theme, no inline styles)
│   └── scripts/
│       ├── main.js         # Global JS: filters, countdown timers, validation
│       └── seller.js       # Seller form: auction date field toggle
│
├── includes/
│   ├── db.php              # MySQL connection (edit credentials here)
│   ├── sessions.php        # Session helpers: isLoggedIn(), isAdmin(), isSeller()
│   ├── functions.php       # Shared helpers: getProducts(), formatPrice(), sanitize()
│   ├── logout.php          # Clears session and redirects to homepage
│   └── schema.sql          # Full database schema + all seed data (run this first)
│
└── templates/
    ├── header.php          # Sticky navigation bar, flash messages, Google Fonts
    └── footer.php          # Footer links, script loader
```

---

## How Each Purchase Mode Works

**Buy It Now** — Buyer adds item to cart, fills in delivery and payment info, clicks Place Order. Item is marked as sold immediately.

**Negotiation** — Buyer submits an offer on the product page (up to 5 rounds). The seller sees incoming offers in their dashboard and can accept or send a counter-offer. If accepted, the buyer is notified and the item is marked sold.

**Best Offer (Auction)** — Item has a start and end datetime. Buyers place a maximum bid; the platform auto-bids on their behalf. The admin reviews bids in the dashboard and clicks Award Winner when the auction ends.

---

## User Roles

| Role          | Can do                                                                 |
|---------------|------------------------------------------------------------------------|
| **Buyer**     | Browse, add to cart, buy, negotiate, bid, set notification alerts      |
| **Seller**    | List products (all 3 sale types), remove listings, respond to offers   |
| **Admin**     | Everything above + add/remove sellers, award auctions, delete any item |

---

## Adding Product Images

1. Place image files (JPG, PNG, WebP) into `assets/uploads/`
2. When logged in as a seller or admin, use the listing form on `seller.php` to upload images directly — they are saved to `assets/uploads/` automatically
3. Products without an image display a category emoji placeholder

---

## Common Issues

**Blank page or PHP errors**
Make sure your PHP version is 7.4+. Check the Apache/PHP error log in your XAMPP/WAMP control panel.

**"Connection failed" error**
Open `includes/db.php` and double-check your `DB_USER` and `DB_PASS`. In XAMPP the default is `root` with no password.

**Images not showing after upload**
Check that the `assets/uploads/` folder exists and is writable. On Linux/Mac: `chmod 755 assets/uploads/`

**Session not persisting**
Make sure `session_start()` is not called twice. All session logic is handled by `includes/sessions.php` — do not call `session_start()` in individual pages.

---

## Technologies Used

- **PHP** — server-side logic, routing, form handling
- **MySQL** — database via `mysqli` with prepared statements
- **HTML5** — semantic structure
- **CSS3** — custom properties, grid, flexbox (no frameworks)
- **Vanilla JavaScript** — filtering, countdown timers, form validation

---

## Project Info

Course: Web Development B1 2026
Platform: Omnes Education
