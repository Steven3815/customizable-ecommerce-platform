USE ecommerce_platform;

SELECT *
FROM CUSTOMER;

SELECT *
FROM STORE;

SELECT *
FROM CATEGORY;

SELECT *
FROM PRODUCT;

SELECT *
FROM PRODUCT_SPEC;

SELECT
    p.product_name,
    ps.spec_name,
    ps.price,
    ps.stock
FROM PRODUCT p
LEFT JOIN PRODUCT_SPEC ps
ON p.product_id = ps.product_id;

SELECT *
FROM CART;

SELECT
    c.customer_id,
    p.product_name,
    ps.spec_name,
    ci.quantity
FROM CART_ITEM ci
JOIN CART c
ON ci.cart_id = c.cart_id
JOIN PRODUCT p
ON ci.product_id = p.product_id
LEFT JOIN PRODUCT_SPEC ps
ON ci.spec_id = ps.spec_id;

SELECT *
FROM ORDERS;

SELECT
    o.order_id,
    oi.product_name,
    oi.spec_name,
    oi.quantity,
    oi.price
FROM ORDERS o
JOIN ORDER_ITEM oi
ON o.order_id = oi.order_id;

SELECT
    o.order_id,
    p.payment_method,
    p.payment_status,
    p.payment_confirm_status
FROM ORDERS o
JOIN PAYMENT p
ON o.order_id = p.order_id;

SELECT
    r.refund_id,
    o.order_id,
    r.refund_reason,
    r.refund_status
FROM REFUND r
JOIN ORDERS o
ON r.order_id = o.order_id;

SELECT
    spm.payment_method,
    spa.bank_name,
    spa.account_number
FROM STORE_PAYMENT_METHOD spm
LEFT JOIN STORE_PAYMENT_ACCOUNT spa
ON spm.store_payment_id = spa.store_payment_id;

SELECT
    cs.service_id,
    c.name,
    o.order_id,
    cs.problem_type,
    cs.status
FROM CUSTOMER_SERVICE cs
JOIN CUSTOMER c
ON cs.customer_id = c.customer_id
LEFT JOIN ORDERS o
ON cs.order_id = o.order_id;

