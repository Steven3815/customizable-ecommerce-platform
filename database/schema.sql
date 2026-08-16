CREATE DATABASE ecommerce_platform;

USE ecommerce_platform;

CREATE TABLE CUSTOMER (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    address VARCHAR(255),
    preferred_payment VARCHAR(50),
    preferred_delivery VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE STORE (
    store_id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(100) NOT NULL,
    store_url VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    owner_name VARCHAR(100),
    phone VARCHAR(30),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE SUPER_ADMIN (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE CATEGORY (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 1,
    status ENUM('active','inactive','deleted') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(store_id, category_id),

    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE PRODUCT (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    category_id INT NOT NULL,

    product_name VARCHAR(200) NOT NULL,
    description TEXT,

    price DECIMAL(10,2),
    stock INT DEFAULT 0,

    has_spec BOOLEAN DEFAULT FALSE,

    status ENUM('active','inactive','deleted')
    DEFAULT 'active',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(store_id, product_id),

    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id),

    FOREIGN KEY(store_id, category_id)
    REFERENCES CATEGORY(store_id, category_id)
);

CREATE TABLE PRODUCT_SPEC (
    spec_id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT NOT NULL,

    spec_name VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,

    status ENUM('active', 'inactive')
    NOT NULL DEFAULT 'active',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(store_id, product_id, spec_id),

    FOREIGN KEY(store_id, product_id)
    REFERENCES PRODUCT(store_id, product_id)
);

CREATE TABLE PRODUCT_IMAGE (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    product_id INT NOT NULL,

    image_url VARCHAR(500),
    sort_order INT DEFAULT 1,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(store_id, product_id)
    REFERENCES PRODUCT(store_id, product_id)
);

CREATE TABLE CART (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,
    store_id INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(customer_id, store_id),

    FOREIGN KEY(customer_id)
        REFERENCES CUSTOMER(customer_id),

    FOREIGN KEY(store_id)
        REFERENCES STORE(store_id)
);

CREATE TABLE CART_ITEM (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,

    cart_id INT NOT NULL,
    store_id INT NOT NULL,
    product_id INT NOT NULL,
    spec_id INT NULL,

    quantity INT NOT NULL,

    FOREIGN KEY(cart_id)
    REFERENCES CART(cart_id),

    FOREIGN KEY(store_id, product_id)
    REFERENCES PRODUCT(store_id, product_id),

    FOREIGN KEY(store_id, product_id, spec_id)
    REFERENCES PRODUCT_SPEC(store_id, product_id, spec_id)
);

CREATE TABLE ORDERS (
    order_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,
    store_id INT NOT NULL,

    receiver_name VARCHAR(100),
    receiver_phone VARCHAR(30),
    receiver_address VARCHAR(255),

    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    product_amount DECIMAL(10,2),
    shipping_fee DECIMAL(10,2),
    total_amount DECIMAL(10,2),

    delivery_method VARCHAR(50),

    delivery_status ENUM(
        'pending',
        'shipping',
        'completed'
    ) DEFAULT 'pending',

    estimated_ship_date DATE,
    estimated_arrival_date DATE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE(order_id, store_id),

    FOREIGN KEY(customer_id)
        REFERENCES CUSTOMER(customer_id),

    FOREIGN KEY(store_id)
        REFERENCES STORE(store_id)
);

CREATE TABLE ORDER_ITEM (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,
    store_id INT NOT NULL,
    product_id INT NOT NULL,
    spec_id INT NULL,

    product_name VARCHAR(200),
    spec_name VARCHAR(100),

    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY(order_id, store_id)
    REFERENCES ORDERS(order_id, store_id),

    FOREIGN KEY(store_id, product_id, spec_id)
    REFERENCES PRODUCT_SPEC(store_id, product_id, spec_id)
);

CREATE TABLE PAYMENT (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT UNIQUE NOT NULL,
    store_id INT NOT NULL,

    payment_method VARCHAR(50),

    amount DECIMAL(10,2) NOT NULL,

    payment_status ENUM(
        'pending',
        'processing',
        'paid',
        'failed'
    ) DEFAULT 'pending',

    payment_confirm_status ENUM(
        'waiting',
        'confirmed',
        'rejected'
    ),

    payment_note TEXT,

    payment_proof_image VARCHAR(500),

    paid_at DATETIME,
    confirmed_at DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(order_id, store_id)
        REFERENCES ORDERS(order_id, store_id),

    FOREIGN KEY(store_id)
        REFERENCES STORE(store_id)
);

CREATE TABLE REFUND (
    refund_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT UNIQUE NOT NULL,
    store_id INT NOT NULL,

    refund_reason VARCHAR(100),
    refund_description TEXT,

    refund_image_url VARCHAR(500),

    refund_status ENUM(
        'pending',
        'approved',
        'rejected'
    ),

    admin_reply TEXT,

    requested_at DATETIME,
    processed_at DATETIME,

    FOREIGN KEY(order_id, store_id)
        REFERENCES ORDERS(order_id, store_id),

    FOREIGN KEY(store_id)
        REFERENCES STORE(store_id)
);

CREATE TABLE WEBSITE_SETTING (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT UNIQUE NOT NULL,

    intro_section_enable BOOLEAN DEFAULT TRUE,
    banner_section_enable BOOLEAN DEFAULT TRUE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE STORE_SETTING (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT UNIQUE NOT NULL,

    store_status ENUM(
        'open',
        'closed'
    ) DEFAULT 'open',

    store_mode ENUM(
        'shopping',
        'showcase'
    ) DEFAULT 'shopping',

    refund_enable BOOLEAN DEFAULT FALSE,

    refund_days_limit INT DEFAULT 7,

    shipping_days INT DEFAULT 3,

    delivery_days INT DEFAULT 5,

    stock_alert_enable BOOLEAN DEFAULT FALSE,

    stock_alert_threshold INT,

    customer_service_enable BOOLEAN DEFAULT FALSE,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE STORE_PAYMENT_METHOD (
    store_id INT NOT NULL,

    store_payment_id TINYINT NOT NULL,

    payment_method VARCHAR(50) NOT NULL,

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'inactive',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (store_id, store_payment_id),

    UNIQUE (store_id, payment_method),

    FOREIGN KEY (store_id)
        REFERENCES STORE(store_id),

    CHECK (store_payment_id BETWEEN 1 AND 5)
);

CREATE TABLE STORE_PAYMENT_ACCOUNT (
    account_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT NOT NULL,

    store_payment_id TINYINT NOT NULL,

    bank_name VARCHAR(100),

    bank_number VARCHAR(100),

    post_office_number VARCHAR(100),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (store_id, store_payment_id)
        REFERENCES STORE_PAYMENT_METHOD(
            store_id,
            store_payment_id
        )
);

CREATE TABLE STORE_DELIVERY_METHOD (
    store_delivery_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT NOT NULL,

    delivery_method VARCHAR(50),

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE HOMEPAGE_PRODUCT_SETTING (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT UNIQUE NOT NULL,

    sort_type VARCHAR(50),

    display_limit INT DEFAULT 6,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE FOOTER_SETTING (
    footer_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT UNIQUE NOT NULL,

    contact_phone VARCHAR(30),

    address VARCHAR(255),

    email VARCHAR(255),

    service_phone VARCHAR(30),


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE DEFAULT_BANNER (
    default_banner_id INT AUTO_INCREMENT PRIMARY KEY,

    image_url VARCHAR(500),

    name VARCHAR(100),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE PROMOTION_BANNER (
    banner_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT NOT NULL,

    default_banner_id INT NULL,

    image_url VARCHAR(500),

    title VARCHAR(200),

    description TEXT,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id),


    FOREIGN KEY(default_banner_id)
    REFERENCES DEFAULT_BANNER(default_banner_id)
);

CREATE TABLE SLIDER_IMAGE (
    image_id INT AUTO_INCREMENT PRIMARY KEY,

    store_id INT NOT NULL,

    image_url VARCHAR(500),

    sort_order INT DEFAULT 1,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,


    FOREIGN KEY(store_id)
    REFERENCES STORE(store_id)
);

CREATE TABLE CUSTOMER_SERVICE (
    service_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,
    store_id INT NOT NULL,

    order_id INT NULL,

    problem_type VARCHAR(100),
    description TEXT,
    image_url VARCHAR(500),

    status ENUM(
        'pending',
        'resolved'
    ) DEFAULT 'pending',

    admin_reply TEXT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(customer_id)
        REFERENCES CUSTOMER(customer_id),

    FOREIGN KEY(store_id)
        REFERENCES STORE(store_id),

    FOREIGN KEY(order_id, store_id)
        REFERENCES ORDERS(order_id, store_id)
);

ALTER TABLE STORE
ADD UNIQUE (store_url);


ALTER TABLE FOOTER_SETTING
ADD COLUMN contact_phone_enable BOOLEAN DEFAULT FALSE,
ADD COLUMN address_enable BOOLEAN DEFAULT FALSE,
ADD COLUMN email_enable BOOLEAN DEFAULT FALSE,
ADD COLUMN service_phone_enable BOOLEAN DEFAULT FALSE;

ALTER TABLE HOMEPAGE_PRODUCT_SETTING
MODIFY COLUMN display_limit INT NOT NULL DEFAULT 4;

ALTER TABLE HOMEPAGE_PRODUCT_SETTING
ADD CONSTRAINT chk_display_limit
CHECK (display_limit IN (4, 5, 6));

ALTER TABLE PRODUCT
ADD COLUMN sort_order INT NULL DEFAULT 1;

ALTER TABLE PROMOTION_BANNER
ADD COLUMN sort_order INT NULL DEFAULT 1;

ALTER TABLE PROMOTION_BANNER
ADD COLUMN status ENUM('active', 'deleted')
NOT NULL DEFAULT 'active';

ALTER TABLE SLIDER_IMAGE
ADD COLUMN title VARCHAR(200) NULL AFTER image_url;

ALTER TABLE SLIDER_IMAGE
ADD COLUMN status ENUM('active', 'deleted')
NOT NULL DEFAULT 'active';