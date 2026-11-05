<?php
declare(strict_types=1);

/**
 * Amulet Service - Business Logic for Catalog, Filtering, and Details.
 *
 * File: shared/services/amulet_service.php
 * Mutation Log: 2026-11-04 - Created search, filter, and detail retrieval functions.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('get_amulet_categories')) {
    /**
     * Retrieves all active amulet categories.
     *
     * @return array<int, array<string, mixed>> List of categories.
     */
    function get_amulet_categories(): array
    {
        log_event('get_amulet_categories', 'START');
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT id, name, slug, description FROM categories ORDER BY id ASC");
        $results = $stmt->fetchAll();
        log_event('get_amulet_categories', 'FINISH', ['count' => count($results)]);
        return $results;
    }
}

if (!function_exists('search_marketplace_amulets')) {
    /**
     * Deep Searches and filters marketplace amulets by category, keyword, monk, temple, province, material, and edition.
     *
     * @param int|null    $category_id   Filter by specific category ID.
     * @param string|null $keyword       Search keyword for name, temple_name, or monk_name.
     * @param string|null $monk_name     Specific monk filter.
     * @param string|null $temple_name   Specific temple filter.
     * @param string|null $province      Province filter (เช่น กรุงเทพมหานคร, พระนครศรีอยุธยา).
     * @param string|null $material_type Material group filter ('powder', 'clay', 'chin', 'metal', 'other').
     * @param string|null $model_edition Model or Edition filter (เช่น รุ่นแรก, พิมพ์ใหญ่).
     * @return array<int, array<string, mixed>> Matching amulet listings.
     */
    function search_marketplace_amulets(
        ?int $category_id = null,
        ?string $keyword = null,
        ?string $monk_name = null,
        ?string $temple_name = null,
        ?string $province = null,
        ?string $material_type = null,
        ?string $model_edition = null
    ): array {
        log_event('search_marketplace_amulets', 'START', [
            'category_id' => $category_id,
            'keyword' => $keyword,
            'monk_name' => $monk_name,
            'temple_name' => $temple_name,
            'province' => $province,
            'material_type' => $material_type,
            'model_edition' => $model_edition,
        ]);

        $pdo = get_db_connection();
        $conditions = ["a.listing_type = 'marketplace'", "a.is_active = 1"];
        $params = [];

        if ($category_id !== null && $category_id > 0) {
            $conditions[] = "a.category_id = ?";
            $params[] = $category_id;
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $trimmed = trim($keyword);
            $conditions[] = "(a.name LIKE ? OR a.temple_name LIKE ? OR a.monk_name LIKE ? OR a.model_edition LIKE ?)";
            $wildcard = "%{$trimmed}%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($monk_name !== null && trim($monk_name) !== '') {
            $conditions[] = "a.monk_name LIKE ?";
            $params[] = "%" . trim($monk_name) . "%";
        }

        if ($temple_name !== null && trim($temple_name) !== '') {
            $conditions[] = "a.temple_name LIKE ?";
            $params[] = "%" . trim($temple_name) . "%";
        }

        if ($province !== null && trim($province) !== '') {
            $conditions[] = "a.province LIKE ?";
            $params[] = "%" . trim($province) . "%";
        }

        if ($material_type !== null && trim($material_type) !== '') {
            $conditions[] = "a.material_type = ?";
            $params[] = trim($material_type);
        }

        if ($model_edition !== null && trim($model_edition) !== '') {
            $conditions[] = "a.model_edition LIKE ?";
            $params[] = "%" . trim($model_edition) . "%";
        }

        $sql = "SELECT a.*, c.name AS category_name
                FROM amulets a
                JOIN categories c ON a.category_id = c.id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY a.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        log_event('search_marketplace_amulets', 'FINISH', ['count' => count($results)]);
        return $results;
    }
}

if (!function_exists('get_amulet_by_id')) {
    /**
     * Retrieves a single amulet record with its 6 status attributes.
     *
     * @param int $amulet_id Amulet primary key.
     * @return array<string, mixed>|null Amulet record or null if not found.
     */
    function get_amulet_by_id(int $amulet_id): ?array
    {
        log_event('get_amulet_by_id', 'START', ['amulet_id' => $amulet_id]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT a.*, c.name AS category_name FROM amulets a JOIN categories c ON a.category_id = c.id WHERE a.id = ?");
        $stmt->execute([$amulet_id]);
        $record = $stmt->fetch();
        log_event('get_amulet_by_id', 'FINISH', ['found' => $record !== false]);
        return $record ?: null;
    }
}

if (!function_exists('get_amulet_market_price_history')) {
    /**
     * Retrieves historical and current market benchmark prices for a specific amulet.
     *
     * @param int $amulet_id Amulet primary key.
     * @return array<int, array<string, mixed>> List of market price records ordered by date.
     */
    function get_amulet_market_price_history(int $amulet_id): array
    {
        log_event('get_amulet_market_price_history', 'START', ['amulet_id' => $amulet_id]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT id, amulet_id, recorded_date, period_label, estimated_price, price_source, source_type
            FROM amulet_market_prices
            WHERE amulet_id = ?
            ORDER BY recorded_date ASC
        ");
        $stmt->execute([$amulet_id]);
        $rows = $stmt->fetchAll();
        log_event('get_amulet_market_price_history', 'FINISH', ['count' => count($rows)]);
        return $rows;
    }
}

if (!function_exists('calculate_market_price_comparison')) {
    /**
     * Compares the seller listing price with the latest verified market average.
     *
     * @param float $listed_price The seller's asking or starting price.
     * @param array<int, array<string, mixed>> $price_history Array of market price records.
     * @return array<string, mixed> Comparison analysis containing difference, percent, and deal status.
     */
    function calculate_market_price_comparison(float $listed_price, array $price_history): array
    {
        log_event('calculate_market_price_comparison', 'START', ['listed_price' => $listed_price]);
        
        $latest_market = null;
        if (!empty($price_history)) {
            // Pick the newest recorded price
            $latest_market = end($price_history);
        }

        if (!$latest_market) {
            $comparison = [
                'has_data' => false,
                'market_price' => 0.0,
                'diff' => 0.0,
                'diff_percent' => 0.0,
                'status' => 'no_benchmark',
                'badge_text' => 'ไม่มีข้อมูลราคากลาง',
                'badge_class' => 'neutral',
            ];
            log_event('calculate_market_price_comparison', 'FINISH', $comparison);
            return $comparison;
        }

        $market_price = (float)$latest_market['estimated_price'];
        $diff = $listed_price - $market_price;
        $diff_percent = $market_price > 0 ? round(($diff / $market_price) * 100, 1) : 0.0;

        if ($diff < 0) {
            $abs_percent = abs($diff_percent);
            $badge_text = "ต่ำกว่าราคากลาง {$abs_percent}% (น่าบูชาอย่างยิ่ง)";
            $badge_class = "deal_good";
        } elseif ($diff > 0) {
            $badge_text = "สูงกว่าราคากลาง {$diff_percent}% (สภาพแชมป์/คัดสวย)";
            $badge_class = "deal_premium";
        } else {
            $badge_text = "ตรงตามราคากลางตลาดพอดี";
            $badge_class = "deal_fair";
        }

        $comparison = [
            'has_data' => true,
            'market_price' => $market_price,
            'diff' => $diff,
            'diff_percent' => $diff_percent,
            'status' => $diff <= 0 ? 'good' : 'premium',
            'badge_text' => $badge_text,
            'badge_class' => $badge_class,
            'latest_label' => $latest_market['period_label'] ?? 'ราคาปัจจุบัน',
            'source' => $latest_market['price_source'] ?? 'ตลาดพระเครื่องส่วนกลาง',
        ];

        log_event('calculate_market_price_comparison', 'FINISH', $comparison);
        return $comparison;
    }
}

