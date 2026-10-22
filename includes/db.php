<?php
// ============================================================
// Database Connection — Sharon Store System
// ============================================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'sharonstore_db');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // Remote DBs (TiDB, Aiven, Railway, etc.) require SSL
        if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
            $ca = __DIR__ . '/cacert.pem';
            if (file_exists($ca) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            }
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            ensureSchema($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
            }
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection — Sharon Store</title>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
            </head>
            <body class="bg-light p-4">
                <div class="card shadow-sm mx-auto mt-4" style="max-width: 600px; border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3 text-danger">
                            <span style="font-size: 2rem;">⚠️</span>
                            <div>
                                <h4 class="mb-0 fw-bold">Database Connection Required</h4>
                                <small class="text-muted">Sharon Store Management System</small>
                            </div>
                        </div>
                        <p class="text-secondary mb-3">
                            Could not connect to MySQL database at <code><?= htmlspecialchars(DB_HOST) ?></code>:
                        </p>
                        <div class="alert alert-danger py-2 px-3 small font-monospace mb-4">
                            <?= htmlspecialchars($e->getMessage()) ?>
                        </div>
                        <?php if (getenv('VERCEL') || !empty($_SERVER['VERCEL'])): ?>
                        <div class="alert alert-info py-2 px-3 small">
                            <strong>Running on Vercel:</strong> Because Vercel serverless microVMs do not have a local MySQL server, please add your remote cloud MySQL credentials (e.g. from TiDB Cloud, Aiven, or Railway) in <strong>Vercel Dashboard &rarr; Project Settings &rarr; Environment Variables</strong>:
                            <ul class="mb-1 mt-2">
                                <li><code>DB_HOST</code> (cloud MySQL host)</li>
                                <li><code>DB_PORT</code> (default 3306)</li>
                                <li><code>DB_USER</code> (username)</li>
                                <li><code>DB_PASS</code> (password)</li>
                                <li><code>DB_NAME</code> (database name)</li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-secondary py-2 px-3 small">
                            Ensure MySQL is running in your local XAMPP Control Panel and database <code>sharonstore_db</code> is created.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    }
    return $pdo;
}

function getDailyManagerPIN(PDO $db): string {
    $today = date('Y-m-d');
    
    // Check if we have a PIN generated for today
    $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = 'pin_date'");
    $stmt->execute();
    $pinDate = $stmt->fetchColumn();

    if ($pinDate === $today) {
        $stmt = $db->prepare("SELECT setting_value FROM tbl_settings WHERE setting_key = 'daily_pin'");
        $stmt->execute();
        return $stmt->fetchColumn() ?: '0000';
    }

    // Generate a new 4-digit PIN for today
    $newPin = str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);

    // Save/Update settings
    $db->prepare("REPLACE INTO tbl_settings (setting_key, setting_value) VALUES ('pin_date', ?)")->execute([$today]);
    $db->prepare("REPLACE INTO tbl_settings (setting_key, setting_value) VALUES ('daily_pin', ?)")->execute([$newPin]);

    return $newPin;
}

function ensureSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'tbl_users'")->fetchAll();
        if (!empty($tables)) {
            return;
        }

        $queries = [
            "CREATE TABLE IF NOT EXISTS `tbl_categories` (
              `category_id` int NOT NULL AUTO_INCREMENT,
              `category_name` varchar(100) NOT NULL,
              `description` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_users` (
              `user_id` int NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `password` varchar(255) NOT NULL,
              `full_name` varchar(100) NOT NULL,
              `role` enum('admin','cashier') NOT NULL DEFAULT 'cashier',
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`user_id`),
              UNIQUE KEY `uq_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_inventory` (
              `item_id` int NOT NULL AUTO_INCREMENT,
              `barcode` varchar(50) DEFAULT NULL,
              `item_name` varchar(150) NOT NULL,
              `category_id` int NOT NULL,
              `unit` enum('pieces','packs','kg') NOT NULL DEFAULT 'pieces',
              `price` decimal(10,2) NOT NULL DEFAULT 0.00,
              `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
              `stock_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
              `low_stock_threshold` int NOT NULL DEFAULT 10,
              `expiry_date` date DEFAULT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`item_id`),
              UNIQUE KEY `uq_barcode` (`barcode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_suppliers` (
              `supplier_id` int NOT NULL AUTO_INCREMENT,
              `company_name` varchar(100) NOT NULL,
              `contact_person` varchar(100) DEFAULT NULL,
              `category` varchar(500) DEFAULT NULL,
              `phone` varchar(50) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `is_active` tinyint(1) DEFAULT 1,
              PRIMARY KEY (`supplier_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_settings` (
              `setting_key` varchar(50) NOT NULL,
              `setting_value` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_shifts` (
              `shift_id` int NOT NULL AUTO_INCREMENT,
              `cashier_id` int NOT NULL,
              `starting_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
              `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `end_time` datetime DEFAULT NULL,
              `expected_cash` decimal(10,2) DEFAULT NULL,
              `actual_cash` decimal(10,2) DEFAULT NULL,
              `short_over` decimal(10,2) DEFAULT NULL,
              `status` enum('open','closed') NOT NULL DEFAULT 'open',
              PRIMARY KEY (`shift_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_transactions` (
              `transaction_id` int NOT NULL AUTO_INCREMENT,
              `transaction_code` varchar(30) NOT NULL,
              `user_id` int NOT NULL,
              `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
              `amount_tendered` decimal(10,2) NOT NULL DEFAULT 0.00,
              `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
              `status` varchar(20) NOT NULL DEFAULT 'completed',
              `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `shift_id` int DEFAULT NULL,
              PRIMARY KEY (`transaction_id`),
              UNIQUE KEY `uq_txn_code` (`transaction_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_transaction_details` (
              `detail_id` int NOT NULL AUTO_INCREMENT,
              `transaction_id` int NOT NULL,
              `item_id` int NOT NULL,
              `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
              `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
              `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
              PRIMARY KEY (`detail_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_audit_logs` (
              `log_id` int NOT NULL AUTO_INCREMENT,
              `user_id` int DEFAULT NULL,
              `action` varchar(100) NOT NULL,
              `module` varchar(50) NOT NULL,
              `description` text DEFAULT NULL,
              `ip_address` varchar(45) DEFAULT NULL,
              `log_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`log_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `tbl_forecasts` (
              `forecast_id` int NOT NULL AUTO_INCREMENT,
              `item_id` int NOT NULL,
              `forecast_period` date NOT NULL,
              `predicted_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
              `method` varchar(10) NOT NULL DEFAULT 'SMA',
              `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`forecast_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT IGNORE INTO `tbl_users` (`user_id`, `username`, `password`, `full_name`, `role`, `is_active`) VALUES 
            (4,'admin','\$2y\$10\$Kqv6WROs7wHAvrLaZeJyjuA3Z5tuty56bD6iLYkEKyHxIVrxyo2LW','Sharon Dela Cruz','admin',1),
            (5,'cashier1','\$2y\$10\$.HD0Z7AR8eJOoa.ifLwAhOBiwj9sX/4.7pW2dqCcpKL5NiXtXF3.q','Maria Santos','cashier',1),
            (6,'cashier2','\$2y\$12\$HQ/GdytdIIRKO0OfUQSGxuNnbVCAXta4kyj14N3Ijw9OAWEt22zsK','Jose Reyes','cashier',1)",

            "INSERT IGNORE INTO `tbl_categories` (`category_id`, `category_name`, `description`) VALUES 
            (1,'Canned Goods','Canned and preserved food products'),
            (2,'Dry Commodities','Rice, sugar, flour and dry staples'),
            (3,'Household Essentials','Cleaning supplies and household items'),
            (4,'Beverages','Drinks, juices and water products'),
            (5,'Snacks & Confectionery','Chips, crackers and sweet items'),
            (6,'Personal Care','Hygiene and personal care products'),
            (7,'Condiments & Sauces','Vinegar, soy sauce, ketchup, etc.')",

            "INSERT IGNORE INTO `tbl_inventory` (`item_id`, `barcode`, `item_name`, `category_id`, `unit`, `price`, `cost_price`, `stock_qty`, `low_stock_threshold`, `expiry_date`, `is_active`) VALUES 
            (1,'4800012345001','Ligo Sardines in Tomato Sauce 155g',1,'pieces',18.00,13.00,120.00,20,'2027-03-15',1),
            (2,'4800012345002','555 Corned Beef 260g',1,'pieces',65.00,50.00,85.00,15,'2027-06-20',0),
            (3,'4800012345003','Century Tuna Flakes in Oil 180g',1,'pieces',42.00,32.00,93.00,20,'2027-09-10',1),
            (4,'4800012345004','Argentina Corned Beef 150g',1,'pieces',48.00,37.00,60.00,15,'2027-08-05',1),
            (5,'4800012345005','Del Monte Tomato Sauce 250g',1,'pieces',28.00,20.00,59.00,10,'2026-12-31',1),
            (6,'4800023456001','Sinandomeng Rice 5kg',2,'packs',280.00,240.00,50.00,10,'2026-12-01',1),
            (7,'4800023456002','White Sugar 1kg',2,'kg',65.00,52.00,30.00,5,NULL,1),
            (8,'4800023456003','All-Purpose Flour 1kg',2,'kg',55.00,43.00,18.00,5,'2027-01-15',1),
            (9,'4800023456004','Table Salt 500g',2,'packs',15.00,10.00,80.00,15,NULL,1),
            (10,'4800023456005','Brown Sugar 1kg',2,'kg',72.00,58.00,19.00,5,NULL,1),
            (11,'4800034567001','Tide Detergent Powder 1kg',3,'packs',95.00,75.00,45.00,10,'2028-01-01',1),
            (12,'4800034567002','Joy Dishwashing Liquid 500ml',3,'pieces',85.00,65.00,37.00,10,'2028-06-01',1),
            (13,'4800034567003','Ariel Detergent 1kg',3,'packs',102.00,80.00,37.00,10,'2028-01-01',1),
            (14,'4800034567004','Domex Toilet Bowl Cleaner 500ml',3,'pieces',78.00,60.00,24.00,5,'2028-06-01',1),
            (15,'4800034567005','Mr. Clean Multipurpose 500ml',3,'pieces',88.00,68.00,22.00,5,'2028-06-01',1),
            (16,'4800045678001','Coca-Cola 1.5L',4,'pieces',65.00,50.00,70.00,15,'2026-10-01',1),
            (17,'4800045678002','Royal Tru-Orange 1.5L',4,'pieces',60.00,46.00,55.00,15,'2026-10-01',1),
            (18,'4800045678003','Nestea Iced Tea 1L',4,'pieces',45.00,34.00,88.00,20,'2026-11-30',1),
            (19,'4800045678004','Absolute Water 500ml',4,'pieces',15.00,10.00,150.00,30,'2027-06-01',0),
            (20,'4800045678005','Milo Powdered Chocolate 400g',4,'pieces',145.00,115.00,35.00,8,'2027-12-31',1),
            (21,'4800056789001','Nova Country Cheddar 22g',5,'pieces',12.00,8.00,200.00,30,'2026-09-15',1),
            (22,'4800056789002','Piattos Cheese 85g',5,'pieces',38.00,28.00,95.00,20,'2026-10-30',1),
            (23,'4800056789003','Boy Bawang Cornick 100g',5,'pieces',22.00,15.00,133.00,25,'2026-11-30',1),
            (24,'4800056789004','Oishi Marty\'s Crackers 135g',5,'pieces',30.00,22.00,77.00,15,'2026-12-31',1),
            (25,'4800067890001','Palmolive Shampoo 200ml',6,'pieces',98.00,75.00,42.00,10,'2028-12-31',1),
            (26,'4800067890002','Safeguard Soap 135g',6,'pieces',45.00,33.00,88.00,15,'2028-12-31',1),
            (27,'4800067890003','Colgate Toothpaste 150ml',6,'pieces',72.00,55.00,64.00,15,'2028-12-31',1),
            (28,'4800078901001','Datu Puti Vinegar 1L',7,'pieces',38.00,28.00,53.00,10,'2028-01-01',1),
            (29,'4800078901002','Silver Swan Soy Sauce 1L',7,'pieces',42.00,32.00,47.00,10,'2028-01-01',1),
            (30,'4800078901003','UFC Banana Ketchup 320g',7,'pieces',52.00,40.00,38.00,8,'2027-06-30',1),
            (31,'705632085943','baguk',2,'pieces',10.00,5.00,0.00,5,'2027-10-13',1)",

            "INSERT IGNORE INTO `tbl_suppliers` (`supplier_id`, `company_name`, `contact_person`, `category`, `phone`, `address`, `is_active`) VALUES 
            (1,'Chicken Manok Corp.','Toti Tolentino','Poultry and Fresh Meats','+639171234567','Manila, Philippines',1)",

            "INSERT IGNORE INTO `tbl_settings` (`setting_key`, `setting_value`) VALUES 
            ('daily_pin','2795'),
            ('pin_date',CURDATE())"
        ];

        foreach ($queries as $q) {
            $pdo->exec($q);
        }
    } catch (Throwable $t) {
        error_log('ensureSchema warning: ' . $t->getMessage());
    }
}
