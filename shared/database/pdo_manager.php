<?php
declare(strict_types=1);

/**
 * Reusable PDO Database Connection Manager with SQLite fallback when MySQL server is offline.
 *
 * File: shared/database/pdo_manager.php
 * Mutation Log: 2026-11-04 - Added intelligent SQLite self-provisioning fallback for development offline environment.
 */

require_once __DIR__ . '/../utils/logger.php';

if (!function_exists('create_pdo_connection')) {
    /**
     * Establishes and returns a configured PDO connection instance.
     * Tries MySQL first; if unavailable in local offline dev sandbox, seamlessly initializes SQLite.
     *
     * @param array<string, mixed> $db_config Associative array containing driver, host, port, database, username, password, charset.
     * @param bool                 $auto_create_db Whether to automatically create the database if it does not exist yet.
     * @return PDO Configured PDO connection instance with ERRMODE_EXCEPTION and FETCH_ASSOC.
     */
    function create_pdo_connection(array $db_config, bool $auto_create_db = true): PDO
    {
        log_event('create_pdo_connection', 'START', [
            'host' => $db_config['host'] ?? 'unknown',
            'database' => $db_config['database'] ?? 'unknown',
        ]);

        $driver = (string)($db_config['driver'] ?? 'mysql');
        $host = (string)($db_config['host'] ?? '127.0.0.1');
        $port = (int)($db_config['port'] ?? 3306);
        $database = (string)($db_config['database'] ?? 'thai_amulet_db');
        $username = (string)($db_config['username'] ?? 'root');
        $password = (string)($db_config['password'] ?? '');
        $charset = (string)($db_config['charset'] ?? 'utf8mb4');
        $collation = (string)($db_config['collation'] ?? 'utf8mb4_unicode_ci');

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // 1. Try MySQL Connection if driver is mysql
        if ($driver === 'mysql') {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            try {
                $pdo = new PDO($dsn, $username, $password, $options);
                log_event('create_pdo_connection', 'FINISH', ['driver' => 'mysql', 'status' => 'connected']);
                return $pdo;
            } catch (PDOException $mysql_err) {
                // If database doesn't exist, try creating it
                if ($auto_create_db && (int)$mysql_err->getCode() === 1049) {
                    try {
                        $root_dsn = "mysql:host={$host};port={$port};charset={$charset}";
                        $root_pdo = new PDO($root_dsn, $username, $password, $options);
                        $safe_db_name = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
                        $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safe_db_name}` CHARACTER SET {$charset} COLLATE {$collation};");
                        $pdo = new PDO($dsn, $username, $password, $options);
                        log_event('create_pdo_connection', 'FINISH', ['driver' => 'mysql', 'status' => 'created_and_connected']);
                        return $pdo;
                    } catch (PDOException $create_err) {
                        log_event('create_pdo_connection', 'WARNING', ['mysql_create_error' => $create_err->getMessage()]);
                    }
                }

                // If MySQL service is offline or unreachable, fall back to SQLite for seamless development
                log_event('create_pdo_connection', 'WARNING', [
                    'reason' => 'MySQL offline; falling back to SQLite for development resilience',
                    'error' => $mysql_err->getMessage(),
                ]);
            }
        }

        // 2. SQLite Database File Fallback
        $sqlite_file = __DIR__ . '/../../database/thai_amulet_db.sqlite';
        $sqlite_dsn = "sqlite:{$sqlite_file}";
        $pdo = new PDO($sqlite_dsn, null, null, $options);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        // Initialize SQLite schema and sample seeds if empty
        init_sqlite_schema_if_needed($pdo);

        log_event('create_pdo_connection', 'FINISH', ['driver' => 'sqlite', 'status' => 'connected']);
        return $pdo;
    }
}

if (!function_exists('init_sqlite_schema_if_needed')) {
    /**
     * Bootstraps SQLite tables and initial seed data if tables are not yet created.
     *
     * @param PDO $pdo Active PDO connection to SQLite database.
     * @return void
     */
    function init_sqlite_schema_if_needed(PDO $pdo): void
    {
        log_event('init_sqlite_schema_if_needed', 'START');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL,
                phone TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS amulets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                temple_name TEXT NOT NULL,
                monk_name TEXT NOT NULL,
                year_created TEXT DEFAULT NULL,
                material TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                price REAL NOT NULL DEFAULT 0.0,
                stock_qty INTEGER NOT NULL DEFAULT 1,
                listing_type TEXT NOT NULL DEFAULT 'marketplace',
                image_path TEXT DEFAULT NULL,
                stat_protection INTEGER NOT NULL DEFAULT 8,
                stat_kindness INTEGER NOT NULL DEFAULT 8,
                stat_charm INTEGER NOT NULL DEFAULT 7,
                stat_evasion INTEGER NOT NULL DEFAULT 8,
                stat_invincibility INTEGER NOT NULL DEFAULT 7,
                stat_wealth INTEGER NOT NULL DEFAULT 8,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories (id)
            );

            CREATE TABLE IF NOT EXISTS auctions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                amulet_id INTEGER NOT NULL UNIQUE,
                start_price REAL NOT NULL,
                current_price REAL NOT NULL,
                min_increment REAL NOT NULL DEFAULT 100.0,
                highest_bidder_id INTEGER DEFAULT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                anti_snipe_count INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (amulet_id) REFERENCES amulets (id),
                FOREIGN KEY (highest_bidder_id) REFERENCES users (id)
            );

            CREATE TABLE IF NOT EXISTS bids (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                auction_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                bid_amount REAL NOT NULL,
                triggered_anti_snipe INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (auction_id) REFERENCES auctions (id),
                FOREIGN KEY (user_id) REFERENCES users (id)
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                total_amount REAL NOT NULL,
                slip_image_path TEXT DEFAULT NULL,
                shipping_address TEXT NOT NULL,
                order_status TEXT NOT NULL DEFAULT 'pending_slip_verify',
                tracking_number TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id)
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                amulet_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                unit_price REAL NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders (id),
                FOREIGN KEY (amulet_id) REFERENCES amulets (id)
            );
        ");

        // Seed default Admin & User accounts if missing
        $user_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($user_count === 0) {
            $admin_hash = password_hash('admin1234', PASSWORD_DEFAULT);
            $user_hash = password_hash('user1234', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@amulet.test', $admin_hash, 'ผู้ดูแลระบบสูงสุด', '0812345678', 'สำนักงานใหญ่ กรุงเทพฯ', 'admin']);
            $stmt->execute(['user1', 'user1@amulet.test', $user_hash, 'สมชาย สายศรัทธา', '0898765432', '123/45 ถ.เจริญกรุง กรุงเทพฯ', 'user']);
        }

        // Seed default Categories
        $cat_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        if ($cat_count === 0) {
            $pdo->exec("
                INSERT INTO categories (id, name, slug, description) VALUES
                (1, 'พระสมเด็จ', 'phra_somdej', 'พระสมเด็จวัดระฆัง บางขุนพรหม เกศไชโย ยอดนิยม'),
                (2, 'พระเหรียญ', 'phra_rian', 'เหรียญคณาจารย์ยอดนิยม หลวงปู่ทวด หลวงพ่อรวย'),
                (3, 'เครื่องรางของขลัง', 'khrueang_rang', 'ตะกรุด เบี้ยแก้ หนุมาน มีดหมอ มหาอำนาจ');
            ");
        }

        // Seed sample Amulets (Marketplace & Auction)
        $amulet_count = (int)$pdo->query("SELECT COUNT(*) FROM amulets")->fetchColumn();
        if ($amulet_count === 0) {
            $pdo->exec("
                INSERT INTO amulets (
                    id, category_id, name, temple_name, monk_name, year_created, material, description,
                    price, stock_qty, listing_type, image_path,
                    stat_protection, stat_kindness, stat_charm, stat_evasion, stat_invincibility, stat_wealth, is_active
                ) VALUES
                (1, 1, 'พระสมเด็จวัดระฆัง พิมพ์ใหญ่ พิมพ์นิยม', 'วัดระฆังโฆสิตาราม', 'สมเด็จพระพุฒาจารย์ (โต พรหมรังสี)', '2410', 'เนื้อผงพุทธคุณ', 'พระสมเด็จวัดระฆังพิมพ์ใหญ่ พระประธาน สง่างาม พุทธคุณครอบจักรวาล สภาพสวยงามเดิมๆ', 250000.0, 1, 'marketplace', 'images/sample_somdej.png', 10, 9, 8, 9, 8, 10, 1),
                (2, 2, 'เหรียญเสมา หลวงพ่อรวย รุ่นรวยชนะจน', 'วัดตะโก', 'หลวงพ่อรวย ปาสาทิโก', '2556', 'เนื้อทองแดงรมดำ', 'เหรียญยอดนิยมด้านโชคลาภ ค้าขาย ร่ำรวย ชนะความยากจน', 45000.0, 2, 'marketplace', 'images/sample_ruay.png', 8, 9, 8, 8, 7, 10, 1),
                (3, 3, 'เบี้ยแก้ ป้องกันคุณไสย ยาสั่ง', 'วัดกลางบางแก้ว', 'หลวงปู่บุญ ขนฺธโชติ', '2460', 'ปรอท หุ้มตะกั่วถักเชือก', 'สุดยอดเครื่องรางป้องกันภัย สเนียดจัญไร ปัดเป่าสิ่งชั่วร้าย แคล้วคลาดปลอดภัย', 35000.0, 1, 'marketplace', 'images/sample_biagae.png', 9, 7, 6, 10, 9, 7, 1),
                (4, 2, 'เหรียญเปิดโลก หลวงปู่ดู่ วัดสะแก', 'วัดสะแก', 'หลวงปู่ดู่ พรหมปัญโญ', '2532', 'เนื้อตะกั่ว', 'เหรียญเปิดสามโลก พุทธคุณเปิดโชคชะตา แคล้วคลาด เจริญรุ่งเรือง เปิดประมูลราคาพิเศษ', 12000.0, 1, 'auction', 'images/sample_perdlok.png', 10, 9, 7, 9, 8, 9, 1);
            ");

            // Seed auction record for amulet #4 (Ending in 1 hour from now)
            $end_time = gmdate('Y-m-d H:i:s', time() + 3600);
            $start_time = gmdate('Y-m-d H:i:s', time() - 3600);
            $pdo->exec("
                INSERT INTO auctions (
                    id, amulet_id, start_price, current_price, min_increment, highest_bidder_id,
                    start_time, end_time, anti_snipe_count, status
                ) VALUES
                (1, 4, 12000.0, 15000.0, 500.0, 2, '{$start_time}', '{$end_time}', 0, 'active');

                INSERT INTO bids (auction_id, user_id, bid_amount, triggered_anti_snipe)
                VALUES (1, 2, 15000.0, 0);
            ");
        }

        log_event('init_sqlite_schema_if_needed', 'FINISH');
    }
}
