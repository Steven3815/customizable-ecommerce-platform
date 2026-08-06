INSERT INTO SUPER_ADMIN
(
    username,
    email,
    password
)
VALUES
(
    'admin',
    'admin@example.com',
    'admin123'
);

INSERT INTO STORE
(
    store_name,
    store_url,
    email,
    password,
    owner_name,
    phone,
    status
)
VALUES
(
    'Beauty Shop',
    'beautyshop',
    'store@example.com',
    'store123',
    'Amy Chen',
    '0912345678',
    'active'
);

INSERT INTO CUSTOMER
(
    name,
    email,
    password,
    phone,
    address,
    preferred_payment,
    preferred_delivery
)
VALUES
(
    'Steven Yeh',
    'steven@example.com',
    '123456',
    '0987654321',
    'Kaohsiung City',
    'Credit Card',
    'Home Delivery'
);

INSERT INTO DEFAULT_BANNER
(
    image_url,
    name
)
VALUES
(
    '/images/default_banner_01.jpg',
    'Default Summer Banner'
);


INSERT INTO CATEGORY
(
    store_id,
    category_name,
    sort_order,
    status
)
VALUES
(
    1,
    'Skincare',
    1,
    'active'
),
(
    1,
    'Makeup',
    2,
    'active'
);

INSERT INTO WEBSITE_SETTING
(
    store_id,
    intro_section_enable,
    banner_section_enable
)
VALUES
(
    1,
    TRUE,
    TRUE
);

INSERT INTO STORE_SETTING
(
    store_id,
    store_status,
    store_mode,
    refund_enable,
    refund_days_limit,
    shipping_days,
    delivery_days,
    stock_alert_enable,
    stock_alert_threshold,
    customer_service_enable
)
VALUES
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
    TRUE
);

INSERT INTO STORE_PAYMENT_METHOD
(
    store_id,
    payment_method,
    status
)
VALUES
(
    1,
    'Credit Card',
    'active'
),
(
    1,
    'ATM Transfer',
    'active'
);

INSERT INTO STORE_DELIVERY_METHOD
(
    store_id,
    delivery_method,
    status
)
VALUES
(
    1,
    'Home Delivery',
    'active'
),
(
    1,
    'Convenience Store Pickup',
    'active'
);


INSERT INTO HOMEPAGE_PRODUCT_SETTING
(
    store_id,
    sort_type,
    display_limit
)
VALUES
(
    1,
    'latest',
    6
);

INSERT INTO FOOTER_SETTING
(
    store_id,
    contact_phone,
    address,
    email,
    service_phone
)
VALUES
(
    1,
    '0912345678',
    'Kaohsiung City',
    'service@beautyshop.com',
    '0800123456'
);

INSERT INTO SLIDER_IMAGE
(
    store_id,
    image_url,
    sort_order
)
VALUES
(
    1,
    '/images/slider01.jpg',
    1
),
(
    1,
    '/images/slider02.jpg',
    2
);

INSERT INTO PROMOTION_BANNER
(
    store_id,
    default_banner_id,
    image_url,
    title,
    description
)
VALUES
(
    1,
    1,
    NULL,
    'Summer Sale',
    'Up to 30% off'
),
(
    1,
    NULL,
    '/images/store_banner.jpg',
    'New Product',
    'New arrivals'
);

INSERT INTO PRODUCT
(
    store_id,
    category_id,
    product_name,
    description,
    price,
    stock,
    has_spec,
    status
)
VALUES
(
    1,
    1,
    'Gentle Facial Cleanser',
    'A mild cleanser for daily skincare',
    350,
    100,
    FALSE,
    'active'
);

INSERT INTO PRODUCT
(
    store_id,
    category_id,
    product_name,
    description,
    has_spec,
    status
)
VALUES
(
    1,
    2,
    'Moisture Lipstick',
    'Long lasting lipstick',
    TRUE,
    'active'
);

INSERT INTO PRODUCT_SPEC
(
    product_id,
    spec_name,
    price,
    stock
)
VALUES
(
    2,
    'Red',
    500,
    30
),
(
    2,
    'Pink',
    520,
    20
),
(
    2,
    'Orange',
    510,
    15
);

INSERT INTO PRODUCT_IMAGE
(
    product_id,
    image_url,
    sort_order
)
VALUES
(
    1,
    '/images/cleanser_main.jpg',
    1
),
(
    1,
    '/images/cleanser_side.jpg',
    2
),
(
    2,
    '/images/lipstick_main.jpg',
    1
);

INSERT INTO CART
(
    customer_id
)
VALUES
(
    1
);

INSERT INTO CART_ITEM
(
    cart_id,
    product_id,
    spec_id,
    quantity
)
VALUES
(
    1,
    1,
    NULL,
    2
),
(
    1,
    2,
    1,
    1
);

INSERT INTO ORDERS
(
    customer_id,
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
)
VALUES
(
    1,
    'Steven',
    '0912345678',
    'Kaohsiung City',
    NOW(),
    1200,
    60,
    1260,
    'Home Delivery',
    'pending',
    '2026-08-10',
    '2026-08-15'
);

INSERT INTO ORDER_ITEM
(
    order_id,
    product_id,
    spec_id,
    product_name,
    spec_name,
    quantity,
    price
)
VALUES
(
    1,
    1,
    NULL,
    'Gentle Facial Cleanser',
    NULL,
    2,
    350
),
(
    1,
    2,
    1,
    'Moisture Lipstick',
    'Red',
    1,
    500
);

INSERT INTO PAYMENT
(
    order_id,
    payment_method,
    payment_status,
    payment_confirm_status,
    payment_note
)
VALUES
(
    1,
    'ATM Transfer',
    'pending',
    'waiting',
    '等待匯款確認'
);

INSERT INTO REFUND
(
    order_id,
    refund_reason,
    refund_description,
    refund_status,
    requested_at
)
VALUES
(
    1,
    'Wrong product',
    'Received incorrect item',
    'pending',
    NOW()
);

INSERT INTO STORE_PAYMENT_ACCOUNT
(
    store_id,
    store_payment_id,
    bank_name,
    account_number,
    account_name
)
VALUES
(
    1,
    2,
    'Taiwan Post',
    '700123456789',
    'Beauty Store'
);

INSERT INTO CUSTOMER_SERVICE
(
    customer_id,
    order_id,
    problem_type,
    description,
    status
)
VALUES
(
    1,
    1,
    'Refund',
    'I want to request a refund',
    'pending'
),
(
    1,
    NULL,
    'Payment',
    'How can I pay?',
    'pending'
);
