<?php

add_action('wp_dashboard_setup', 'pvt_register_dashboard_widget');
add_action('admin_enqueue_scripts', 'pvt_enqueue_chart_js');

function pvt_enqueue_chart_js($hook)
{
  if ($hook !== 'index.php') return;
  if (!get_option('haysky_post_views', false)) return;
  wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', [], null, true);
}

function pvt_register_dashboard_widget()
{
  if (!get_option('haysky_post_views', false)) return;
  wp_add_dashboard_widget(
    'pvt_post_views_widget',
    'Haysky Post Views',
    'pvt_render_dashboard_widget'
  );
}

function pvt_render_dashboard_widget()
{
  global $wpdb;
  $table_daily = $wpdb->prefix . 'haysky_post_views_daily';

  $month      = isset($_GET['pvt_month']) ? sanitize_text_field($_GET['pvt_month']) : current_time('Y-m');
  $month_ts   = strtotime($month . '-01');
  $year       = (int) date('Y', $month_ts);
  $month_num  = date('m', $month_ts);
  $days       = (int) date('t', $month_ts);
  $start_date = "$year-$month_num-01";
  $end_date   = "$year-$month_num-" . sprintf('%02d', $days);

  // Daily totals
  $rows = $wpdb->get_results($wpdb->prepare(
    "SELECT view_date, SUM(views) AS total_views, COUNT(DISTINCT post_id) AS post_count
     FROM $table_daily
     WHERE view_date BETWEEN %s AND %s
     GROUP BY view_date
     ORDER BY view_date ASC",
    $start_date,
    $end_date
  ));

  $daily_map = [];
  foreach ($rows as $row) {
    $daily_map[$row->view_date] = $row;
  }

  $labels      = [];
  $views_data  = [];
  $posts_data  = [];
  for ($d = 1; $d <= $days; $d++) {
    $date_str     = sprintf('%s-%s-%02d', $year, $month_num, $d);
    $labels[]     = $d;
    $views_data[] = isset($daily_map[$date_str]) ? (int) $daily_map[$date_str]->total_views : 0;
    $posts_data[] = isset($daily_map[$date_str]) ? (int) $daily_map[$date_str]->post_count  : 0;
  }

  // Top 10 posts this month
  $top_posts = $wpdb->get_results($wpdb->prepare(
    "SELECT post_id, SUM(views) AS total_views
     FROM $table_daily
     WHERE view_date BETWEEN %s AND %s
     GROUP BY post_id
     ORDER BY total_views DESC
     LIMIT 10",
    $start_date,
    $end_date
  ));

  // Navigation
  $prev_month    = date('Y-m', strtotime('-1 month', $month_ts));
  $next_month    = date('Y-m', strtotime('+1 month', $month_ts));
  $current_month = current_time('Y-m');
  $month_label   = date('F Y', $month_ts);
  $prev_label    = date('F Y', strtotime($prev_month . '-01'));
  $next_label    = date('F Y', strtotime($next_month . '-01'));
  $base_url      = admin_url('index.php');

  $chart_id = 'pvt-chart-' . wp_unique_id();
  ?>
  <style>
    .pvt-wrap { font-size: 13px; color: #3c434a; }
    .pvt-section-title { display: flex; align-items: center; gap: 6px; font-weight: 600; margin-bottom: 10px; }
    .pvt-section-title .dashicons { color: #999; font-size: 16px; cursor: default; }
    .pvt-chart-box { position: relative; }
    .pvt-legend { display: flex; justify-content: center; gap: 20px; margin-top: 8px; font-size: 12px; }
    .pvt-legend span { display: flex; align-items: center; gap: 5px; }
    .pvt-legend-dot { width: 12px; height: 12px; display: inline-block; border-radius: 2px; }
    .pvt-legend-dot-views { background: rgba(78,154,241,0.7); }
    .pvt-legend-dot-posts { background: rgba(160,196,241,0.7); }
    .pvt-nav { display: flex; justify-content: space-between; align-items: center; margin: 14px 0; font-size: 12px; }
    .pvt-nav a { text-decoration: none; color: #2271b1; }
    .pvt-nav a:hover { text-decoration: underline; }
    .pvt-nav-disabled { color: #ccc; }
    .pvt-nav strong { font-size: 13px; }
    .pvt-posts-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .pvt-posts-table th { text-align: left; padding: 6px 8px; border-bottom: 2px solid #eee; font-weight: 600; color: #3c434a; }
    .pvt-posts-table th:last-child { text-align: right; }
    .pvt-posts-table td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .pvt-posts-table td:first-child { width: 28px; color: #888; font-weight: 600; }
    .pvt-posts-table td:last-child { text-align: right; white-space: nowrap; font-weight: 600; }
    .pvt-posts-table td a { text-decoration: none; color: #2271b1; }
    .pvt-posts-table td a:hover { text-decoration: underline; }
    .pvt-powered { text-align: center; font-size: 11px; color: #aaa; margin-top: 14px; }
    .pvt-no-data { color: #888; font-size: 12px; padding: 10px 0; }
  </style>

  <div class="pvt-wrap">

    <!-- Chart section -->
    <div class="pvt-section-title">
      Haysky Post Views
      <span class="dashicons dashicons-info-outline" title="Daily post views for this month"></span>
    </div>
    <div class="pvt-chart-box">
      <canvas id="<?php echo esc_attr($chart_id); ?>" height="130"></canvas>
    </div>
    <div class="pvt-legend">
      <span><span class="pvt-legend-dot pvt-legend-dot-views"></span> Total Views</span>
      <span><span class="pvt-legend-dot pvt-legend-dot-posts"></span> Posts</span>
    </div>

    <!-- Month navigation -->
    <?php echo pvt_nav_html($base_url, $prev_month, $prev_label, $month_label, $next_month, $next_label, $current_month); ?>

    <!-- Top Posts -->
    <div class="pvt-section-title" style="margin-top:6px;">
      Top Posts
      <span class="dashicons dashicons-info-outline" title="Top posts by views this month"></span>
    </div>
    <?php if ($top_posts): ?>
    <table class="pvt-posts-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Post</th>
          <th>Views</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($top_posts as $i => $row):
          $title    = get_the_title($row->post_id);
          $edit_url = get_edit_post_link($row->post_id);
        ?>
        <tr>
          <td><?php echo $i + 1; ?></td>
          <td><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($title); ?></a></td>
          <td><?php echo number_format((int) $row->total_views); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p class="pvt-no-data">No data available for this month.</p>
    <?php endif; ?>

    <!-- Bottom navigation -->
    <?php echo pvt_nav_html($base_url, $prev_month, $prev_label, $month_label, $next_month, $next_label, $current_month); ?>

    <p class="pvt-powered"><b>Powered by <a href="https://haysky.com">Haysky.com</a></b></p>
  </div>

  <script>
  (function () {
    var labels     = <?php echo json_encode($labels); ?>;
    var viewsData  = <?php echo json_encode($views_data); ?>;
    var postsData  = <?php echo json_encode($posts_data); ?>;
    var chartId    = <?php echo json_encode($chart_id); ?>;

    function initChart() {
      if (typeof Chart === 'undefined') { setTimeout(initChart, 150); return; }
      var ctx = document.getElementById(chartId);
      if (!ctx) return;

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Total Views',
              data: viewsData,
              backgroundColor: 'rgba(78,154,241,0.25)',
              borderColor: 'rgba(78,154,241,1)',
              borderWidth: 2,
              pointRadius: 3,
              pointBackgroundColor: 'rgba(78,154,241,1)',
              fill: true,
              tension: 0.35,
              yAxisID: 'y'
            },
            {
              label: 'Posts',
              data: postsData,
              backgroundColor: 'rgba(160,196,241,0.2)',
              borderColor: 'rgba(160,196,241,1)',
              borderWidth: 2,
              pointRadius: 3,
              pointBackgroundColor: 'rgba(160,196,241,1)',
              fill: true,
              tension: 0.35,
              yAxisID: 'y2'
            }
          ]
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          plugins: { legend: { display: false } },
          scales: {
            x: {
              grid: { color: 'rgba(0,0,0,0.04)' },
              ticks: { font: { size: 11 }, maxTicksLimit: 10 }
            },
            y: {
              beginAtZero: true,
              position: 'left',
              grid: { color: 'rgba(0,0,0,0.06)' },
              ticks: { font: { size: 11 } }
            },
            y2: {
              beginAtZero: true,
              display: false
            }
          }
        }
      });
    }

    initChart();
  })();
  </script>
  <?php
}

function pvt_nav_html($base_url, $prev_month, $prev_label, $month_label, $next_month, $next_label, $current_month)
{
  $prev_url = esc_url(add_query_arg('pvt_month', $prev_month, $base_url));
  $next_url = esc_url(add_query_arg('pvt_month', $next_month, $base_url));
  $can_next = $next_month <= $current_month;

  $html  = '<div class="pvt-nav">';
  $html .= '<a href="' . $prev_url . '">&lsaquo; ' . esc_html($prev_label) . '</a>';
  $html .= '<strong>' . esc_html($month_label) . '</strong>';
  if ($can_next) {
    $html .= '<a href="' . $next_url . '">' . esc_html($next_label) . ' &rsaquo;</a>';
  } else {
    $html .= '<span class="pvt-nav-disabled">' . esc_html($next_label) . ' &rsaquo;</span>';
  }
  $html .= '</div>';
  return $html;
}
