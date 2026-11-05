<?php
declare(strict_types=1);

/**
 * User Personal Collection & Portfolio Valuation Service.
 *
 * File: shared/services/collection_service.php
 * Mutation Log: 2026-11-04 - Created personal collection manager and real-time market portfolio calculator.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('get_user_collection_portfolio')) {
    /**
     * Retrieves all amulets in a user's showcase portfolio along with current market valuation.
     *
     * @param int $user_id User primary key.
     * @return array<string, mixed> Portfolio data containing items list, total acquisition, current market value, and unrealized gain/loss.
     */
    function get_user_collection_portfolio(int $user_id): array
    {
        log_event('get_user_collection_portfolio', 'START', ['user_id' => $user_id]);
        $pdo = get_db_connection();

        $sql = "
            SELECT uc.*, 
                   a.name AS amulet_name, a.temple_name, a.monk_name, a.year_created, a.material, 
                   a.material_type, a.model_edition, a.province, a.image_path, a.price AS listing_price,
                   c.name AS category_name,
                   cert.cert_number, cert.grade_score, cert.grade_label,
                   (
                       SELECT amp.estimated_price 
                       FROM amulet_market_prices amp 
                       WHERE amp.amulet_id = a.id 
                       ORDER BY amp.recorded_date DESC 
                       LIMIT 1
                   ) AS latest_market_price
            FROM user_collections uc
            JOIN amulets a ON uc.amulet_id = a.id
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN certificates cert ON (cert.amulet_id = a.id AND cert.status = 'active')
            WHERE uc.user_id = ?
            ORDER BY uc.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();

        $total_cost = 0.0;
        $total_market_value = 0.0;
        $formatted_items = [];

        foreach ($items as $row) {
            $cost = (float)$row['acquisition_price'];
            $market = (float)($row['latest_market_price'] ?? $row['listing_price']);
            $gain = $market - $cost;
            $gain_pct = $cost > 0 ? round(($gain / $cost) * 100, 2) : 0.0;

            $total_cost += $cost;
            $total_market_value += $market;

            $formatted_items[] = array_merge($row, [
                'market_value' => $market,
                'gain_loss' => $gain,
                'gain_loss_pct' => $gain_pct,
            ]);
        }

        $net_portfolio_gain = $total_market_value - $total_cost;
        $net_portfolio_pct = $total_cost > 0 ? round(($net_portfolio_gain / $total_cost) * 100, 2) : 0.0;

        $portfolio = [
            'items' => $formatted_items,
            'total_items' => count($formatted_items),
            'total_acquisition_cost' => $total_cost,
            'total_market_value' => $total_market_value,
            'net_gain' => $net_portfolio_gain,
            'net_gain_pct' => $net_portfolio_pct,
        ];

        log_event('get_user_collection_portfolio', 'FINISH', [
            'total_items' => count($formatted_items),
            'market_val' => $total_market_value,
        ]);
        return $portfolio;
    }
}

if (!function_exists('add_amulet_to_collection')) {
    /**
     * Adds an amulet into the user's personal collection vault.
     *
     * @param int    $user_id           Owner ID.
     * @param int    $amulet_id         Amulet ID.
     * @param float  $acquisition_price Cost paid.
     * @param string $notes             Personal provenance or memory notes.
     * @return bool True on success.
     */
    function add_amulet_to_collection(int $user_id, int $amulet_id, float $acquisition_price, string $notes = ''): bool
    {
        log_event('add_amulet_to_collection', 'START', [
            'user_id' => $user_id,
            'amulet_id' => $amulet_id,
        ]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            INSERT INTO user_collections (user_id, amulet_id, acquisition_price, acquisition_date, personal_notes, is_public)
            VALUES (?, ?, ?, CURDATE(), ?, 1)
            ON DUPLICATE KEY UPDATE acquisition_price = VALUES(acquisition_price), personal_notes = VALUES(personal_notes)
        ");
        $res = $stmt->execute([$user_id, $amulet_id, $acquisition_price, $notes]);
        log_event('add_amulet_to_collection', 'FINISH', ['success' => $res]);
        return $res;
    }
}
