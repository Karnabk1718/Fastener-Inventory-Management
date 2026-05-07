USE fastener_db;

ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(120) NULL AFTER username;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(15) NULL AFTER email;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_otp VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_otp_expires DATETIME NULL;
ALTER TABLE users ADD UNIQUE KEY IF NOT EXISTS users_username_unique (username);
ALTER TABLE users ADD UNIQUE KEY IF NOT EXISTS users_email_unique (email);
ALTER TABLE users ADD UNIQUE KEY IF NOT EXISTS users_phone_unique (phone);
ALTER TABLE fastener ADD COLUMN IF NOT EXISTS part_number VARCHAR(80) NULL AFTER name;

CREATE TABLE IF NOT EXISTS pick_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fastener_id INT NOT NULL,
    quantity INT NOT NULL,
    picked_by VARCHAR(100) NULL,
    picked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX pick_list_fastener_idx (fastener_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO supplier (id, name, contact, address, contract_date) VALUES
(1, 'Apex Industrial Fasteners', '9876543210', 'Peenya Industrial Area, Bengaluru', '2026-01-15'),
(2, 'Dharwad Precision Tools', '9123456789', 'Vidyagiri, Dharwad', '2026-02-02'),
(3, 'Metro Alloy Supplies', '9988776655', 'MIDC Area, Pune', '2026-02-20'),
(4, 'Coastal Hardware Works', '9765432109', 'Baikampady Industrial Area, Mangaluru', '2026-03-05');

INSERT IGNORE INTO fastener (id, name, part_number, type, size, unit_price, description, image) VALUES
(1, 'Hex Head Bolt', 'HHB-10', 'Bolt', '10mm', 8.50, 'Zinc-plated hex bolt for medium-duty machine assembly.', 'fastener_69e9cf614cf3f.jpeg'),
(2, 'Socket Cap Screw', 'SCS-06', 'Screw', '6mm', 5.75, 'High-tensile socket cap screw for precision fixtures.', 'fastener_69e9cf4926872.jpeg'),
(3, 'Nylon Lock Nut', 'NLN-08', 'Nut', '8mm', 3.25, 'Vibration-resistant lock nut with nylon insert.', 'fastener_69e9cf3c6bc93.jpeg'),
(4, 'Flat Washer', 'FW-12', 'Washer', '12mm', 1.40, 'Mild-steel washer for load distribution and surface protection.', 'fastener_69e9cf2d7c486.jpeg'),
(5, 'Countersunk Screw', 'CSS-05', 'Screw', '5mm', 4.10, 'Flush-fit countersunk screw for panels and covers.', 'fastener_69e9cf1e270bf.jpeg');

INSERT IGNORE INTO stock (id, fastener_id, quantity) VALUES
(1, 1, 180),
(2, 2, 95),
(3, 3, 240),
(4, 4, 420),
(5, 5, 60);

INSERT IGNORE INTO orders (order_id, fastener_id, supplier_id, quantity, order_date, status) VALUES
(1, 1, 1, 100, '2026-04-01', 'Delivered'),
(2, 2, 2, 75, '2026-04-08', 'Pending'),
(3, 3, 3, 150, '2026-04-18', 'Delivered'),
(4, 5, 4, 80, '2026-05-02', 'Pending');
