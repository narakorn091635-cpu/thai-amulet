/**
 * Real-time Auction Countdown Timer, AJAX Bidding & Anti-Snipe Handler (Module 2).
 *
 * File: js/auction.js
 * Mutation Log: 2026-11-04 - Implemented countdown timer, Fetch API bidding, and 1-min Anti-Snipe (+3 min extension).
 */

'use strict';

/**
 * Formats remaining seconds into HH:MM:SS display string.
 *
 * @param {number} total_seconds - Non-negative integer seconds remaining.
 * @returns {string} Formatted countdown string (e.g. "00:02:45").
 */
function format_countdown_seconds(total_seconds) {
  const clamped = Math.max(0, Math.floor(total_seconds));
  const hours = String(Math.floor(clamped / 3600)).padStart(2, '0');
  const minutes = String(Math.floor((clamped % 3600) / 60)).padStart(2, '0');
  const seconds = String(clamped % 60).padStart(2, '0');
  return `${hours}:${minutes}:${seconds}`;
}

/**
 * Initializes real-time countdown timers for all elements carrying `[data-auction-end]`.
 * Highlights timers in red when inside the 60-second Anti-Snipe window.
 *
 * @returns {number} Interval timer ID.
 */
function init_auction_countdown_timers() {
  if (typeof log_client_event === 'function') {
    log_client_event('init_auction_countdown_timers', 'START');
  }

  const update_all_timers = () => {
    const timer_elements = document.querySelectorAll('[data-auction-end]');
    const now_ms = Date.now();

    timer_elements.forEach((timer_el) => {
      const end_iso = timer_el.getAttribute('data-auction-end');
      const end_ms = Date.parse(end_iso);
      if (Number.isNaN(end_ms)) {
        return;
      }

      const remaining_seconds = Math.floor((end_ms - now_ms) / 1000);
      if (remaining_seconds <= 0) {
        timer_el.textContent = 'ปิดประมูลแล้ว (Ended)';
        timer_el.classList.remove('snipe_warning');
        return;
      }

      if (remaining_seconds <= 60) {
        timer_el.classList.add('snipe_warning');
      } else {
        timer_el.classList.remove('snipe_warning');
      }

      timer_el.textContent = format_countdown_seconds(remaining_seconds);
    });
  };

  update_all_timers();
  const interval_id = window.setInterval(update_all_timers, 1000);

  if (typeof log_client_event === 'function') {
    log_client_event('init_auction_countdown_timers', 'FINISH', { interval_id });
  }
  return interval_id;
}

/**
 * Submits a bid asynchronously via Fetch API, enforcing minimum bid rules
 * and applying Anti-Snipe (+3 minutes when bid placed within final 60 seconds).
 *
 * @param {number} auction_id - Target auction identifier.
 * @param {number} bid_amount - Proposed bid amount in THB.
 * @param {number} min_required_bid - Minimum valid bid amount (current_price + min_increment).
 * @param {string} endpoint_url - Relative path to the PHP bidding endpoint.
 * @returns {Promise<Object<string, *>>} Parsed JSON response from server.
 * @throws {Error} When bid_amount is below min_required_bid or network request fails.
 */
async function submit_auction_bid(auction_id, bid_amount, min_required_bid, endpoint_url = './api_place_bid.php') {
  if (typeof log_client_event === 'function') {
    log_client_event('submit_auction_bid', 'START', {
      auction_id,
      bid_amount,
      min_required_bid,
    });
  }

  const numeric_bid = Number(bid_amount);
  const numeric_min = Number(min_required_bid);

  if (Number.isNaN(numeric_bid) || numeric_bid < numeric_min) {
    const error_msg = `ราคาประมูลต้องไม่ต่ำกว่า ${numeric_min.toLocaleString('th-TH')} บาท`;
    if (typeof log_client_event === 'function') {
      log_client_event('submit_auction_bid', 'ERROR', { reason: error_msg });
    }
    throw new Error(error_msg);
  }

  const response = await fetch(endpoint_url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      auction_id: Number(auction_id),
      bid_amount: numeric_bid,
    }),
  });

  const payload = await response.json();

  if (!response.ok || !payload.success) {
    const server_err = payload.message || 'ไม่สามารถเสนอราคาได้ กรุณาลองใหม่อีกครั้ง';
    if (typeof log_client_event === 'function') {
      log_client_event('submit_auction_bid', 'ERROR', { reason: server_err });
    }
    throw new Error(server_err);
  }

  if (typeof log_client_event === 'function') {
    log_client_event('submit_auction_bid', 'FINISH', {
      new_price: payload.current_price,
      new_end_time: payload.end_time,
      anti_snipe_extended: Boolean(payload.anti_snipe_extended),
    });
  }

  return payload;
}

document.addEventListener('DOMContentLoaded', () => {
  init_auction_countdown_timers();
});

