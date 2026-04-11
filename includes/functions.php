<?php
// Sanitize user input to prevent XSS
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Format price in euros
function formatPrice($price) {
    return '€' . number_format((float)$price, 2, '.', ',');
}

// Get category badge label
function getCategoryLabel($category) {
    $labels = [
        'rare'     => '✦ Rare',
        'high_end' => '◈ High-End',
        'regular'  => '● Regular',
    ];
    return $labels[$category] ?? ucfirst($category);
}

// Get sale type badge label
function getSaleTypeLabel($sale_type) {
    $labels = [
        'buy_now'     => 'Buy It Now',
        'negotiation' => 'Negotiation',
        'best_offer'  => 'Best Offer',
    ];
    return $labels[$sale_type] ?? ucfirst($sale_type);
}

// Get all products, optionally filtered by category or sale type
function getProducts($conn, $category = null, $sale_type = null, $limit = null) {
    $sql = "SELECT p.*, u.username AS seller_name 
            FROM products p 
            LEFT JOIN users u ON p.seller_id = u.id 
            WHERE p.is_sold = 0";

    $params = [];
    $types  = '';

    if ($category) {
        $sql    .= " AND p.category = ?";
        $params[] = $category;
        $types   .= 's';
    }
    if ($sale_type) {
        $sql    .= " AND p.sale_type = ?";
        $params[] = $sale_type;
        $types   .= 's';
    }

    $sql .= " ORDER BY p.created_at DESC";

    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types   .= 'i';
    }

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get a single product by ID
function getProductById($conn, $id) {
    $stmt = $conn->prepare(
        "SELECT p.*, u.username AS seller_name 
         FROM products p 
         LEFT JOIN users u ON p.seller_id = u.id 
         WHERE p.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get cart items for current user
function getCartItems($conn, $user_id) {
    $stmt = $conn->prepare(
        "SELECT c.*, p.name, p.price, p.image, p.category, p.sale_type 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.user_id = ?"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get cart item count for current user
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['cnt'];
}

// Get notification count for current user
function getNotificationCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)$row['cnt'];
}

// Get all users of a specific role
function getUsersByRole($conn, $role) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get active auctions (best offer items still open)
function getActiveAuctions($conn) {
    $sql = "SELECT p.*, u.username AS seller_name,
                   (SELECT MAX(bid_amount) FROM bids WHERE product_id = p.id) AS highest_bid,
                   (SELECT COUNT(*) FROM bids WHERE product_id = p.id) AS bid_count
            FROM products p 
            LEFT JOIN users u ON p.seller_id = u.id 
            WHERE p.sale_type = 'best_offer' AND p.is_sold = 0
            ORDER BY p.auction_end DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get negotiations for a product
function getNegotiations($conn, $product_id) {
    $stmt = $conn->prepare(
        "SELECT n.*, u.username AS buyer_name 
         FROM negotiations n 
         JOIN users u ON n.buyer_id = u.id 
         WHERE n.product_id = ? 
         ORDER BY n.created_at DESC"
    );
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Truncate text for product cards
function truncateText($text, $length = 80) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '…';
}
?>
