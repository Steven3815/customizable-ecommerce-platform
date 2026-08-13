USE ecommerce_platform;

INSERT INTO CUSTOMER
(name, email, password, phone, address, preferred_payment, preferred_delivery)
VALUES
('王小明', 'test@example.com', '$2y$12$qqCtiUHSArle3h9tflIm..I8Y0HCLSt0k64ju1169E/km81eG8que', '0912345678', '高雄市鼓山區大學路100號', 'credit_card', 'home_delivery'),
('陳小華', 'customer2@example.com', '$2y$12$qqCtiUHSArle3h9tflIm..I8Y0HCLSt0k64ju1169E/km81eG8que', '0923456789', '台南市東區中華東路200號', 'atm', 'home_delivery');

INSERT INTO STORE
(store_name, store_url, email, password, owner_name, phone, status)
VALUES
('小明生活選物店', 'https://ecommerce.com/store-1', 'store@example.com', '$2y$12$qqCtiUHSArle3h9tflIm..I8Y0HCLSt0k64ju1169E/km81eG8que', '王小明', '0987654321', 'active'),
('台灣好物商店', 'https://ecommerce.com/store-2', 'store2@example.com', '$2y$12$qqCtiUHSArle3h9tflIm..I8Y0HCLSt0k64ju1169E/km81eG8que', '陳大華', '0977123456', 'active');

INSERT INTO SUPER_ADMIN
(username, email, password)
VALUES
('admin', 'admin@example.com', '$2y$12$qqCtiUHSArle3h9tflIm..I8Y0HCLSt0k64ju1169E/km81eG8que');

INSERT INTO CATEGORY
(store_id, category_name, sort_order, status)
VALUES
(1, '食品', 1, 'active'),
(1, '生活用品', 2, 'active'),
(1, '飲料', 3, 'active'),
(2, '美妝', 1, 'active'),
(2, '服飾', 2, 'active');

INSERT INTO PRODUCT
(store_id, category_id, product_name, description, price, stock, has_spec, status)
VALUES
(1, 1, '台灣鳳梨酥', '台灣特色鳳梨酥，香甜好吃。', 300.00, 50, FALSE, 'active'),
(1, 2, '環保購物袋', '可重複使用的環保購物袋。', 120.00, 100, FALSE, 'active'),
(1, 3, '珍珠奶茶', '經典台灣珍珠奶茶。', 60.00, 80, TRUE, 'active'),
(2, 4, '保濕面膜', '日常保濕面膜。', 250.00, 40, TRUE, 'active'),
(2, 5, '基本款T恤', '簡約基本款短袖T恤。', 490.00, 30, TRUE, 'active');

INSERT INTO PRODUCT_SPEC
(store_id, product_id, spec_name, price, stock, status)
VALUES
(1, 3, '正常冰', 60.00, 30, 'active'),
(1, 3, '少冰', 60.00, 30, 'active'),
(1, 3, '去冰', 60.00, 20, 'active'),
(2, 4, '單片裝', 250.00, 20, 'active'),
(2, 4, '五片裝', 1000.00, 10, 'active'),
(2, 5, 'S-黑色', 490.00, 10, 'active'),
(2, 5, 'M-黑色', 490.00, 10, 'active'),
(2, 5, 'L-黑色', 490.00, 10, 'active');

INSERT INTO PRODUCT_IMAGE
(store_id, product_id, image_url, sort_order)
VALUES
(1, 1, 'https://example.com/images/pineapple-cake.jpg', 1),
(1, 2, 'https://example.com/images/shopping-bag.jpg', 1),
(1, 3, 'https://example.com/images/bubble-tea.jpg', 1),
(2, 4, 'https://example.com/images/mask.jpg', 1),
(2, 5, 'https://example.com/images/tshirt.jpg', 1);

INSERT INTO CART
(customer_id, store_id)
VALUES
(1, 1),
(1, 2),
(2, 1);

INSERT INTO CART_ITEM
(cart_id, store_id, product_id, spec_id, quantity)
VALUES
(1, 1, 1, NULL, 2),
(1, 1, 3, 1, 1),
(2, 2, 4, 4, 2),
(3, 1, 2, NULL, 3);

INSERT INTO ORDERS
(customer_id, store_id, receiver_name, receiver_phone, receiver_address, order_date, product_amount, shipping_fee, total_amount, delivery_method, delivery_status, estimated_ship_date, estimated_arrival_date)
VALUES
(1, 1, '王小明', '0912345678', '高雄市鼓山區大學路100號', NOW(), 420.00, 60.00, 480.00, 'home_delivery', 'pending', CURDATE() + INTERVAL 3 DAY, CURDATE() + INTERVAL 8 DAY),
(1, 2, '王小明', '0912345678', '高雄市鼓山區大學路100號', NOW(), 500.00, 60.00, 560.00, 'home_delivery', 'shipping', CURDATE() - INTERVAL 2 DAY, CURDATE() + INTERVAL 3 DAY),
(2, 1, '陳小華', '0923456789', '台南市東區中華東路200號', NOW(), 120.00, 60.00, 180.00, 'home_delivery', 'completed', CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 5 DAY);

INSERT INTO ORDER_ITEM
(order_id, store_id, product_id, spec_id, product_name, spec_name, quantity, price)
VALUES
(1, 1, 1, NULL, '台灣鳳梨酥', NULL, 1, 300.00),
(1, 1, 3, 1, '珍珠奶茶', '正常冰', 2, 60.00),
(2, 2, 4, 4, '保濕面膜', '單片裝', 2, 250.00),
(3, 1, 2, NULL, '環保購物袋', NULL, 1, 120.00);

INSERT INTO PAYMENT
(order_id, store_id, payment_method, amount, payment_status, payment_confirm_status, payment_note, paid_at, confirmed_at)
VALUES
(1, 1, 'credit_card', 480.00, 'paid', 'confirmed', '信用卡付款成功', NOW(), NOW()),
(2, 2, 'atm', 560.00, 'processing', 'waiting', '等待商家確認匯款', NULL, NULL),
(3, 1, 'cash_on_delivery', 180.00, 'paid', 'confirmed', '貨到付款完成', NOW(), NOW());

INSERT INTO REFUND
(order_id, store_id, refund_reason, refund_description, refund_image_url, refund_status, admin_reply, requested_at, processed_at)
VALUES
(3, 1, '商品不符合需求', '收到商品後希望申請退款。', 'https://example.com/images/refund.jpg', 'pending', NULL, NOW(), NULL);

INSERT INTO WEBSITE_SETTING
(store_id, intro_section_enable, banner_section_enable)
VALUES
(1, TRUE, TRUE),
(2, TRUE, TRUE);

INSERT INTO STORE_SETTING
(store_id, store_status, store_mode, refund_enable, refund_days_limit, shipping_days, delivery_days, stock_alert_enable, stock_alert_threshold, customer_service_enable)
VALUES
(1, 'open', 'shopping', TRUE, 7, 3, 5, TRUE, 10, TRUE),
(2, 'open', 'shopping', FALSE, 7, 3, 5, TRUE, 5, TRUE);

INSERT INTO STORE_PAYMENT_METHOD
(store_id, store_payment_id, payment_method, status)
VALUES
(1, 1, 'credit_card', 'active'),
(1, 2, 'atm', 'active'),
(1, 3, 'post_office', 'inactive'),
(1, 4, 'cash_on_delivery', 'active'),
(1, 5, 'in_store', 'inactive'),
(2, 1, 'credit_card', 'active'),
(2, 2, 'atm', 'active'),
(2, 3, 'post_office', 'active'),
(2, 4, 'cash_on_delivery', 'inactive'),
(2, 5, 'in_store', 'inactive');

INSERT INTO STORE_PAYMENT_ACCOUNT
(store_id, store_payment_id, bank_name, bank_number, post_office_number)
VALUES
(1, 2, '台灣銀行', '004123456789', NULL),
(1, 3, NULL, NULL, '70012345678901'),
(2, 2, '中國信託', '822987654321', NULL),
(2, 3, NULL, NULL, '70098765432109');

INSERT INTO STORE_DELIVERY_METHOD
(store_id, delivery_method, status)
VALUES
(1, 'home_delivery', 'active'),
(1, 'convenience_store', 'active'),
(2, 'home_delivery', 'active'),
(2, 'convenience_store', 'inactive');

INSERT INTO HOMEPAGE_PRODUCT_SETTING
(store_id, sort_type, display_limit)
VALUES
(1, 'latest', 6),
(2, 'popular', 6);

INSERT INTO FOOTER_SETTING
(store_id, contact_phone, address, email, service_phone)
VALUES
(1, '0987654321', '高雄市鼓山區大學路100號', 'store@example.com', '0800-123-456'),
(2, '0977123456', '台南市東區中華東路200號', 'store2@example.com', '0800-654-321');

INSERT INTO DEFAULT_BANNER
(image_url, name)
VALUES
('https://example.com/images/default-banner-1.jpg', '夏季促銷 Banner'),
('https://example.com/images/default-banner-2.jpg', '新品上市 Banner');

INSERT INTO PROMOTION_BANNER
(store_id, default_banner_id, image_url, title, description)
VALUES
(1, 1, 'https://example.com/images/store1-banner.jpg', '夏季限定優惠', '夏季商品限時優惠活動。'),
(2, 2, 'https://example.com/images/store2-banner.jpg', '新品上市', '最新商品正式上市。');

INSERT INTO SLIDER_IMAGE
(store_id, image_url, sort_order)
VALUES
(1, 'https://example.com/images/store1-slider-1.jpg', 1),
(1, 'https://example.com/images/store1-slider-2.jpg', 2),
(2, 'https://example.com/images/store2-slider-1.jpg', 1);

INSERT INTO CUSTOMER_SERVICE
(customer_id, store_id, order_id, problem_type, description, image_url, status, admin_reply)
VALUES
(1, 1, 1, '訂單問題', '想詢問訂單目前的處理進度。', NULL, 'pending', NULL),
(2, 1, 3, '退款問題', '想詢問退款申請的處理進度。', 'https://example.com/images/service.jpg', 'resolved', '您好，您的退款申請已經收到。');