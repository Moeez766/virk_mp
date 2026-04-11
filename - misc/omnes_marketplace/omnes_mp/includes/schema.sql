-- ============================================================
-- Omnes MarketPlace – Database Schema
-- Run this file to create and seed the database
-- ============================================================

CREATE DATABASE IF NOT EXISTS omnes_marketplace
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE omnes_marketplace;

-- -------------------------------------------------------
-- USERS  (administrators, sellers, buyers)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','seller','buyer') NOT NULL DEFAULT 'buyer',
    first_name    VARCHAR(60),
    last_name     VARCHAR(60),
    phone         VARCHAR(30),
    address_line1 VARCHAR(150),
    address_line2 VARCHAR(150),
    city          VARCHAR(80),
    postal_code   VARCHAR(20),
    country       VARCHAR(80),
    profile_photo VARCHAR(255),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PRODUCTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    seller_id     INT NOT NULL,
    name          VARCHAR(200) NOT NULL,
    description   TEXT,
    defects       TEXT,
    price         DECIMAL(10,2) NOT NULL,
    category      ENUM('rare','high_end','regular') NOT NULL,
    sale_type     ENUM('buy_now','negotiation','best_offer') NOT NULL DEFAULT 'buy_now',
    image         VARCHAR(255),
    video_url     VARCHAR(255),
    auction_start DATETIME,
    auction_end   DATETIME,
    is_sold       TINYINT(1) DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- CART
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDERS  (completed purchases)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id       INT NOT NULL,
    product_id     INT NOT NULL,
    final_price    DECIMAL(10,2) NOT NULL,
    purchase_type  ENUM('buy_now','negotiation','best_offer') NOT NULL,
    payment_method ENUM('visa','mastercard','amex','paypal') NOT NULL,
    card_last4     VARCHAR(4),
    status         ENUM('pending','completed','cancelled') DEFAULT 'completed',
    ordered_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- NEGOTIATIONS  (seller–buyer chat)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS negotiations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    product_id   INT NOT NULL,
    buyer_id     INT NOT NULL,
    round        INT DEFAULT 1,                          -- 1 to 5
    buyer_offer  DECIMAL(10,2),
    seller_reply DECIMAL(10,2),
    status       ENUM('pending','accepted','rejected','countered') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id)   REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- BIDS  (best offer / auction)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS bids (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    bidder_id   INT NOT NULL,
    bid_amount  DECIMAL(10,2) NOT NULL,
    max_amount  DECIMAL(10,2) NOT NULL,   -- buyer's private max
    is_winner   TINYINT(1) DEFAULT 0,
    placed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (bidder_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- NOTIFICATIONS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    link       VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- NOTIFICATION PREFERENCES  (alert when item matches criteria)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification_prefs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    keyword    VARCHAR(150),
    category   ENUM('rare','high_end','regular'),
    max_price  DECIMAL(10,2),
    sale_type  ENUM('buy_now','negotiation','best_offer'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin account  (password: Admin@1234)
INSERT INTO users (username, email, password_hash, role, first_name, last_name)
VALUES ('admin', 'admin@omnesmp.com',
        '$2y$10$Vq3Q5X8kL2mN9oP1rS7uOe0gYzXwJhKlMnBvCdTfEaQbRsWpZiUy',
        'admin', 'Admin', 'Omnes');

-- Demo seller  (password: Seller@1234)
INSERT INTO users (username, email, password_hash, role, first_name, last_name)
VALUES ('seller_demo', 'seller@omnesmp.com',
        '$2y$10$Vq3Q5X8kL2mN9oP1rS7uOe0gYzXwJhKlMnBvCdTfEaQbRsWpZiUy',
        'seller', 'Sophie', 'Martin');

-- Demo buyer  (password: Buyer@1234)
INSERT INTO users (username, email, password_hash, role, first_name, last_name,
                   address_line1, city, postal_code, country, phone)
VALUES ('buyer_demo', 'buyer@omnesmp.com',
        '$2y$10$Vq3Q5X8kL2mN9oP1rS7uOe0gYzXwJhKlMnBvCdTfEaQbRsWpZiUy',
        'buyer', 'Lucas', 'Bernard',
        '12 Rue de Rivoli', 'Paris', '75001', 'France', '+33 6 12 34 56 78');

-- -------------------------------------------------------
-- REGULAR items (seller_id = 2 = seller_demo)
-- -------------------------------------------------------
INSERT INTO products (seller_id, name, description, price, category, sale_type) VALUES
(2, 'Minimalist Ceramic Mug',      'Matte finish 350ml stoneware mug.',                          12.00,  'regular', 'buy_now'),
(2, 'Scented Soy Candle',          'Lavender and eucalyptus natural wax.',                        18.00,  'regular', 'buy_now'),
(2, 'Cotton Throw Blanket',        'Woven breathable cotton for sofa or bed.',                    25.00,  'regular', 'buy_now'),
(2, 'Geometric Wall Planter',      'Modern indoor hanging pot for succulents.',                   15.00,  'regular', 'buy_now'),
(2, 'Linen Napkin Set (4pcs)',     'Eco-friendly soft linen table linens.',                       22.00,  'regular', 'buy_now'),
(2, 'Cork Coaster Set',            'Set of 6 moisture-absorbent natural cork coasters.',          10.00,  'regular', 'buy_now'),
(2, 'Glass Bud Vase',              'Clear cylindrical vase for single stems.',                    14.00,  'regular', 'buy_now'),
(2, 'Bamboo Picture Frame',        'Sustainable wood frame for 10x15cm photos.',                  19.00,  'regular', 'buy_now'),
(2, 'Macrame Wall Hanging',        'Hand-knotted cotton rope bohemian decor.',                    28.00,  'regular', 'buy_now'),
(2, 'Woven Seagrass Basket',       'Foldable storage basket for plants.',                         24.00,  'regular', 'buy_now'),
(2, 'Desktop Terrarium',           'Teardrop glass container for moss and ferns.',                20.00,  'regular', 'buy_now'),
(2, 'Decorative Bookends',         'Heavy marble-finish L-shaped supports.',                      30.00,  'regular', 'buy_now'),
(2, 'Microfiber Area Rug',         'Soft non-slip mat for entryways.',                            35.00,  'regular', 'buy_now'),
(2, 'Metal Wire Fruit Bowl',       'Black powder-coated kitchen centerpiece.',                    16.00,  'regular', 'buy_now'),
(2, 'Incense Holder Tray',         'Ceramic leaf-shaped tray.',                                   12.00,  'regular', 'buy_now');

-- -------------------------------------------------------
-- HIGH-END items — mix of buy_now and negotiation
-- -------------------------------------------------------
INSERT INTO products (seller_id, name, description, price, category, sale_type) VALUES
(2, 'Hand-Blown Glass Vase',       'Artisanal centerpiece with unique swirls.',                  120.00,  'high_end', 'buy_now'),
(2, 'Solid Oak Side Table',        'Mid-century modern bedside or lounge table.',                280.00,  'high_end', 'negotiation'),
(2, 'Alpaca Wool Throw',           'Ultra-soft sustainable wool from Peru.',                     150.00,  'high_end', 'buy_now'),
(2, 'Smart Ambient Lamp',          'Dimmable LED with app control.',                             195.00,  'high_end', 'buy_now'),
(2, 'Velvet Accent Chair',         'Plush seating with gold-finished legs.',                     450.00,  'high_end', 'negotiation'),
(2, 'Marble Dining Centerpiece',   'Large Carrara marble decorative bowl.',                      110.00,  'high_end', 'buy_now'),
(2, 'Abstract Oil Painting',       'Original canvas with textured brushwork.',                   320.00,  'high_end', 'negotiation'),
(2, 'Cast Iron Wall Clock',        'Industrial design with silent movement.',                    145.00,  'high_end', 'buy_now'),
(2, 'Leather Desk Mat',            'Full-grain tan leather workspace accessory.',                  85.00,  'high_end', 'buy_now'),
(2, 'Sculptural Floor Mirror',     'Full-length arched mirror with brass frame.',                380.00,  'high_end', 'negotiation'),
(2, 'Ceramic Dinnerware Set',      '16-piece handcrafted stoneware service.',                    210.00,  'high_end', 'buy_now'),
(2, 'Crystal Decanter Set',        'Lead-free crystal bottle with 4 tumblers.',                  165.00,  'high_end', 'buy_now'),
(2, 'Indoor Olive Tree',           '150cm tall tree in a premium terracotta pot.',               130.00,  'high_end', 'buy_now'),
(2, 'Cashmere Pillow Set',         'Pair of knitted Mongolian cashmere covers.',                 140.00,  'high_end', 'buy_now'),
(2, 'Floating Wall Shelf',         'Minimalist walnut wood with hidden brackets.',                 95.00,  'high_end', 'buy_now');

-- -------------------------------------------------------
-- RARE items — best_offer (auction) + some negotiation
-- -------------------------------------------------------
INSERT INTO products (seller_id, name, description, price, category, sale_type,
                       auction_start, auction_end) VALUES
(1, 'Antique Gilded Mirror (19th C.)', 'Authentic French restoration with gold leaf.',
    1200.00, 'rare', 'best_offer',
    '2026-04-10 09:00:00', '2026-04-17 18:00:00'),
(1, 'Hand-Knotted Silk Rug',           'Persian design with 1,000 knots per inch.',
    2500.00, 'rare', 'best_offer',
    '2026-04-10 09:00:00', '2026-04-20 18:00:00'),
(1, 'Signed Ceramic Sculpture',        'Limited edition piece from a renowned artist.',
     850.00, 'rare', 'negotiation', NULL, NULL),
(1, 'Fossilized Wood Coffee Table',    'Petrified wood slab top.',
    3200.00, 'rare', 'best_offer',
    '2026-04-11 10:00:00', '2026-04-18 20:00:00'),
(1, 'Vintage Bauhaus Desk Lamp',       'Original 1930s fixture in mint condition.',
     950.00, 'rare', 'negotiation', NULL, NULL),
(1, 'Reclaimed Monastery Door',        'Hand-carved 18th-century oak entry piece.',
    1800.00, 'rare', 'best_offer',
    '2026-04-12 09:00:00', '2026-04-19 18:00:00'),
(1, 'Murano Glass Chandelier',         'Multicolored hand-blown Venetian glass.',
    4500.00, 'rare', 'best_offer',
    '2026-04-10 09:00:00', '2026-04-21 22:00:00'),
(1, 'Meteorite Fragment Display',      'Certified fragment on a custom stand.',
    1100.00, 'rare', 'best_offer',
    '2026-04-13 10:00:00', '2026-04-20 18:00:00'),
(1, 'First Edition Architecture Folio','Leather-bound rare book.',
     750.00, 'rare', 'negotiation', NULL, NULL),
(1, 'Edo Period Japanese Screen',      'Hand-painted 4-panel divider with gold leaf.',
    5000.00, 'rare', 'best_offer',
    '2026-04-10 09:00:00', '2026-04-24 18:00:00'),
(1, 'Limited Bronze Figurine',         'Numbered 1 of 50 cast bronze sculpture.',
    1400.00, 'rare', 'best_offer',
    '2026-04-14 10:00:00', '2026-04-21 18:00:00'),
(1, 'Antique Nautical Astrolabe',      'Brass maritime instrument from the 1700s.',
    2100.00, 'rare', 'best_offer',
    '2026-04-10 09:00:00', '2026-04-17 20:00:00'),
(1, 'Hand-Woven Tapestry',             'Large-scale medieval style wall art.',
    3800.00, 'rare', 'negotiation', NULL, NULL),
(1, 'Petrified Coral Specimen',        'Large, legally sourced deep-sea centerpiece.',
     600.00, 'rare', 'negotiation', NULL, NULL),
(1, 'Victorian Wrought Iron Gate',     'Intricate salvaged gate from a historic estate.',
    1600.00, 'rare', 'best_offer',
    '2026-04-15 10:00:00', '2026-04-22 18:00:00');

-- Sample bids on first auction item
INSERT INTO bids (product_id, bidder_id, bid_amount, max_amount) VALUES
(31, 3, 1100.00, 1350.00),
(32, 3, 2200.00, 2800.00);

-- Sample notifications
INSERT INTO notifications (user_id, message, link) VALUES
(3, 'Your bid on "Antique Gilded Mirror" is currently the highest!', 'product.php?id=31'),
(3, 'A new Rare item has been listed: Edo Period Japanese Screen.', 'product.php?id=40');
