<?php
declare(strict_types=1);

/**
 * Auction Service - Real-time Auction Data, Anti-Snipe Extension, and Bidding Logic.
 *
 * File: shared/services/auction_service.php
 * Mutation Log: 2026-11-04 - Created auction retrieval and bidding with 1-min Anti-Snipe (+3 min extension).
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/notification_service.php';

if (!function_exists('get_active_auctions')) {
    /**
     * Retrieves all active auctions with amulet details and highest bidder.
     *
     * @return array<int, array<string, mixed>> List of active auction records.
     */
    function get_active_auctions(): array
    {
        log_event('get_active_auctions', 'START');
        $pdo = get_db_connection();
        $sql = "SELECT auc.*, a.name AS amulet_name, a.temple_name, a.monk_name, a.year_created, a.material, a.image_path,
                       a.stat_protection, a.stat_kindness, a.stat_charm, a.stat_evasion, a.stat_invincibility, a.stat_wealth,
                       c.name AS category_name, u.username AS highest_bidder_name
                FROM auctions auc
                JOIN amulets a ON auc.amulet_id = a.id
                JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON auc.highest_bidder_id = u.id
                ORDER BY auc.end_time ASC";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        log_event('get_active_auctions', 'FINISH', ['count' => count($results)]);
        return $results;
    }
}

if (!function_exists('get_auction_by_id')) {
    /**
     * Retrieves a single auction with related amulet info and bid history.
     *
     * @param int $auction_id Primary key of auction.
     * @return array<string, mixed>|null Auction data or null.
     */
    function get_auction_by_id(int $auction_id): ?array
    {
        log_event('get_auction_by_id', 'START', ['auction_id' => $auction_id]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT auc.*, a.name AS amulet_name, a.temple_name, a.monk_name, a.year_created, a.material, a.description, a.image_path,
                   a.stat_protection, a.stat_kindness, a.stat_charm, a.stat_evasion, a.stat_invincibility, a.stat_wealth,
                   c.name AS category_name, u.username AS highest_bidder_name
            FROM auctions auc
            JOIN amulets a ON auc.amulet_id = a.id
            JOIN categories c ON a.category_id = c.id
            LEFT JOIN users u ON auc.highest_bidder_id = u.id
            WHERE auc.id = ?
        ");
        $stmt->execute([$auction_id]);
        $data = $stmt->fetch();
        log_event('get_auction_by_id', 'FINISH', ['found' => $data !== false]);
        return $data ?: null;
    }
}

if (!function_exists('get_auction_bid_history')) {
    /**
     * Retrieves recent bid history for an auction.
     *
     * @param int $auction_id Auction ID.
     * @param int $limit      Maximum number of rows to return.
     * @return array<int, array<string, mixed>> List of bids.
     */
    function get_auction_bid_history(int $auction_id, int $limit = 20): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT b.*, u.username, u.full_name
            FROM bids b
            JOIN users u ON b.user_id = u.id
            WHERE b.auction_id = ?
            ORDER BY b.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $auction_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('process_auction_bid')) {
    /**
     * Places a bid with atomic transaction, validation, and Anti-Snipe protection:
     * If bid is made in the last 60 seconds (1 minute), extends end_time by 3 minutes (180 seconds).
     *
     * @param int   $auction_id Auction ID.
     * @param int   $user_id    Bidder user ID.
     * @param float $bid_amount Proposed bid amount.
     * @return array<string, mixed> Result payload including new price and whether anti-snipe triggered.
     * @throws Exception If bid is invalid, below minimum, or auction closed.
     */
    function process_auction_bid(int $auction_id, int $user_id, float $bid_amount): array
    {
        log_event('process_auction_bid', 'START', [
            'auction_id' => $auction_id,
            'user_id' => $user_id,
            'bid_amount' => $bid_amount,
        ]);

        $pdo = get_db_connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM auctions WHERE id = ? FOR UPDATE");
            $stmt->execute([$auction_id]);
            $auction = $stmt->fetch();

            if (!$auction) {
                throw new Exception('ไม่พบข้อมูลรายการประมูล');
            }

            if ($auction['status'] !== 'active') {
                throw new Exception('การประมูลรายการนี้สิ้นสุดหรือถูกยกเลิกแล้ว');
            }

            $now_ts = time();
            $end_ts = strtotime((string)$auction['end_time']);

            if ($now_ts >= $end_ts) {
                // Auto mark as ended
                $close_stmt = $pdo->prepare("UPDATE auctions SET status = 'ended' WHERE id = ?");
                $close_stmt->execute([$auction_id]);
                throw new Exception('หมดเวลาการประมูลแล้ว');
            }

            $current_price = (float)$auction['current_price'];
            $min_increment = (float)$auction['min_increment'];
            $min_required = $current_price + $min_increment;

            if ($bid_amount < $min_required) {
                throw new Exception("ราคาประมูลต้องไม่ต่ำกว่า " . number_format($min_required, 2) . " บาท (ขั้นต่ำเพิ่มครั้งละ " . number_format($min_increment, 2) . " บาท)");
            }

            // Anti-Snipe Check: If bid in last 60 seconds, add 3 minutes (180 seconds)
            $remaining_seconds = $end_ts - $now_ts;
            $anti_snipe_triggered = false;
            $new_end_time = (string)$auction['end_time'];

            if ($remaining_seconds <= 60) {
                $anti_snipe_triggered = true;
                $new_end_ts = $end_ts + 180;
                $new_end_time = gmdate('Y-m-d H:i:s', $new_end_ts);
            }

            // Record Bid
            $bid_stmt = $pdo->prepare("
                INSERT INTO bids (auction_id, user_id, bid_amount, triggered_anti_snipe)
                VALUES (?, ?, ?, ?)
            ");
            $bid_stmt->execute([$auction_id, $user_id, $bid_amount, $anti_snipe_triggered ? 1 : 0]);

            // Notify Previous Highest Bidder (Outbid Alert)
            $prev_highest_id = !empty($auction['highest_bidder_id']) ? (int)$auction['highest_bidder_id'] : 0;
            if ($prev_highest_id > 0 && $prev_highest_id !== $user_id) {
                create_user_notification(
                    $prev_highest_id,
                    'outbid',
                    '⚠️ คุณถูกเคาะราคาแซงแล้ว!',
                    "มีผู้เคาะสู้ราคาในรายการประมูล #{$auction_id} เป็นจำนวน " . number_format($bid_amount, 2) . " บาท เคาะสู้ต่อเลยไหม?",
                    "auction_detail.php?id={$auction_id}"
                );
            }

            // Update Auction state
            if ($anti_snipe_triggered) {
                $upd_stmt = $pdo->prepare("
                    UPDATE auctions 
                    SET current_price = ?, highest_bidder_id = ?, end_time = ?, anti_snipe_count = anti_snipe_count + 1
                    WHERE id = ?
                ");
                $upd_stmt->execute([$bid_amount, $user_id, $new_end_time, $auction_id]);
            } else {
                $upd_stmt = $pdo->prepare("
                    UPDATE auctions 
                    SET current_price = ?, highest_bidder_id = ?
                    WHERE id = ?
                ");
                $upd_stmt->execute([$bid_amount, $user_id, $auction_id]);
            }

            $pdo->commit();

            log_event('process_auction_bid', 'FINISH', [
                'current_price' => $bid_amount,
                'anti_snipe_triggered' => $anti_snipe_triggered,
                'new_end_time' => $new_end_time,
            ]);

            return [
                'success' => true,
                'current_price' => $bid_amount,
                'end_time' => $new_end_time,
                'anti_snipe_extended' => $anti_snipe_triggered,
                'message' => $anti_snipe_triggered
                    ? 'เสนอราคาสำเร็จ! และระบบขยายเวลาต่ออีก 3 นาที (Anti-Snipe Protection)'
                    : 'เสนอราคาสำเร็จเรียบร้อย!',
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            log_event('process_auction_bid', 'ERROR', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

