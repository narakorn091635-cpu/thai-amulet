<?php
declare(strict_types=1);

/**
 * Notification Service - Outbid, Order Status, and Monk Follower Alerts.
 *
 * File: shared/services/notification_service.php
 * Mutation Log: 2026-11-05 - Created notification dispatcher, unread counter, and mark read helpers.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('create_user_notification')) {
    /**
     * Dispatches a new notification to a specific user.
     *
     * @param int    $user_id  Recipient user ID.
     * @param string $type     Notification category ('outbid', 'auction_won', 'order_status', 'monk_new_item', 'system').
     * @param string $title    Brief heading.
     * @param string $message  Full descriptive text.
     * @param string|null $link Optional redirection URL.
     * @return bool True on success.
     */
    function create_user_notification(int $user_id, string $type, string $title, string $message, ?string $link = null): bool
    {
        log_event('create_user_notification', 'START', [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
        ]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, link_url, is_read)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $res = $stmt->execute([$user_id, $type, $title, $message, $link]);
        log_event('create_user_notification', 'FINISH', ['success' => $res]);
        return $res;
    }
}

if (!function_exists('get_user_notifications')) {
    /**
     * Retrieves recent notifications for the logged-in user.
     *
     * @param int $user_id Recipient user ID.
     * @param int $limit   Maximum number of alerts to fetch.
     * @return array<int, array<string, mixed>> List of alerts.
     */
    function get_user_notifications(int $user_id, int $limit = 10): array
    {
        log_event('get_user_notifications', 'START', ['user_id' => $user_id]);
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        log_event('get_user_notifications', 'FINISH', ['count' => count($rows)]);
        return $rows;
    }
}

if (!function_exists('get_unread_notification_count')) {
    /**
     * Counts unread notifications for navbar bell badge.
     *
     * @param int $user_id Target user ID.
     * @return int Count of unread alerts.
     */
    function get_unread_notification_count(int $user_id): int
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('mark_all_notifications_read')) {
    /**
     * Marks all unread alerts for the user as read.
     *
     * @param int $user_id Target user ID.
     * @return bool
     */
    function mark_all_notifications_read(int $user_id): bool
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
