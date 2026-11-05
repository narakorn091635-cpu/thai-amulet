/**
 * Main Client-Side Logic & Module 3 Hexagonal Amulet Status Radar Chart Engine.
 *
 * File: js/main.js
 * Mutation Log: 2026-11-04 - Implemented structured client telemetry and 6-axis HTML5 Canvas Radar Chart.
 */

'use strict';

/**
 * Logs structured frontend events with correlation IDs for full-stack observability.
 *
 * @param {string} function_name - Name of the executing JS function.
 * @param {string} phase - Execution lifecycle phase ('START', 'FINISH', 'MUTATION', 'ERROR').
 * @param {Object<string, *>} [context={}] - Additional key-value telemetry metadata.
 * @returns {void}
 */
function log_client_event(function_name, phase, context = {}) {
  const meta_el = document.querySelector('meta[name="x-correlation-id"]');
  const correlation_id = meta_el ? meta_el.getAttribute('content') : 'client_local';
  const entry = {
    timestamp: new Date().toISOString(),
    correlation_id,
    function: function_name,
    phase,
    context,
  };
  console.info('[AMULET_CLIENT]', JSON.stringify(entry));
}

/**
 * Renders a 6-axis hexagonal Radar Chart (Spider Web) on an HTML5 <canvas> element
 * for Module 3: Amulet Status Graphic.
 *
 * @param {HTMLCanvasElement} canvas_el - Target <canvas> element to render onto.
 * @param {Array<number>} scores - Array of 6 numeric scores (each scaled 1 to 10).
 * @param {Array<string>} [labels] - Optional array of 6 Thai attribute names.
 * @returns {void}
 * @throws {Error} When canvas_el is invalid or scores does not contain 6 numeric values.
 */
function draw_amulet_radar_chart(canvas_el, scores, labels) {
  log_client_event('draw_amulet_radar_chart', 'START', {
    canvas_id: canvas_el ? canvas_el.id : null,
    scores,
  });

  if (!canvas_el || typeof canvas_el.getContext !== 'function') {
    log_client_event('draw_amulet_radar_chart', 'ERROR', { reason: 'Invalid canvas element' });
    throw new Error('Valid HTMLCanvasElement is required to draw radar chart.');
  }

  const default_labels = [
    'พุทธคุณ',
    'เมตตามหานิยม',
    'มหาเสน่ห์',
    'แคล้วคลาด',
    'คงกระพันชาตรี',
    'โชคลาภ',
  ];
  const axis_labels = Array.isArray(labels) && labels.length === 6 ? labels : default_labels;
  const normalized_scores = (Array.isArray(scores) ? scores : [8, 8, 7, 8, 7, 8])
    .slice(0, 6)
    .map((val) => Math.max(1, Math.min(10, Number(val) || 1)));

  const root_styles = getComputedStyle(document.documentElement);
  const bg_dark = (root_styles.getPropertyValue('--bg-dark') || '#5e030f').trim();
  const gold_accent = (root_styles.getPropertyValue('--gold-accent') || '#E9D793').trim();

  const ctx = canvas_el.getContext('2d');
  const width = canvas_el.width;
  const height = canvas_el.height;
  const center_x = width / 2;
  const center_y = height / 2;
  const max_radius = Math.min(width, height) * 0.34;
  const total_axes = 6;
  const max_score = 10;

  // 1. Fill dark red background (#5e030f)
  ctx.clearRect(0, 0, width, height);
  ctx.fillStyle = bg_dark;
  ctx.fillRect(0, 0, width, height);

  // 2. Draw concentric hexagonal web rings (levels 2, 4, 6, 8, 10)
  const ring_levels = [2, 4, 6, 8, 10];
  ring_levels.forEach((level) => {
    const ring_radius = (level / max_score) * max_radius;
    ctx.beginPath();
    for (let i = 0; i < total_axes; i += 1) {
      const angle = (Math.PI * 2 * i) / total_axes - Math.PI / 2;
      const x = center_x + ring_radius * Math.cos(angle);
      const y = center_y + ring_radius * Math.sin(angle);
      if (i === 0) {
        ctx.moveTo(x, y);
      } else {
        ctx.lineTo(x, y);
      }
    }
    ctx.closePath();
    ctx.strokeStyle = 'rgba(233, 215, 147, 0.25)';
    ctx.lineWidth = level === 10 ? 1.5 : 1;
    ctx.stroke();
  });

  // 3. Draw 6 spokes and attribute labels
  ctx.font = 'bold 13px sans-serif';
  ctx.fillStyle = gold_accent;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';

  for (let i = 0; i < total_axes; i += 1) {
    const angle = (Math.PI * 2 * i) / total_axes - Math.PI / 2;
    const outer_x = center_x + max_radius * Math.cos(angle);
    const outer_y = center_y + max_radius * Math.sin(angle);

    ctx.beginPath();
    ctx.moveTo(center_x, center_y);
    ctx.lineTo(outer_x, outer_y);
    ctx.strokeStyle = 'rgba(233, 215, 147, 0.35)';
    ctx.lineWidth = 1;
    ctx.stroke();

    const label_radius = max_radius + 32;
    const label_x = center_x + label_radius * Math.cos(angle);
    const label_y = center_y + label_radius * Math.sin(angle);
    ctx.fillText(`${axis_labels[i]} (${normalized_scores[i]})`, label_x, label_y);
  }

  // 4. Draw the amulet's polygon in Gold (#E9D793)
  ctx.beginPath();
  normalized_scores.forEach((score, index) => {
    const point_radius = (score / max_score) * max_radius;
    const angle = (Math.PI * 2 * index) / total_axes - Math.PI / 2;
    const x = center_x + point_radius * Math.cos(angle);
    const y = center_y + point_radius * Math.sin(angle);
    if (index === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  });
  ctx.closePath();
  ctx.fillStyle = 'rgba(233, 215, 147, 0.28)';
  ctx.fill();
  ctx.strokeStyle = gold_accent;
  ctx.lineWidth = 2.5;
  ctx.stroke();

  // 5. Draw vertex nodes
  normalized_scores.forEach((score, index) => {
    const point_radius = (score / max_score) * max_radius;
    const angle = (Math.PI * 2 * index) / total_axes - Math.PI / 2;
    const x = center_x + point_radius * Math.cos(angle);
    const y = center_y + point_radius * Math.sin(angle);
    ctx.beginPath();
    ctx.arc(x, y, 4, 0, Math.PI * 2);
    ctx.fillStyle = gold_accent;
    ctx.fill();
  });

  log_client_event('draw_amulet_radar_chart', 'FINISH', { rendered_axes: total_axes });
}

/**
 * Automatically initializes any <canvas data-radar-scores="..."> on page load.
 *
 * @returns {void}
 */
function init_all_radar_charts() {
  log_client_event('init_all_radar_charts', 'START');
  const canvases = document.querySelectorAll('canvas[data-radar-scores]');
  canvases.forEach((canvas_el) => {
    try {
      const raw_scores = JSON.parse(canvas_el.getAttribute('data-radar-scores') || '[]');
      const raw_labels = canvas_el.getAttribute('data-radar-labels')
        ? JSON.parse(canvas_el.getAttribute('data-radar-labels'))
        : undefined;
      draw_amulet_radar_chart(canvas_el, raw_scores, raw_labels);
    } catch (err) {
      log_client_event('init_all_radar_charts', 'ERROR', { error: err.message });
    }
  });
  log_client_event('init_all_radar_charts', 'FINISH', { count: canvases.length });
}

/**
 * Initialize Smooth Page Transition & Top Gold Progress Bar across all navigation clicks.
 *
 * @returns {void}
 */
function init_page_transitions() {
  log_client_event('init_page_transitions', 'START');

  // Inject or retrieve top progress bar
  let bar = document.getElementById('page_progress_bar');
  if (!bar) {
    bar = document.createElement('div');
    bar.id = 'page_progress_bar';
    document.body.prepend(bar);
  }

  // Animate progress bar completion on page load
  bar.style.opacity = '1';
  bar.style.width = '100%';
  setTimeout(() => {
    bar.style.opacity = '0';
    setTimeout(() => {
      bar.style.width = '0%';
    }, 300);
  }, 250);

  // Mark current active link in header
  const current_path = window.location.pathname.split('/').pop() || 'index.php';
  const nav_anchors = document.querySelectorAll('.nav_links a');
  nav_anchors.forEach((a) => {
    const href = a.getAttribute('href');
    if (href && (href === current_path || href.endsWith('/' + current_path))) {
      a.classList.add('active');
    }
  });

  // Intercept internal link transitions for smooth exit/enter animation
  document.addEventListener('click', (e) => {
    const target_a = e.target.closest('a');
    if (!target_a) return;

    const href = target_a.getAttribute('href');
    const target = target_a.getAttribute('target');

    // Skip javascript links, external links, anchors, or new tab links
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || target === '_blank') {
      return;
    }

    // Check if it's an internal site navigation
    const is_internal = !href.startsWith('http://') && !href.startsWith('https://') || href.includes(window.location.host);
    if (!is_internal) return;

    // Prevent immediate jump
    e.preventDefault();

    // Trigger golden progress bar animation
    bar.style.opacity = '1';
    bar.style.width = '75%';

    // Trigger page content exit animation
    const main_content = document.querySelector('.main_content') || document.body;
    main_content.classList.add('page_fade_exit');

    // Navigate to target URL after animation
    setTimeout(() => {
      bar.style.width = '100%';
      window.location.href = href;
    }, 180);
  });

  log_client_event('init_page_transitions', 'FINISH');
}

document.addEventListener('DOMContentLoaded', () => {
  init_page_transitions();
  init_all_radar_charts();
});

