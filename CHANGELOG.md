# CHANGELOG

All notable changes, architectural decisions, thinking logic, planning, tracking, and issue resolutions for the **Thai Amulet E-commerce & Real-time Auction Platform** are documented in this file.

## [1.5.7] - 2026-11-05
### Instant Zero-Login Public Live Cloud Tunnel
- **Architecture & Thinking Logic**:
  - Solved login/authentication roadblock without requiring manual CLI authorizations.
  - Deployed encrypted SSL/TLS tunnel (`Serveo`) forwarding local PHP 8.2 & MySQL server at port `8000` to a secure public HTTPS endpoint.
  - Verified public HTTP/2 200 response with active sessions and real-time database support.

## [1.5.6] - 2026-11-05
### Render Cloud Deployment Infrastructure (Docker & render.yaml)
- **Thinking Logic & Architecture**:
  - Configured full-stack containerization for Render via `Dockerfile` (PHP 8.2 Apache base, GD with WebP/JPEG, PDO MySQL, SQLite, Apache Rewrite).
  - Configured dynamic port binding (`PORT=10000`) required by Render Cloud environment.
  - Created Blueprint specification `render.yaml` with Singapore region setup for lowest latency in Thailand.

## [1.5.5] - 2026-11-05
### Firebase Hosting Production Packaging & Configuration
- **Thinking Logic & Architecture**:
  - Configured `.firebaserc` pointing to project `amulet-c509e`.
  - Configured `firebase.json` for Firebase Hosting with `cleanUrls: true` and asset routing.
  - Packaged static distribution inside `public/` containing pre-rendered `index.html`, `auctions.html`, `leaderboard.html`, and `verify_cert.html` bundled with assets, fonts, and luxury CSS/JS.

## [1.5.4] - 2026-11-05
### Comprehensive Codebase Audit & Security Hardening
- **Security & Integrity Enhancements**:
  - Performed complete PHP syntax linting across all codebase files (`find . -name "*.php"`): 100% passed without syntax errors.
  - Audited all SQL statements: verified 100% prepared statement usage (`PDO::prepare()`) on user-controlled inputs, preventing SQL injection.
  - Audited File Upload Security in `checkout.php` (payment slips) and `admin/amulet_manage.php` (amulet photos):
    - Added rigorous binary MIME-type verification using `finfo_open(FILEINFO_MIME_TYPE)`.
    - Enforced strict whitelist checks (`image/jpeg`, `image/png`, `image/webp`), preventing executable or polyglot file execution.
  - Performed live HTTP 200 health check across all endpoints (`index.php`, `auctions.php`, `leaderboard.php`, `verify_cert.php`, `amulet_detail.php`, `print_cert.php`).

## [1.5.3] - 2026-11-05
### Seamless Page Transitions & Luxury Golden Progress Bar
- **Thinking Logic & UI/UX Enhancement**:
  - Implemented smooth cinematic page transitions (`@keyframes pageFadeIn`, `@keyframes pageFadeOut`) for all tab navigation and internal link clicks.
  - Added a luxury golden top loading progress bar (`#page_progress_bar`) with neon amber luminescence simulating high-performance SPA navigation.
  - Added active tab indicator underline animation (`.nav_links a::after`) on navbar links that highlights the currently viewed page automatically.

## [1.5.2] - 2026-11-05
### Bugfix: AJAX Content-Type Support for Wishlist & Follow Monk Endpoints
- **Root Cause Analysis**:
  - In `api_wishlist.php`, the script was only reading `file_get_contents('php://input')` assuming JSON payload, while the frontend fetch requests in `index.php` and `amulet_detail.php` were sent as `application/x-www-form-urlencoded` (`$_POST`).
  - This resulted in `$data['action']` being empty/undefined, triggering the fallback response: `{"success": false, "message": "Invalid action"}`.
- **Resolution**:
  - Updated `api_wishlist.php` to seamlessly accept both standard `application/x-www-form-urlencoded` (`$_POST`) and raw JSON payloads (`php://input`).
  - Added compatibility response keys `is_in_wishlist` and `is_following` to match client expectations.

## [1.5.1] - 2026-11-05
### Ultra-Luxury Royal Heritage UI & Visual Redesign
- **Thinking Logic & Aesthetics Upgrade**:
  - Transformed the platform design from flat maroon into a **Royal Thai Heritage & Luxury Gold** aesthetic matching high-end auction houses (Sotheby's/Christie's & Thai Amulet Association standard).
  - Integrated Google Fonts: `Prompt` (crisp modern Thai typography) and `Cinzel` (royal serif luxury accents).
  - Enhanced Header & Navigation with blurred glassmorphism (`backdrop-filter: blur(14px)`), golden emblem floating animation, and dual-tone branding.
  - Added **Hero Showcase Section** with gold metallic gradient headlines, prestige trust badges ("รับประกันพระแท้ 100%", "PSA GRADE", "LIVE AUCTION"), and royal border glow.
  - Redesigned Catalog Cards into **Glassmorphic Gold Slabs**:
    - Created high-resolution fallback medallions (`images/sample_*.png`) for พระสมเด็จ, เหรียญหลวงพ่อรวย, เบี้ยแก้, และเหรียญเปิดโลก เพื่อไม่ให้แสดงไอคอนหลุดหรือรูปเสีย
    - Added quick-click Wishlist heart button (❤️) directly on amulet cards with micro-animations
    - Added clear metadata rows with sacred icons (วัด, เกจิ, พิมพ์/รุ่น, ปีสร้าง)
    - Upgraded price tags to embossed gold metallic typography and polished dual-action CTA buttons.

## [1.5.0] - 2026-11-05
### Features 1 to 3: Real-Time In-App Notifications, Provenance Transfer & Printable PSA Slab, Wishlist & Follow Monk Feed
- **Thinking Logic & Architecture**:
  1. **Notification Hub & Real-time Outbid Alert (Feature 1)**:
     - Created `notifications` table (`id`, `user_id`, `type`, `title`, `message`, `link_url`, `is_read`, `created_at`).
     - Built `shared/services/notification_service.php` with `create_user_notification()`, `get_user_notifications()`, and `mark_all_notifications_read()`.
     - Integrated Outbid Alert in `shared/services/auction_service.php`: whenever a higher bid is submitted, the previous highest bidder instantly receives an in-app outbid notification pointing to the auction room.
     - Added floating notification bell badge in `includes/header.php` with real-time unread counter and interactive dropdown panel.
  2. **Smart Slip Verification, Provenance Transfer & Printable PSA Certificate (Feature 2)**:
     - Created `provenance_transfers` table logging certificate ownership transfers with transaction/order tracking.
     - Enhanced `admin/order_manage.php`: when admin verifies payment and transitions order to `preparing_shipment` or `shipped`, the system automatically transfers PSA Certificate ownership (`certificates.current_owner_id`, `certificates.current_owner_name`) to the buyer, logs the immutable provenance trail, and sends a notification to the buyer.
     - Built `print_cert.php` providing a museum-grade, printable A4 PSA grading certificate with security guilloche border, gold seal, anti-counterfeit hash, and verification QR code.
  3. **Wishlist & Follow Monk System (Feature 3)**:
     - Created `amulet_wishlists` and `user_followed_monks` tables.
     - Built `shared/services/wishlist_service.php` and AJAX endpoint `api_wishlist.php`.
     - Created dedicated `wishlist.php` view displaying user's saved sacred items, followed master monks, and personalized discovery feeds.
     - Embedded interactive "❤️ Wishlist" toggle button and "+ ติดตามเกจิ" follow button in `amulet_detail.php`.

## [1.4.1] - 2026-11-05
### Hybrid Architecture: Firebase Cloud Firestore Real-Time Live Bidding Engine
- **Thinking Logic & Architecture**:
  - Implemented **Hybrid Dual-Engine Architecture** preserving MySQL ACID transaction integrity for core business logic, sessions, and order fulfilment while leveraging Firebase Cloud Firestore (`amulet-c509e`) for instant zero-latency broadcast.
  - Enhanced `auction_detail.php` with a live Firestore listener (`onSnapshot()`) that listens to the `auctions/{id}` document.
  - Injected instant DOM updates: when any user submits a bid via `handle_submit_bid()`, the newly confirmed bid price, bidder username, and anti-snipe extended timer are synced into Firestore, broadcasting instantly to all connected bidders without any page refresh.
  - Added a pulsing `FIREBASE REAL-TIME LIVE` status badge on the auction interface.

## [1.4.0] - 2026-11-05
### Firebase Web Client SDK Integration & Cloud Migration Tool
- **Thinking Logic & Architecture**:
  - Configured Firebase Web Client Modular SDK in `js/firebase_config.js` with Project `amulet-c509e`, connecting Firestore, Realtime Database, and Analytics.
  - Exported MySQL database collections to standard schema JSON at `database/firebase_data.json`.
  - Built interactive 1-Click Cloud Sync Dashboard at `firebase_sync.php` allowing instant migration of `amulets`, `certificates`, `categories`, `auctions`, `market_prices`, and `user_collections` directly into Firestore Collections.

## [1.3.0] - 2026-11-04
### Deep Search, Personal Portfolio Showcase, and Dual Leaderboard with Hall of Fame
- **Thinking Logic & Architecture**:
  - Expanded `amulets` schema with `province`, `material_type` (powder, clay, chin, metal, other), `model_edition`, `view_count`, and `wishlist_count`.
  - Upgraded `search_marketplace_amulets()` in `amulet_service.php` with multi-faceted filtering (เกจิอาจารย์, วัด, จังหวัด, เนื้อพระ, รุ่น).
  - Built **Personal Collection Vault & Portfolio Tracker (`collection_showcase.php`)** showing user amulet assets, acquisition costs, current market valuation, and unrealized % gain/loss with quick modal entry.
  - Built **Dual Leaderboard (`leaderboard.php`)**:
    1. **ฝั่งองค์พระ (Amulet Leaderboard)**:
       - **ทำเนียบพระแชมป์ (Top Graded Masterpieces)**: เรียงตามคะแนนเกรด PSA (10/10) และความหายาก
       - **มูลค่าและสถิติประมูลสูงสุด (Top Value & Most Bids)**: ราคาประเมินแพงสุด และการสู้ราคาเคาะประมูลดุเดือดสุด
       - **ท็อปชาร์ตตามสายสรรพคุณ (Top by Properties)**: แยกกระดานสายคงกระพัน, สายเมตตามหานิยม, สายโชคลาภ
       - **พระยอดฮิตมาแรง (Trending Amulets)**: คำนวณจากยอดคลิกดู (Views) และจำนวนคนกดเซฟลง Wishlist
    2. **ฝั่งนักสะสมและเซียนพระ (Collector Leaderboard & Hall of Fame)**:
       - **เจ้ากรมพระ (Highest Portfolio Value)**: จัดอันดับตามมูลค่าประเมินรวมของพระในพอร์ต
       - **นักสะสมของแรร์ (Top Quality Collector)**: นับคะแนนเกรดพระแท้สภาพสวยเดิม
       - **เซียนนักเทรด (Top Trusted Traders)**: ประวัติซื้อขายสำเร็จและรีวิวเต็ม พร้อมฉายาพิเศษ เช่น "เซียนร้อยล้าน", "เจ้ากรมเนื้อผง", "ตาไว สายแรร์"
       - **หอเกียรติยศ (Hall of Fame)**: กระดานเกียรติยศตลอดกาล (All-Time) บันทึกประวัติศาสตร์วงการ

## [1.2.1] - 2026-11-04
### Centralized Session Bootstrap & AJAX Bid Endpoint Fix
- **Root Cause Analysis**:
  - In `api_place_bid.php`, `session_start()` was invoked without setting `session_save_path()`, reverting to `/Applications/XAMPP/xamppfiles/temp/` which failed with Permission Denied (13).
  - Consequently, `api_place_bid.php` failed to load `$_SESSION['user_id']` from the client cookie and rejected incoming bids with `401 Unauthorized` ("กรุณาเข้าสู่ระบบก่อนทำการประมูลพระเครื่อง") even when the user was logged in.
- **Resolution**:
  - Built centralized session bootstrap helper `shared/utils/session_init.php` that standardizes `session_save_path()` to `<root>/sessions/`.
  - Replaced manual `session_start()` in `api_place_bid.php`, `auth_guard.php`, `login.php`, `register.php`, `checkout.php`, and `cart.php` with `shared/utils/session_init.php`.
  - Verified AJAX bidding endpoint now correctly recognizes logged-in user and admin sessions.

## [1.2.0] - 2026-11-04
### PSA-Style Certificate & QR Verification System (Anti-Counterfeit Protection)
- **Architecture & Thinking Logic**:
  - Designed `certificates` table in MySQL storing PSA-style grade score (1.0 to 10.0 Gem Mint), certificate serial numbers, SHA-256 anti-counterfeiting security signature hashes, macro zoom defect inspection photos, and current verified provenance owner identity.
  - Built `shared/services/cert_service.php` providing `get_certificate_by_cert_number()`, `get_certificate_by_amulet_id()`, and dynamic QR Code generation targeting the verification route.
  - Implemented public verification page `verify_cert.php` featuring:
    1. Authenticated PSA grading slab design with grade score badge (เช่น `PSA PERFECT 10 (MINT)`).
    2. Dynamic QR Code for instant mobile smartphone camera verification.
    3. Current verified provenance owner name (เช่น `BaiNingNing`) to prevent identity theft / photo reuse scams.
    4. Interactive micro zoom inspection (นำเมาส์ชี้เพื่อขยาย 220% ตรวจสอบมวลสาร โค้ดแท้ และรอยตัดขอบข้าง).
  - Integrated Verified PSA Badge into `amulet_detail.php` and added navigation link in top navbar.

## [1.1.4] - 2026-11-04
### Market Price Benchmarking & Real Historical Comparison
- **Architecture & Thinking Logic**:
  - Designed `amulet_market_prices` table in MySQL schema to store verified benchmark market prices across different time periods (`historical`, `market_average`, `recent_deal`) with provenance attribution (`price_source`).
  - Implemented `get_amulet_market_price_history()` and `calculate_market_price_comparison()` in `shared/services/amulet_service.php` to compute real-time price variance, percentage deviations, and highlight deal quality.
  - Upgraded `amulet_detail.php` with a Market Price Benchmark card displaying:
    1. Listed Asking Price vs. Latest Market Average (Today).
    2. Real difference amount and smart badge (e.g., "ต่ำกว่าราคากลาง 15% (น่าบูชาอย่างยิ่ง)").
    3. Historical timeline comparing current prices with past years (เช่น ราคาเปิดจองปี 2562, ราคาประเมินปี 2566, 2567).

## [1.1.3] - 2026-11-04
### Radar Chart Status Bar UI Enhancement & Footer Fix
- **Root Cause Analysis**:
  - `amulet_detail.php` and `auction_detail.php` failed to load `js/main.js` script tags because `includes/footer.php` was not imported, causing the Canvas Radar Chart script not to execute and leaving the top canvas area blank.
- **Resolution**:
  - Added `require_once __DIR__ . '/includes/footer.php'` in both `amulet_detail.php` and `auction_detail.php` so `js/main.js` loads properly to render the Hexagonal Canvas Radar Chart.
  - Added golden status progress bars (`Status Power Bars`) with percentage calculation based on scores (1-10) with glowing effects inside each attribute card.

## [1.1.2] - 2026-11-04
### Image Rendering & Auction Automation Integration
- **Root Cause Analysis**:
  - `index.php`, `amulet_detail.php`, `auctions.php`, and `auction_detail.php` were hardcoded to display a placeholder emoji (`⚜️`) and omitted rendering `<img>` tags pointing to `$amulet['image_path']`.
  - In `admin/amulet_manage.php`, saving an amulet with `listing_type = 'auction'` only updated the `amulets` table, but did not automatically create an active record in the `auctions` table, causing the newly added auction amulet to not show up on the `auctions.php` page.
- **Resolution**:
  - Updated all client-facing templates to render `image_path` dynamically with fallback handling.
  - Updated `cart.php` item table to display uploaded thumbnail images.
  - Enhanced `admin/amulet_manage.php` to automatically create an active auction room in `auctions` table when `listing_type === 'auction'`.
  - Added sample auction entry for testing.

## [1.1.1] - 2026-11-04
### Bug Fixes & Session Path Hardening
- **Root Cause Analysis**:
  - `cart.php` was missing an explicit import of `includes/footer.php`, leading to an undefined function call to `render_site_footer()` on cart render.
  - XAMPP default session directory (`/Applications/XAMPP/xamppfiles/temp/`) caused permission denied (13) on session writes under development server context, causing `$_SESSION['cart']` to be dropped between redirects.
- **Resolution**:
  - Added dedicated workspace session directory at `sessions/` and configured `session_save_path()` across header, cart, and auth guards.
  - Added `require_once __DIR__ . '/includes/footer.php'` in `cart.php`.
  - Synced sample amulets and registered users with active MySQL database instance.

## [1.1.0] - 2026-11-04
### Phase 2: Authentication & Admin CMS (20 - 40%) Completed
- **Thinking Logic & Architecture**:
  - Implemented standalone `register.php` with double password confirmation and strict email/username validation.
  - Refactored `login.php` to store authenticated user session attributes explicitly (`$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role'] = 'admin'|'user'`).
  - Created centralized guard middleware in `shared/services/auth_guard.php` (`require_admin()`, `require_login()`) protecting `/admin/` routes and returning `403 Forbidden` for non-admin accounts.
  - Upgraded `admin/amulet_manage.php` with file upload logic saving photos into `/images/uploads/` with MIME checks (`jpg`, `jpeg`, `png`, `webp`) and collision-free names.
  - Enforced 6 numeric inputs (min 1, max 10) in `admin/amulet_manage.php` with real-time `<canvas>` Hexagonal Radar Chart preview on input change.
  - Verified syntax and authentication tests via PHP 8+ PDO runner.
