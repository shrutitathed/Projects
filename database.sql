CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    p_name VARCHAR(100),
    quantity INT,
    total_imported INT,
    cost_price DECIMAL(10,2),
    selling_price DECIMAL(10,2)
);
CREATE TABLE sales_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    product_name VARCHAR(100),
    qty_sold INT,
    profit_earned DECIMAL(10,2),
    sale_date DATETIME
);
CREATE TABLE damage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    product_name VARCHAR(100),
    qty_damaged INT,
    loss_amount DECIMAL(10,2),
    damage_date DATETIME DEFAULT CURRENT_TIMESTAMP
);
