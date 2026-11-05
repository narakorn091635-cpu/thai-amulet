<?php
declare(strict_types=1);

/**
 * Dual Leaderboard & Hall of Fame Data Service (Module Amulets & Collectors).
 *
 * File: shared/services/leaderboard_service.php
 * Mutation Log: 2026-11-04 - Created Top Graded, Top Value & Bids, Sacred Properties, Trending, and Collector Leaderboard queries.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('get_top_graded_masterpieces')) {
    /**
     * Leaderboard: Top Graded Masterpieces (ทำเนียบพระแชมป์)
     * Ranks by PSA grade score DESC, then by rarity (stock_qty ASC).
     *
     * @param int $limit Max records.
     * @return array<int, array<string, mixed>>
     */
    function get_top_graded_masterpieces(int $limit = 5): array
    {
        log_event('get_top_graded_masterpieces', 'START');
        $pdo = get_db_connection();
        $sql = "
            SELECT a.*, c.cert_number, c.grade_score, c.grade_label, c.current_owner_name,
                   cat.name AS category_name
            FROM certificates c
            JOIN amulets a ON c.amulet_id = a.id
            JOIN categories cat ON a.category_id = cat.id
            WHERE c.status = 'active'
            ORDER BY c.grade_score DESC, a.stock_qty ASC, a.id ASC
            LIMIT ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        log_event('get_top_graded_masterpieces', 'FINISH', ['count' => count($rows)]);
        return $rows;
    }
}

if (!function_exists('get_top_value_and_bids')) {
    /**
     * Leaderboard: Top Value & Most Bids (มูลค่าและสถิติประมูลสูงสุด)
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    function get_top_value_and_bids(): array
    {
        log_event('get_top_value_and_bids', 'START');
        $pdo = get_db_connection();

        // 1. Top Value (Highest price / market appraisal)
        $top_value_sql = "
            SELECT a.*, cat.name AS category_name,
                   COALESCE((SELECT MAX(estimated_price) FROM amulet_market_prices WHERE amulet_id = a.id), a.price) AS benchmark_val
            FROM amulets a
            JOIN categories cat ON a.category_id = cat.id
            ORDER BY benchmark_val DESC
            LIMIT 5
        ";
        $top_value = $pdo->query($top_value_sql)->fetchAll();

        // 2. Most Heated Auction Bids (สู้ราคาดุเดือดสุด)
        $most_bids_sql = "
            SELECT a.*, auc.id AS auction_id, auc.current_price, auc.start_price,
                   COUNT(b.id) AS total_bids_count
            FROM amulets a
            JOIN auctions auc ON auc.amulet_id = a.id
            LEFT JOIN bids b ON b.auction_id = auc.id
            GROUP BY auc.id
            ORDER BY total_bids_count DESC, auc.current_price DESC
            LIMIT 5
        ";
        $most_bids = $pdo->query($most_bids_sql)->fetchAll();

        log_event('get_top_value_and_bids', 'FINISH');
        return [
            'top_value' => $top_value,
            'most_bids' => $most_bids,
        ];
    }
}

if (!function_exists('get_top_by_sacred_properties')) {
    /**
     * Leaderboard: Top by Properties (ท็อปชาร์ตตามสายสรรพคุณ)
     *
     * @param string $property Attribute key ('invulnerable', 'kindness', 'wealth').
     * @param int    $limit Max records.
     * @return array<int, array<string, mixed>>
     */
    function get_top_by_sacred_properties(string $property = 'wealth', int $limit = 5): array
    {
        log_event('get_top_by_sacred_properties', 'START', ['property' => $property]);
        $pdo = get_db_connection();

        $column_map = [
            'invulnerable' => 'stat_invincibility', // คงกระพันชาตรี / มหาอุด
            'kindness' => 'stat_kindness',          // เมตตามหานิยม / เสน่ห์
            'wealth' => 'stat_wealth',              // โชคลาภ / ค้าขาย
            'protection' => 'stat_protection',      // แคล้วคลาด พุทธคุณคุ้มครอง
        ];
        $col = $column_map[$property] ?? 'stat_wealth';

        $sql = "
            SELECT a.*, cat.name AS category_name
            FROM amulets a
            JOIN categories cat ON a.category_id = cat.id
            ORDER BY a.{$col} DESC, a.price DESC
            LIMIT ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        log_event('get_top_by_sacred_properties', 'FINISH', ['count' => count($rows)]);
        return $rows;
    }
}

if (!function_exists('get_trending_amulets')) {
    /**
     * Leaderboard: Trending Amulets (พระยอดฮิตมาแรง)
     * Ranks by views + wishlist count weight.
     *
     * @param int $limit Max records.
     * @return array<int, array<string, mixed>>
     */
    function get_trending_amulets(int $limit = 5): array
    {
        log_event('get_trending_amulets', 'START');
        $pdo = get_db_connection();
        $sql = "
            SELECT a.*, cat.name AS category_name,
                   (a.view_count + (a.wishlist_count * 5)) AS popularity_score
            FROM amulets a
            JOIN categories cat ON a.category_id = cat.id
            ORDER BY popularity_score DESC, a.view_count DESC
            LIMIT ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        log_event('get_trending_amulets', 'FINISH', ['count' => count($rows)]);
        return $rows;
    }
}

if (!function_exists('get_collector_leaderboard')) {
    /**
     * Collector Leaderboard & Hall of Fame (ฝั่งนักสะสมและเซียนพระ)
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    function get_collector_leaderboard(): array
    {
        log_event('get_collector_leaderboard', 'START');
        $pdo = get_db_connection();

        // 1. เจ้ากรมพระ: Highest Portfolio Value
        $top_portfolio_sql = "
            SELECT u.id, u.username, u.full_name, uts.honor_title, uts.hall_of_fame,
                   COUNT(uc.id) AS total_items_count,
                   COALESCE(SUM(
                       COALESCE((SELECT MAX(estimated_price) FROM amulet_market_prices WHERE amulet_id = uc.amulet_id), a.price)
                   ), 0.00) AS portfolio_market_value
            FROM users u
            LEFT JOIN user_trader_stats uts ON u.id = uts.user_id
            JOIN user_collections uc ON u.id = uc.user_id
            JOIN amulets a ON uc.amulet_id = a.id
            GROUP BY u.id
            ORDER BY portfolio_market_value DESC
            LIMIT 5
        ";
        $top_portfolio = $pdo->query($top_portfolio_sql)->fetchAll();

        // 2. นักสะสมของแรร์: Top Quality Collector (เกรดพระสูงสุดเฉลี่ย)
        $top_quality_sql = "
            SELECT u.id, u.username, u.full_name, uts.honor_title,
                   COUNT(cert.id) AS certified_count,
                   MAX(cert.grade_score) AS highest_grade,
                   AVG(cert.grade_score) AS avg_grade
            FROM users u
            LEFT JOIN user_trader_stats uts ON u.id = uts.user_id
            JOIN user_collections uc ON u.id = uc.user_id
            JOIN certificates cert ON cert.amulet_id = uc.amulet_id
            GROUP BY u.id
            ORDER BY highest_grade DESC, certified_count DESC
            LIMIT 5
        ";
        $top_quality = $pdo->query($top_quality_sql)->fetchAll();

        // 3. เซียนนักเทรด & หอเกียรติยศ (Hall of Fame): Top Trusted Traders
        $top_traders_sql = "
            SELECT u.id, u.username, u.full_name, uts.*
            FROM users u
            JOIN user_trader_stats uts ON u.id = uts.user_id
            ORDER BY uts.hall_of_fame DESC, uts.reputation_points DESC, uts.successful_deals DESC
            LIMIT 6
        ";
        $top_traders = $pdo->query($top_traders_sql)->fetchAll();

        log_event('get_collector_leaderboard', 'FINISH');
        return [
            'top_portfolio' => $top_portfolio,
            'top_quality' => $top_quality,
            'top_traders' => $top_traders,
        ];
    }
}
