<?php

if (!get_option('haysky_show_active_sessions', false)) {
    return;
}

// ---------------------------------------------------------------------------
// 1. Store IP & UA in session data when auth cookie is set (WP 4.9+)
// ---------------------------------------------------------------------------
add_action('set_auth_cookie', 'has_store_session_info', 10, 6);
function has_store_session_info($auth_cookie, $expire, $expiration, $user_id, $scheme, $token)
{
    $manager = WP_Session_Tokens::get_instance($user_id);
    $session  = $manager->get($token);
    if ($session) {
        $session['ip']    = has_get_client_ip();
        $session['ua']    = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '';
        $session['login'] = time();
        $manager->update($token, $session);
    }
}

// ---------------------------------------------------------------------------
// 2. Helpers
// ---------------------------------------------------------------------------
function has_get_client_ip()
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', wp_unslash($_SERVER[$h]))[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '';
}

function has_is_bot($ua)
{
    if (empty($ua)) return true;
    $patterns = [
        'bot', 'crawl', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandex',
        'baidu', 'duckduck', 'facebookexternalhit', 'twitterbot', 'linkedinbot',
        'whatsapp', 'curl', 'wget', 'python', 'java/', 'libwww', 'scrapy',
        'httpclient', 'go-http-client', 'okhttp', 'axios', 'headlesschrome',
        'phantomjs', 'selenium',
    ];
    $lower = strtolower($ua);
    foreach ($patterns as $p) {
        if (strpos($lower, $p) !== false) return true;
    }
    return false;
}

function has_parse_ua($ua)
{
    if (empty($ua)) {
        return ['browser' => 'Unknown', 'os' => 'Unknown', 'device' => 'Unknown'];
    }

    // OS
    $os = 'Unknown';
    if (preg_match('/Windows NT 10/i', $ua))      $os = 'Windows 10/11';
    elseif (preg_match('/Windows NT 6\.3/i', $ua)) $os = 'Windows 8.1';
    elseif (preg_match('/Windows NT 6\.2/i', $ua)) $os = 'Windows 8';
    elseif (preg_match('/Windows NT 6\.1/i', $ua)) $os = 'Windows 7';
    elseif (preg_match('/Windows/i', $ua))         $os = 'Windows';
    elseif (preg_match('/iPhone/i', $ua))          $os = 'iOS (iPhone)';
    elseif (preg_match('/iPad/i', $ua))            $os = 'iOS (iPad)';
    elseif (preg_match('/Android/i', $ua))         $os = 'Android';
    elseif (preg_match('/Mac OS X/i', $ua))        $os = 'macOS';
    elseif (preg_match('/Linux/i', $ua))           $os = 'Linux';

    // Browser (order matters: Edge/Opera before Chrome)
    $browser = 'Unknown';
    if (preg_match('/Edg\//i', $ua))                         $browser = 'Edge';
    elseif (preg_match('/OPR\/(\d+)/i', $ua, $m))            $browser = 'Opera ' . $m[1];
    elseif (preg_match('/Opera\/(\d+)/i', $ua, $m))          $browser = 'Opera ' . $m[1];
    elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m))         $browser = 'Chrome ' . $m[1];
    elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m))        $browser = 'Firefox ' . $m[1];
    elseif (preg_match('/Version\/[\d\.]+.*Safari/i', $ua))  $browser = 'Safari';
    elseif (preg_match('/MSIE|Trident/i', $ua))              $browser = 'Internet Explorer';

    // Device
    $device = 'Desktop';
    if (preg_match('/Mobi|Android|iPhone|iPad|tablet/i', $ua)) $device = 'Mobile/Tablet';

    return compact('browser', 'os', 'device');
}

// ---------------------------------------------------------------------------
// 3. Handle revoke actions (admin-post handler)
// ---------------------------------------------------------------------------
add_action('admin_post_has_session_action', 'has_handle_session_action');
function has_handle_session_action()
{
    $action         = isset($_POST['has_session_action'])    ? sanitize_text_field($_POST['has_session_action'])    : '';
    $target_user_id = isset($_POST['has_target_user_id'])   ? intval($_POST['has_target_user_id'])                 : 0;

    if (!$target_user_id || !$action) {
        wp_die('Invalid request.', 403);
    }

    $current_user_id = get_current_user_id();

    // Permission check
    if ($current_user_id !== $target_user_id && !current_user_can('manage_options')) {
        wp_die('Insufficient permissions.', 403);
    }

    check_admin_referer('has_session_nonce_' . $target_user_id);

    $manager = WP_Session_Tokens::get_instance($target_user_id);

    if ($action === 'destroy_all') {
        if ($current_user_id === $target_user_id) {
            // Keep own current session
            $current_token = wp_get_session_token();
            if ($current_token) {
                $manager->destroy_others($current_token);
            } else {
                $manager->destroy_all();
            }
        } else {
            $manager->destroy_all();
        }

    } elseif ($action === 'destroy_one') {
        $token_hash = isset($_POST['has_session_token']) ? sanitize_text_field($_POST['has_session_token']) : '';
        if (!$token_hash) {
            wp_die('Missing token.', 400);
        }

        // Prevent a user from revoking their own current session from this form
        if ($current_user_id === $target_user_id) {
            $current_token = wp_get_session_token();
            $current_hash  = $current_token ? hash('sha256', $current_token) : '';
            if ($token_hash === $current_hash) {
                wp_die('Cannot revoke your own current session here. Use WordPress logout instead.', 400);
            }
        }

        $sessions = get_user_meta($target_user_id, 'session_tokens', true);
        if (is_array($sessions) && array_key_exists($token_hash, $sessions)) {
            unset($sessions[$token_hash]);
            update_user_meta($target_user_id, 'session_tokens', $sessions);
        }
    }

    // Redirect back
    if ($current_user_id === $target_user_id) {
        $redirect = admin_url('profile.php');
    } else {
        $redirect = admin_url('user-edit.php?user_id=' . $target_user_id);
    }

    wp_redirect(add_query_arg('has_updated', '1', $redirect) . '#has-active-sessions');
    exit;
}

// ---------------------------------------------------------------------------
// 4. Render sessions panel on profile pages
// ---------------------------------------------------------------------------
add_action('show_user_profile', 'has_render_sessions_panel');   // own profile
add_action('edit_user_profile', 'has_render_sessions_panel');   // admin editing another user

function has_render_sessions_panel(WP_User $user)
{
    $user_id         = $user->ID;
    $current_user_id = get_current_user_id();

    if ($current_user_id !== $user_id && !current_user_can('manage_options')) {
        return;
    }

    $sessions     = get_user_meta($user_id, 'session_tokens', true);
    if (!is_array($sessions)) $sessions = [];

    // Sort by login time descending
    uasort($sessions, function ($a, $b) {
        $la = isset($a['login']) ? $a['login'] : 0;
        $lb = isset($b['login']) ? $b['login'] : 0;
        return $lb <=> $la;
    });

    $is_own_profile = ($current_user_id === $user_id);
    $current_token  = $is_own_profile ? wp_get_session_token() : '';
    $current_hash   = $current_token  ? hash('sha256', $current_token) : '';

    ?>
    <div id="has-active-sessions" style="margin-top:2em;">
        <h2>Active Sessions</h2>

        <?php if (isset($_GET['has_updated'])): ?>
            <div class="notice notice-success is-dismissible" style="max-width:900px;">
                <p>Sessions updated successfully.</p>
            </div>
        <?php endif; ?>

        <?php if (empty($sessions)): ?>
            <p>No active sessions found.</p>
        <?php else: ?>

            <p style="color:#555;">
                <?php echo count($sessions); ?> active session(s) for
                <strong><?php echo esc_html($user->display_name); ?></strong>.
            </p>

            <table class="wp-list-table widefat fixed striped" style="max-width:1200px;margin-bottom:1em;">
                <thead>
                    <tr>
                        <th style="width:90px;">Status</th>
                        <th style="width:130px;">IP Address</th>
                        <th>Browser / OS</th>
                        <th style="width:110px;">Device</th>
                        <th style="width:60px;">Bot?</th>
                        <th style="width:150px;">Login Time</th>
                        <th style="width:150px;">Expires</th>
                        <th style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sessions as $token_hash => $session):
                    $ip         = isset($session['ip'])         ? $session['ip']         : '';
                    $ua         = isset($session['ua'])         ? $session['ua']         : '';
                    $login_ts   = isset($session['login'])      ? $session['login']      : 0;
                    $expire_ts  = isset($session['expiration']) ? $session['expiration'] : 0;
                    $is_current = ($token_hash === $current_hash);
                    $is_bot     = has_is_bot($ua);
                    $ua_info    = has_parse_ua($ua);
                    $row_style  = $is_current ? 'background:#eeffee;' : '';
                ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td>
                            <?php if ($is_current): ?>
                                <span style="color:#006600;font-weight:bold;">&#x25CF;&nbsp;Current</span>
                            <?php else: ?>
                                <span style="color:#aaa;">&#x25CB;&nbsp;Other</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $ip ? esc_html($ip) : '<em style="color:#aaa;">N/A</em>'; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($ua_info['browser']); ?></strong>
                            &nbsp;&mdash;&nbsp;
                            <span style="color:#555;"><?php echo esc_html($ua_info['os']); ?></span>
                            <?php if ($ua): ?>
                                <br>
                                <small style="color:#aaa;font-size:0.72em;" title="<?php echo esc_attr($ua); ?>">
                                    <?php echo esc_html(mb_substr($ua, 0, 100) . (mb_strlen($ua) > 100 ? '…' : '')); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($ua_info['device']); ?></td>
                        <td>
                            <?php if ($is_bot): ?>
                                <span style="color:#cc0000;font-weight:bold;" title="User-agent matches a known bot/crawler pattern">
                                    &#x26A0; Yes
                                </span>
                            <?php else: ?>
                                <span style="color:#006600;">&#x2713; No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $login_ts
                                ? esc_html(wp_date('Y-m-d H:i:s', $login_ts))
                                : '<em style="color:#aaa;">N/A</em>'; ?>
                        </td>
                        <td>
                            <?php echo $expire_ts
                                ? esc_html(wp_date('Y-m-d H:i:s', $expire_ts))
                                : '<em style="color:#aaa;">N/A</em>'; ?>
                        </td>
                        <td>
                            <?php if (!$is_current): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <?php wp_nonce_field('has_session_nonce_' . $user_id); ?>
                                    <input type="hidden" name="action"             value="has_session_action">
                                    <input type="hidden" name="has_session_action" value="destroy_one">
                                    <input type="hidden" name="has_target_user_id" value="<?php echo esc_attr($user_id); ?>">
                                    <input type="hidden" name="has_session_token"  value="<?php echo esc_attr($token_hash); ?>">
                                    <button type="submit" class="button button-small"
                                        style="color:#cc0000;border-color:#cc0000;"
                                        onclick="return confirm('Revoke this session?');">
                                        Revoke
                                    </button>
                                </form>
                            <?php else: ?>
                                <em style="color:#aaa;font-size:0.85em;">This session</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                <?php wp_nonce_field('has_session_nonce_' . $user_id); ?>
                <input type="hidden" name="action"             value="has_session_action">
                <input type="hidden" name="has_session_action" value="destroy_all">
                <input type="hidden" name="has_target_user_id" value="<?php echo esc_attr($user_id); ?>">
                <button type="submit" class="button button-secondary"
                    onclick="return confirm('<?php echo $is_own_profile
                        ? 'Revoke all other sessions? You will remain logged in.'
                        : 'Revoke ALL sessions for this user? They will be logged out everywhere.'; ?>');">
                    <?php echo $is_own_profile ? 'Revoke All Other Sessions' : 'Revoke All Sessions'; ?>
                </button>
            </form>

        <?php endif; ?>
    </div>
    <?php
}
