USE ecommerce_platform;
SET NAMES utf8mb4;

INSERT INTO CUSTOMER
(customer_id, name, email, password, phone, address, preferred_payment, preferred_delivery)
VALUES
(1,'林小安','an@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','0912000001','高雄市前金區中正四路100號','credit_card','home_delivery'),
(2,'陳柏宇','bo@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','0912000002','台南市東區大學路1號','atm','convenience_store'),
(3,'黃品妤','pin@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','0912000003','台中市西區公益路200號','post_office','home_delivery'),
(4,'張家豪','hao@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','0912000004','台北市大安區和平東路50號','cash_on_delivery','home_delivery'),
(5,'王思涵','sihan@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','0912000005','新竹市東區光復路一段88號','in_store','store_pickup');

INSERT INTO STORE
(store_id,store_name,store_url,email,password,owner_name,phone,status)
VALUES
(1,'森日生活選物','https://example.com/store-1','store1@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','林怡君','07-2000001','active'),
(2,'日常好食光','https://example.com/store-2','store2@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','陳志明','06-2000002','active'),
(3,'拾光文具室','https://example.com/store-3','store3@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','黃雅婷','04-2000003','active'),
(4,'山海戶外用品','https://example.com/store-4','store4@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','張育誠','02-2000004','active'),
(5,'柔棉穿搭所','https://example.com/store-5','store5@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW','王美玲','03-2000005','active');

INSERT INTO SUPER_ADMIN
(admin_id,username,email,password)
VALUES
(1,'superadmin','admin@example.com','$2y$10$dU4A8kt7GiBSwlbFIlo/ZeXbGme4lvAnwR6XM7Udp9rzINOyRvcqW');

INSERT INTO CATEGORY
(category_id,store_id,category_name,sort_order,status)
VALUES
(1,1,'居家收納',1,'active'),
(2,1,'廚房用品',2,'active'),
(3,1,'生活清潔',3,'active'),
(4,1,'香氛療癒',4,'active'),
(5,1,'居家布織',5,'active'),
(6,2,'零食點心',1,'active'),
(7,2,'沖泡飲品',2,'active'),
(8,2,'米麵主食',3,'active'),
(9,2,'調味醬料',4,'active'),
(10,2,'健康食品',5,'active'),
(11,3,'書寫工具',1,'active'),
(12,3,'筆記本冊',2,'active'),
(13,3,'桌面收納',3,'active'),
(14,3,'美術材料',4,'active'),
(15,3,'文創小物',5,'active'),
(16,4,'露營裝備',1,'active'),
(17,4,'戶外餐具',2,'active'),
(18,4,'運動配件',3,'active'),
(19,4,'防曬雨具',4,'active'),
(20,4,'旅行收納',5,'active'),
(21,5,'上衣',1,'active'),
(22,5,'下身服飾',2,'active'),
(23,5,'外套',3,'active'),
(24,5,'包款配件',4,'active'),
(25,5,'襪子居家服',5,'active');

INSERT INTO PRODUCT
(product_id,store_id,category_id,product_name,description,price,stock,has_spec,spec_name,sort_order,status)
SELECT
    (c.category_id - 1) * 6 + n.n,
    c.store_id,
    c.category_id,
    CONCAT(c.category_name,'商品',n.n),
    CONCAT(c.category_name,'的實用商品，適合日常使用。'),
    99 + c.category_id * 13 + n.n * 20,
    20 + n.n * 5,
    IF(n.n IN (2,4),1,0),
    IF(n.n IN (2,4),'款式',NULL),
    n.n,
    'active'
FROM CATEGORY c
CROSS JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
) n;

INSERT INTO PRODUCT_SPEC
(spec_id,store_id,product_id,spec_name,price,stock,status)
SELECT
    p.product_id * 2 - 1,
    p.store_id,
    p.product_id,
    '款式A',
    p.price,
    10,
    'active'
FROM PRODUCT p
WHERE p.has_spec = 1;

INSERT INTO PRODUCT_SPEC
(spec_id,store_id,product_id,spec_name,price,stock,status)
SELECT
    p.product_id * 2,
    p.store_id,
    p.product_id,
    '款式B',
    p.price + 50,
    15,
    'active'
FROM PRODUCT p
WHERE p.has_spec = 1;

INSERT INTO PRODUCT_IMAGE
(store_id,product_id,image_url,sort_order)
SELECT
    store_id,
    product_id,
    'http://localhost:5173/test/products/default-product.jpg',
    1
FROM PRODUCT;

INSERT INTO CART
(cart_id,customer_id,store_id)
VALUES
(1,1,1),
(2,1,2),
(3,2,1),
(4,2,3),
(5,3,2),
(6,3,4),
(7,4,3),
(8,4,5),
(9,5,4),
(10,5,5);

INSERT INTO CART_ITEM
(cart_item_id,cart_id,store_id,product_id,spec_id,quantity)
VALUES
(1,1,1,1,NULL,2),
(2,2,2,31,NULL,1),
(3,3,1,7,NULL,1),
(4,4,3,61,NULL,2),
(5,5,2,31,NULL,3),
(6,6,4,91,NULL,1),
(7,7,3,61,NULL,1),
(8,8,5,121,NULL,1),
(9,9,4,91,NULL,2),
(10,10,5,121,NULL,1);

INSERT INTO ORDERS
(order_id,order_number,customer_id,store_id,receiver_name,receiver_phone,
 receiver_address,order_date,product_amount,shipping_fee,total_amount,
 order_status,delivery_method,delivery_status,estimated_ship_date,estimated_arrival_date)
VALUES
(1,'ORD202610090001',1,1,'林小安','0912000001','高雄市前金區中正四路100號','2026-10-01 10:00:00',132,60,192,'confirmed','home_delivery','shipping','2026-10-04','2026-10-09'),
(2,'ORD202610090002',2,2,'陳柏宇','0912000002','台南市東區大學路1號','2026-10-02 11:00:00',197,60,257,'confirmed','convenience_store','completed','2026-10-05','2026-10-08'),
(3,'ORD202610090003',3,3,'黃品妤','0912000003','台中市西區公益路200號','2026-10-03 12:00:00',262,60,322,'pending','home_delivery','pending','2026-10-06','2026-10-11'),
(4,'ORD202610090004',4,4,'張家豪','0912000004','台北市大安區和平東路50號','2026-10-04 13:00:00',327,60,387,'confirmed','home_delivery','shipping','2026-10-07','2026-10-12'),
(5,'ORD202610090005',5,5,'王思涵','0912000005','新竹市東區光復路一段88號','2026-10-05 14:00:00',392,60,452,'pending','store_pickup','pending','2026-10-08','2026-10-13'),
(6,'ORD202610090006',1,2,'林小安','0912000001','高雄市前金區中正四路100號','2026-10-06 15:00:00',197,60,257,'cancelled','home_delivery','pending','2026-10-09','2026-10-14'),
(7,'ORD202610090007',2,4,'陳柏宇','0912000002','台南市東區大學路1號','2026-10-07 16:00:00',327,60,387,'confirmed','home_delivery','pending','2026-10-10','2026-10-15'),
(8,'ORD202610090008',3,5,'黃品妤','0912000003','台中市西區公益路200號','2026-10-08 17:00:00',392,60,452,'pending','home_delivery','pending','2026-10-11','2026-10-16');

INSERT INTO ORDER_ITEM
(order_item_id,order_id,store_id,product_id,spec_id,product_name,spec_name,quantity,price)
SELECT
    o.order_id,
    o.order_id,
    o.store_id,
    p.product_id,
    NULL,
    p.product_name,
    NULL,
    1,
    p.price
FROM ORDERS o
JOIN PRODUCT p
  ON p.store_id = o.store_id
 AND p.category_id = (o.store_id - 1) * 5 + 1
 AND p.product_id = (p.category_id - 1) * 6 + 1
WHERE o.order_id <= 5;

INSERT INTO ORDER_ITEM
(order_item_id,order_id,store_id,product_id,spec_id,product_name,spec_name,quantity,price)
SELECT
    o.order_id + 5,
    o.order_id,
    o.store_id,
    p.product_id,
    NULL,
    p.product_name,
    NULL,
    1,
    p.price
FROM ORDERS o
JOIN PRODUCT p
  ON p.store_id = o.store_id
 AND p.category_id = (o.store_id - 1) * 5 + 1
 AND p.product_id = (p.category_id - 1) * 6 + 1
WHERE o.order_id > 5;

INSERT INTO PAYMENT
(payment_id,order_id,store_id,payment_method,amount,payment_status,
 payment_confirm_status,payment_note,paid_at,confirmed_at)
VALUES
(1,1,1,'credit_card',192,'paid','confirmed','信用卡付款完成','2026-10-01 10:05:00','2026-10-01 10:05:00'),
(2,2,2,'atm',257,'paid','confirmed','轉帳已確認','2026-10-02 12:00:00','2026-10-02 13:00:00'),
(3,3,3,'post_office',322,'pending','waiting','等待轉帳',NULL,NULL),
(4,4,4,'credit_card',387,'paid','confirmed','信用卡付款完成','2026-10-04 13:05:00','2026-10-04 13:05:00'),
(5,5,5,'in_store',452,'pending','waiting','等待到店付款',NULL,NULL),
(6,6,2,'atm',257,'failed','rejected','訂單已取消',NULL,NULL),
(7,7,4,'cash_on_delivery',387,'processing','waiting','貨到付款',NULL,NULL),
(8,8,5,'credit_card',452,'pending','waiting','尚未完成付款',NULL,NULL);

INSERT INTO REFUND
(refund_id,order_id,store_id,refund_reason,refund_description,
 refund_image_url,refund_status,admin_reply,requested_at,processed_at)
VALUES
(1,2,2,'商品不符預期','商品與預期有差異。',NULL,'approved','退款申請已核准。','2026-10-08 10:00:00','2026-10-08 15:00:00'),
(2,4,4,'商品瑕疵','商品外觀有刮痕。',NULL,'pending',NULL,'2026-10-09 09:00:00',NULL),
(3,7,4,'其他原因','想詢問訂單退款規則。',NULL,'rejected','目前不符合退款條件。','2026-10-09 11:00:00','2026-10-09 16:00:00');

INSERT INTO WEBSITE_SETTING
(store_id,intro_section_enable,banner_section_enable)
VALUES
(1,1,1),
(2,1,1),
(3,1,1),
(4,1,1),
(5,1,1);

INSERT INTO STORE_SETTING
(store_id,store_status,store_mode,refund_enable,refund_days_limit,
 shipping_days,delivery_days,stock_alert_enable,stock_alert_threshold,
 spec_stock_alert_threshold,customer_service_enable)
VALUES
(1,'open','shopping',1,7,3,5,1,10,3,1),
(2,'open','shopping',1,7,3,5,1,10,3,1),
(3,'open','shopping',0,7,3,5,1,8,3,1),
(4,'open','shopping',1,14,3,5,1,5,2,1),
(5,'open','shopping',1,7,3,5,0,NULL,3,1);

INSERT INTO HOMEPAGE_PRODUCT_SETTING
(store_id,display_limit)
VALUES
(1,4),
(2,5),
(3,6),
(4,4),
(5,5);

INSERT INTO FOOTER_SETTING
(store_id,contact_phone,address,email,service_phone,
 contact_phone_enable,address_enable,email_enable,service_phone_enable)
VALUES
(1,'07-2000001','高雄市前金區中正四路100號','store1@example.com','07-2000011',1,1,1,0),
(2,'06-2000002','台南市東區大學路1號','store2@example.com','06-2000022',1,1,1,1),
(3,'04-2000003','台中市西區公益路200號','store3@example.com','04-2000033',1,0,1,0),
(4,'02-2000004','台北市大安區和平東路50號','store4@example.com','02-2000044',1,1,1,1),
(5,'03-2000005','新竹市東區光復路一段88號','store5@example.com','03-2000055',1,1,1,0);

INSERT INTO STORE_PAYMENT_METHOD
(store_id,store_payment_id,payment_method,status)
SELECT
    s.store_id,
    m.method_id,
    m.method_name,
    'active'
FROM STORE s
CROSS JOIN (
    SELECT 1 AS method_id,'credit_card' AS method_name
    UNION ALL SELECT 2,'atm'
    UNION ALL SELECT 3,'post_office'
    UNION ALL SELECT 4,'cash_on_delivery'
    UNION ALL SELECT 5,'in_store'
) m;

INSERT INTO STORE_PAYMENT_ACCOUNT
(store_id,store_payment_id,bank_name,bank_number,post_office_number)
VALUES
(1,2,'台灣銀行','004123456789',NULL),
(1,3,'中華郵政',NULL,'70012345678901'),
(2,2,'第一銀行','007234567890',NULL),
(2,3,'中華郵政',NULL,'70023456789012'),
(3,2,'合作金庫','006345678901',NULL),
(3,3,'中華郵政',NULL,'70034567890123'),
(4,2,'兆豐銀行','017456789012',NULL),
(4,3,'中華郵政',NULL,'70045678901234'),
(5,2,'玉山銀行','808567890123',NULL),
(5,3,'中華郵政',NULL,'70056789012345');

INSERT INTO STORE_DELIVERY_METHOD
(store_id,delivery_method,status)
VALUES
(1,'home_delivery','active'),
(1,'convenience_store','active'),
(1,'store_pickup','active'),
(2,'home_delivery','active'),
(2,'convenience_store','active'),
(2,'store_pickup','active'),
(3,'home_delivery','active'),
(3,'convenience_store','active'),
(3,'store_pickup','active'),
(4,'home_delivery','active'),
(4,'convenience_store','active'),
(4,'store_pickup','active'),
(5,'home_delivery','active'),
(5,'convenience_store','active'),
(5,'store_pickup','active');

INSERT INTO DEFAULT_BANNER
(default_banner_id,image_url,name)
VALUES
(1,'http://localhost:5173/test/banner/banner.jpg','自然生活'),
(2,'http://localhost:5173/test/banner/banner.jpg','簡約日常'),
(3,'http://localhost:5173/test/banner/banner.jpg','季節推薦'),
(4,'http://localhost:5173/test/banner/banner.jpg','新品上市'),
(5,'http://localhost:5173/test/banner/banner.jpg','質感選物');

INSERT INTO PROMOTION_BANNER
(store_id,default_banner_id,image_url,title,description,status,sort_order)
VALUES
(1,1,'http://localhost:5173/test/banner/banner.jpg','讓生活更有秩序','居家收納與生活用品。','active',1),
(1,2,'http://localhost:5173/test/banner/banner.jpg','日常質感提案','打造舒適居家。','active',2),
(2,3,'http://localhost:5173/test/banner/banner.jpg','每日好食光','零食飲品與日常主食。','active',1),
(2,4,'http://localhost:5173/test/banner/banner.jpg','補給你的日常','方便選購的日常食品。','active',2),
(3,2,'http://localhost:5173/test/banner/banner.jpg','把靈感寫下來','文具與創作用品。','active',1),
(3,4,'http://localhost:5173/test/banner/banner.jpg','文具控的日常','讓書桌更有風格。','active',2),
(4,1,'http://localhost:5173/test/banner/banner.jpg','走進戶外','為旅程準備實用裝備。','active',1),
(4,3,'http://localhost:5173/test/banner/banner.jpg','輕裝出發','露營與戶外用品。','active',2),
(5,5,'http://localhost:5173/test/banner/banner.jpg','穿出你的日常','簡約服飾與實用配件。','active',1),
(5,4,'http://localhost:5173/test/banner/banner.jpg','換季穿搭提案','日常穿搭靈感。','active',2);

INSERT INTO SLIDER_IMAGE
(store_id,image_url,title,status,sort_order)
VALUES
(1,'http://localhost:5173/test/banner/banner.jpg','居家收納','active',1),
(1,'http://localhost:5173/test/banner/banner.jpg','廚房日常','active',2),
(2,'http://localhost:5173/test/banner/banner.jpg','人氣零食','active',1),
(2,'http://localhost:5173/test/banner/banner.jpg','沖泡飲品','active',2),
(3,'http://localhost:5173/test/banner/banner.jpg','書寫工具','active',1),
(3,'http://localhost:5173/test/banner/banner.jpg','手帳時光','active',2),
(4,'http://localhost:5173/test/banner/banner.jpg','露營裝備','active',1),
(4,'http://localhost:5173/test/banner/banner.jpg','戶外餐具','active',2),
(5,'http://localhost:5173/test/banner/banner.jpg','本季上衣','active',1),
(5,'http://localhost:5173/test/banner/banner.jpg','穿搭配件','active',2);

INSERT INTO CUSTOMER_SERVICE
(service_id,customer_id,store_id,order_id,problem_type,description,
 image_url,status,admin_reply,created_at)
VALUES
(1,1,1,1,'商品諮詢','想確認商品的使用方式。',NULL,'resolved','您好，請參考商品說明。','2026-10-02 09:00:00'),
(2,2,2,2,'退款問題','想了解退款處理進度。',NULL,'resolved','退款申請已核准。','2026-10-08 10:30:00'),
(3,3,3,3,'訂單問題','請問訂單何時出貨？',NULL,'pending',NULL,'2026-10-08 14:00:00'),
(4,4,4,4,'商品瑕疵','商品外觀有刮痕。',NULL,'pending',NULL,'2026-10-09 09:30:00'),
(5,5,5,5,'尺寸詢問','想確認商品尺寸。',NULL,'pending',NULL,'2026-10-09 11:00:00');