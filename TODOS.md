# Features 1-3: Notifications, Slip Verification & Provenance Transfer, Wishlist & Follow Monk
## Done ✓
- [x] Create `notifications`, `user_followed_monks`, `provenance_transfers`, and `amulet_wishlists` tables in MySQL ~1d #db @developer 2026-11-05
- [x] Feature 1: Real-time Outbid & Slip Notifications (Floating bell badge + live polling/listener + notification service) ~1d #notification @developer 2026-11-05
- [x] Feature 2: Smart Slip Verification & Provenance Transfer (Admin approves slip -> auto transfer certificate owner to buyer + printable PSA certificate view `print_cert.php`) ~1d #orders @developer 2026-11-05
- [x] Feature 3: Wishlist & Follow Monk system (Favorite button, followed monks feed, wishlist management page `wishlist.php`, and `api_wishlist.php`) ~1d #wishlist @developer 2026-11-05

# Hybrid Firebase Real-Time Auction & Sync Architecture
## Done ✓
- [x] Connect `api_place_bid.php` to push bid event and new price to Firestore `auctions` collection ~1d #firebase @developer 2026-11-05
- [x] Implement live listener `onSnapshot()` in `auction_detail.php` to update current bid, timer, and bidder history instantly across all screens ~1d #firebase @developer 2026-11-05
- [x] Add live Firebase Real-time indicator badge in auction room ~1d #ui @developer 2026-11-05

# Phase 2: Authentication & Admin CMS (20 - 40%)
## Done ✓
- [x] Create standalone register.php with validation and confirm password ~1d #auth @developer 2026-11-04
- [x] Implement auth guard middleware in shared/services/auth_guard.php (require_admin, require_login) ~1d #auth @developer 2026-11-04
- [x] Add amulet image upload handler and form inputs in admin/amulet_manage.php saving to /images/uploads/ ~1d #admin @developer 2026-11-04
- [x] Integrate real-time Canvas Radar Chart preview in admin/amulet_manage.php form ~1d #canvas @developer 2026-11-04
- [x] Enforce $_SESSION['role'] === 'admin' verification across all /admin/ pages ~1d #admin @developer 2026-11-04

# Advanced Search & Deep Filter
## Done ✓
- [x] Add columns `province`, `material_type` (ผง, ดิน, ชิน, โลหะ), `model_edition` to `amulets` table in MySQL ~1d #db @developer 2026-11-04
- [x] Update `amulet_service.php` with deep multi-faceted filters and build advanced search UI in `index.php` ~1d #search @developer 2026-11-04

# Personal Collection Showcase & Portfolio Tracker
## Done ✓
- [x] Create `user_collections` and `collection_history` tables for tracking user amulet portfolios ~1d #portfolio @developer 2026-11-04
- [x] Build premium Portfolio Showcase Dashboard `collection_showcase.php` with total portfolio valuation graph ~1d #portfolio @developer 2026-11-04

# Dual Leaderboard & Hall of Fame System
## Done ✓
- [x] Create `amulet_interactions` (clicks, searches, wishlist) and `user_trader_stats` for leaderboard metrics ~1d #leaderboard @developer 2026-11-04
- [x] Implement Amulet Leaderboard: Top Graded Masterpieces, Top Value & Bids, Top by Sacred Properties (คงกระพัน, เมตตา, โชคลาภ), Trending ~1d #leaderboard @developer 2026-11-04
- [x] Implement Collector Leaderboard & Hall of Fame: Highest Portfolio Value, Top Quality Collector, Top Trusted Traders with special titles/badges (เช่น "เจ้ากรมเนื้อผง", "เซียนร้อยล้าน", "ตาไว") ~1d #leaderboard @developer 2026-11-04
- [x] Build unified Leaderboard UI `leaderboard.php` with navigation tab in header ~1d #ui @developer 2026-11-04

# PSA Certificate & QR Verification System
## Done ✓
- [x] Create `certificates` table in MySQL with grading score (PSA-style 1-10), cert number, security hash, defects zoom photos, current owner ~1d #cert @developer 2026-11-04
- [x] Create QR Code generator utility / verification route `verify_cert.php?cert_no=XXX` ~1d #cert @developer 2026-11-04
- [x] Implement interactive zoom lens / modal inspection for defects & authentication points ~1d #ui @developer 2026-11-04
- [x] Display verified PSA badge & QR code trigger in `amulet_detail.php` and admin management ~1d #cert @developer 2026-11-04
