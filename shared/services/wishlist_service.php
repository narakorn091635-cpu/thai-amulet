<?php
declare(strict_types=1);

/**
 * Wishlist & Monk Follower Service.
 *
 * File: shared/services/wishlist_service.php
 * Mutation Log: 2026-11-05 - Created toggle wishlist, follow/unfollow monk, and followed feeds.
 */

require_once __DIR__ . '/../utils/logger.php';
require_once __DIR__ . '/../../includes/db_connect.php';

if (!function_exists('toggle_amulet_wishlist')) {
    /**
     * Toggles an amulet in the user's wishlist and increments/decrements amulet wishlist_count.
     *
     * @param int $user_id
     * @param int $amulet_id
     * @return bool True if added, False if removed.
     */
    function toggle_amulet_wishlist(int $user_id, int $amulet_id): bool
    {
        $pdo = get_db_connection();
        $check = $pdo->prepare("SELECT id FROM amulet_wishlists WHERE user_id = ? AND amulet_id = ?");
        $check->execute([$user_id, $amulet_id]);
        $existing = $check->fetch();

        if ($existing) {
            $del = $pdo->prepare("DELETE FROM amulet_wishlists WHERE user_id = ? AND amulet_id = ?");
            $del->execute([$user_id, $amulet_id]);
            $pdo->prepare("UPDATE amulets SET wishlist_count = GREATEST(0, wishlist_count - 1) WHERE id = ?")->execute([$amulet_id]);
            return false;
        } else {
            $ins = $pdo->prepare("INSERT INTO amulet_wishlists (user_id, amulet_id) VALUES (?, ?)");
            $ins->execute([$user_id, $amulet_id]);
            $pdo->prepare("UPDATE amulets SET wishlist_count = wishlist_count + 1 WHERE id = ?")->execute([$amulet_id]);
            return true;
        }
    }
}

if (!function_exists('is_amulet_in_wishlist')) {
    function is_amulet_in_wishlist(int $user_id, int $amulet_id): bool
    {
        if ($user_id <= 0) return false;
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT id FROM amulet_wishlists WHERE user_id = ? AND amulet_id = ?");
        $stmt->execute([$user_id, $amulet_id]);
        return $stmt->fetch() !== false;
    }
}

if (!function_exists('get_user_wishlist_amulets')) {
    function get_user_wishlist_amulets(int $user_id): array
    {
        $pdo = get_db_connection();
        $sql = "
            SELECT a.*, c.name AS category_name, w.created_at AS saved_at
            FROM amulet_wishlists w
            JOIN amulets a ON w.amulet_id = a.id
            JOIN categories c ON a.category_id = c.id
            WHERE w.user_id = ?
            ORDER BY w.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('toggle_follow_monk')) {
    function toggle_follow_monk(int $user_id, string $monk_name): bool
    {
        $clean = trim($monk_name);
        $pdo = get_db_connection();
        $check = $pdo->prepare("SELECT id FROM user_followed_monks WHERE user_id = ? AND monk_name = ?");
        $check->execute([$user_id, $clean]);
        if ($check->fetch()) {
            $del = $pdo->prepare("DELETE FROM user_followed_monks WHERE user_id = ? AND monk_name = ?");
            $del->execute([$user_id, $clean]);
            return false;
        } else {
            $ins = $pdo->prepare("INSERT INTO user_followed_monks (user_id, monk_name) VALUES (?, ?)");
            $ins->execute([$user_id, $clean]);
            return true;
        }
    }
}

if (!function_exists('is_following_monk')) {
    function is_following_monk(int $user_id, string $monk_name): bool
    {
        if ($user_id <= 0) return false;
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT id FROM user_followed_monks WHERE user_id = ? AND monk_name = ?");
        $stmt->execute([$user_id, trim($monk_name)]);
        return $stmt->fetch() !== false;
    }
}

if (!function_exists('get_user_followed_monks')) {
    function get_user_followed_monks(int $user_id): array
    {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT monk_name, created_at FROM user_followed_monks WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
