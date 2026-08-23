USE ecommerce_platform;

INSERT INTO CUSTOMER (
    name,
    email,
    password,
    phone,
    address,
    preferred_payment,
    preferred_delivery
) VALUES
(
    '王小明',
    'xiaoming@example.com',
    '$2y$10$B14L97nU6Pd2Q5dgdcmdZeaNQZ78Wt79CUSWPhWgdlykdh/UbjYry',
    '0912345678',
    '高雄市鼓山區大學路1號',
    'credit_card',
    'home_delivery'
),
(
    '陳小華',
    'xiaohua@example.com',
    '$2y$10$B14L97nU6Pd2Q5dgdcmdZeaNQZ78Wt79CUSWPhWgdlykdh/UbjYry',
    '0923456789',
    '高雄市左營區博愛路100號',
    'atm',
    'convenience_store'
),
(
    '林小美',
    'xiaomei@example.com',
    '$2y$10$B14L97nU6Pd2Q5dgdcmdZeaNQZ78Wt79CUSWPhWgdlykdh/UbjYry',
    '0934567890',
    '高雄市三民區建國路200號',
    'post_office',
    'home_delivery'
);

INSERT INTO STORE (
    store_name,
    store_url,
    email,
    password,
    owner_name,
    phone,
    status
) VALUES
(
    '小明選物店',
    'xiaoming-store',
    'store@example.com',
    '$2y$10$B14L97nU6Pd2Q5dgdcmdZeaNQZ78Wt79CUSWPhWgdlykdh/UbjYry',
    '王小明',
    '0912345678',
    'active'
);

INSERT INTO SUPER_ADMIN (
    username,
    email,
    password
) VALUES
(
    'admin',
    'admin@example.com',
    '$$2y$10$B14L97nU6Pd2Q5dgdcmdZeaNQZ78Wt79CUSWPhWgdlykdh/UbjYry'
);

INSERT INTO CATEGORY (
    store_id,
    category_name,
    sort_order,
    status
) VALUES
(1, '飲料', 1, 'active'),
(1, '甜點', 2, 'active'),
(1, '生活用品', 3, 'active');

INSERT INTO PRODUCT (
    store_id,
    category_id,
    product_name,
    description,
    price,
    stock,
    has_spec,
    spec_name,
    sort_order,
    status
) VALUES
(
    1,
    1,
    '珍珠奶茶',
    '香濃奶茶搭配Q彈珍珠',
    60.00,
    50,
    FALSE,
    NULL,
    1,
    'active'
),
(
    1,
    1,
    '冬瓜茶',
    '清爽甘甜的冬瓜茶',
    40.00,
    30,
    FALSE,
    NULL,
    2,
    'active'
),
(
    1,
    2,
    '經典蛋糕',
    '每日新鮮製作的蛋糕',
    NULL,
    0,
    TRUE,
    '尺寸',
    3,
    'active'
),
(
    1,
    3,
    '環保水壺',
    '可重複使用的不鏽鋼水壺',
    NULL,
    0,
    TRUE,
    '容量',
    4,
    'active'
);

INSERT INTO PRODUCT_SPEC (
    store_id,
    product_id,
    spec_name,
    price,
    stock,
    status
) VALUES
(1, 3, '4吋', 300.00, 10, 'active'),
(1, 3, '6吋', 500.00, 8, 'active'),
(1, 3, '8吋', 700.00, 5, 'active'),
(1, 4, '500ml', 350.00, 20, 'active'),
(1, 4, '750ml', 450.00, 15, 'active'),
(1, 4, '1000ml', 550.00, 10, 'active');

INSERT INTO PRODUCT_IMAGE (
    store_id,
    product_id,
    image_url,
    sort_order
) VALUES
(1, 1, '/uploads/products/milk-tea.jpg', 1),
(1, 2, '/uploads/products/winter-melon.jpg', 1),
(1, 3, '/uploads/products/cake.jpg', 1),
(1, 3, '/uploads/products/cake-2.jpg', 2),
(1, 4, '/uploads/products/bottle.jpg', 1);

INSERT INTO CART (
    customer_id,
    store_id
) VALUES
(1, 1),
(2, 1),
(3, 1);

INSERT INTO CART_ITEM (
    cart_id,
    store_id,
    product_id,
    spec_id,
    quantity
) VALUES
(1, 1, 1, NULL, 2),
(1, 1, 3, 2, 1),
(2, 1, 2, NULL, 3),
(3, 1, 4, 5, 1);

INSERT INTO ORDERS (
    order_number,
    customer_id,
    store_id,
    receiver_name,
    receiver_phone,
    receiver_address,
    order_date,
    product_amount,
    shipping_fee,
    total_amount,
    delivery_method,
    delivery_status,
    estimated_ship_date,
    estimated_arrival_date
) VALUES
(
    'ORD202608220001',
    1,
    1,
    '王小明',
    '0912345678',
    '高雄市鼓山區大學路1號',
    NOW(),
    560.00,
    60.00,
    620.00,
    'home_delivery',
    'pending',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 5 DAY)
),
(
    'ORD202608220002',
    2,
    1,
    '陳小華',
    '0923456789',
    '高雄市左營區博愛路100號',
    NOW(),
    120.00,
    60.00,
    180.00,
    'home_delivery',
    'shipping',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 3 DAY)
),
(
    'ORD202608210003',
    3,
    1,
    '林小美',
    '0934567890',
    '高雄市三民區建國路200號',
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    500.00,
    60.00,
    560.00,
    'home_delivery',
    'completed',
    DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    CURDATE()
);

INSERT INTO ORDER_ITEM (
    order_id,
    store_id,
    product_id,
    spec_id,
    product_name,
    spec_name,
    quantity,
    price
) VALUES
(
    1,
    1,
    1,
    NULL,
    '珍珠奶茶',
    NULL,
    1,
    60.00
),
(
    1,
    1,
    3,
    2,
    '經典蛋糕',
    '6吋',
    1,
    500.00
),
(
    2,
    1,
    1,
    NULL,
    '珍珠奶茶',
    NULL,
    2,
    60.00
),
(
    3,
    1,
    3,
    2,
    '經典蛋糕',
    '6吋',
    1,
    500.00
);

INSERT INTO PAYMENT (
    order_id,
    store_id,
    payment_method,
    amount,
    payment_status,
    payment_confirm_status,
    payment_note,
    paid_at,
    confirmed_at
) VALUES
(
    1,
    1,
    'credit_card',
    620.00,
    'paid',
    'confirmed',
    NULL,
    NOW(),
    NOW()
),
(
    2,
    1,
    'atm',
    180.00,
    'pending',
    'waiting',
    '等待店家確認匯款',
    NULL,
    NULL
),
(
    3,
    1,
    'post_office',
    560.00,
    'paid',
    'confirmed',
    NULL,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY)
);

INSERT INTO REFUND (
    order_id,
    store_id,
    refund_reason,
    refund_description,
    refund_image_url,
    refund_status,
    admin_reply,
    requested_at,
    processed_at
) VALUES
(
    3,
    1,
    '商品問題',
    '商品收到後發現外觀有損傷。',
    '/uploads/refunds/refund-3.jpg',
    'pending',
    NULL,
    NOW(),
    NULL
);

INSERT INTO WEBSITE_SETTING (
    store_id,
    intro_section_enable,
    banner_section_enable
) VALUES
(
    1,
    TRUE,
    TRUE
);

INSERT INTO STORE_SETTING (
    store_id,
    store_status,
    store_mode,
    refund_enable,
    refund_days_limit,
    shipping_days,
    delivery_days,
    stock_alert_enable,
    stock_alert_threshold,
    spec_stock_alert_threshold,
    customer_service_enable
) VALUES
(
    1,
    'open',
    'shopping',
    TRUE,
    7,
    3,
    5,
    TRUE,
    10,
    3,
    TRUE
);

INSERT INTO STORE_PAYMENT_METHOD (
    store_id,
    store_payment_id,
    payment_method,
    status
) VALUES
(1, 1, 'credit_card', 'active'),
(1, 2, 'atm', 'active'),
(1, 3, 'post_office', 'active'),
(1, 4, 'cash_on_delivery', 'inactive'),
(1, 5, 'in_store', 'inactive');

INSERT INTO STORE_PAYMENT_ACCOUNT (
    store_id,
    store_payment_id,
    bank_name,
    bank_number
) VALUES
(
    1,
    2,
    '台灣銀行',
    '004123456789'
);

INSERT INTO STORE_PAYMENT_ACCOUNT (
    store_id,
    store_payment_id,
    post_office_number
) VALUES
(
    1,
    3,
    '70012345678901'
);

INSERT INTO STORE_DELIVERY_METHOD (
    store_id,
    delivery_method,
    status
) VALUES
(1, 'home_delivery', 'active'),
(1, 'convenience_store', 'active'),
(1, 'store_pickup', 'inactive');

INSERT INTO HOMEPAGE_PRODUCT_SETTING (
    store_id,
    display_limit
) VALUES
(
    1,
    4
);

INSERT INTO FOOTER_SETTING (
    store_id,
    contact_phone,
    address,
    email,
    service_phone,
    contact_phone_enable,
    address_enable,
    email_enable,
    service_phone_enable
) VALUES
(
    1,
    '0912345678',
    '高雄市鼓山區大學路1號',
    'store@example.com',
    '0800-123-456',
    TRUE,
    TRUE,
    TRUE,
    TRUE
);

INSERT INTO DEFAULT_BANNER (
    image_url,
    name
) VALUES
(
    '/uploads/banners/default-1.jpg',
    '預設促銷 Banner'
),
(
    '/uploads/banners/default-2.jpg',
    '預設新品 Banner'
);

INSERT INTO PROMOTION_BANNER (
    store_id,
    default_banner_id,
    image_url,
    title,
    description,
    status,
    sort_order
) VALUES
(
    1,
    1,
    NULL,
    '夏季飲品優惠',
    '夏季飲品限時優惠活動',
    'active',
    1
),
(
    1,
    NULL,
    '/uploads/banners/store-banner.jpg',
    '新品上市',
    '最新商品正式上市',
    'active',
    2
);

INSERT INTO SLIDER_IMAGE (
    store_id,
    image_url,
    title,
    status,
    sort_order
) VALUES
(
    1,
    '/uploads/sliders/slider-1.jpg',
    '夏季新品',
    'active',
    1
),
(
    1,
    '/uploads/sliders/slider-2.jpg',
    '人氣商品',
    'active',
    2
);

INSERT INTO CUSTOMER_SERVICE (
    customer_id,
    store_id,
    order_id,
    problem_type,
    description,
    image_url,
    status,
    admin_reply
) VALUES
(
    1,
    1,
    1,
    '商品問題',
    '收到的商品與訂單內容有些差異。',
    '/uploads/service/service-1.jpg',
    'pending',
    NULL
),
(
    2,
    1,
    2,
    '付款問題',
    '已完成 ATM 轉帳，請協助確認。',
    NULL,
    'resolved',
    '已確認收到您的款項。'
);