<?php
defined('ABSPATH') || exit;

$username = get_query_var('blp_username');
if (!$username && isset($_GET['blp_username'])) {
    $username = sanitize_key(wp_unslash($_GET['blp_username']));
}
$is_preview = isset($_GET['blp_preview']);
$profile  = $is_preview
    ? BLP_Database::get_profile_by_username_any($username)
    : BLP_Database::get_profile_by_username($username);

if (!$profile) {
    status_header(404);
    wp_die('<h1>404 — Page not found</h1><p>This BioLink page does not exist.</p>');
}

$links = BLP_Database::get_links($profile->id, true);
$now = current_time('mysql', true);
$visible_links = [];
foreach ($links as $link) {
    if (!empty($link->schedule_start) && $now < $link->schedule_start) {
        continue;
    }
    if (!empty($link->schedule_end) && $now > $link->schedule_end) {
        continue;
    }

    $visible_links[] = $link;
}

// Parse theme settings
$theme = [
    'theme'                => 'midnight-glass',
    'bg_type'              => 'solid',
    'bg_color'             => '#0a0a1a',
    'bg_gradient_start'    => '#0a0a1a',
    'bg_gradient_end'      => '#1a0a3a',
    'bg_gradient_angle'    => 135,
    'bg_gradient_mid'      => '',
    'accent_color'         => '#7c6df0',
    'text_color'           => '#ffffff',
    'card_color'           => '#1a1a2e',
    'card_opacity'         => 90,
    'card_blur'            => 0,
    'font_family'          => 'DM Sans',
    'button_style'         => 'rounded',
    'button_effect'        => 'lift',
    'avatar_shape'         => 'circle',
    'layout_variant'       => 'center_classic',
    'show_social_icons'    => true,
    'enable_particles'     => false,
    'enable_gradient_anim' => false,
    'bg_effect'            => 'none',
    'effect_intensity'     => 45,
    'bg_image_url'         => '',
    'bg_image_overlay'     => 50,
    'bg_image_blur'        => 0,
    'banner_url'           => '',
    'banner_height'        => 200,
    'countdown_date'       => '',
    'countdown_label'      => '',
    'status_text'          => '',
    'status_emoji'         => '',
    'bg_video_url'         => '',
    'music_url'            => '',
    'enable_darklight'     => false,
    'light_bg_color'       => '#f5f5f5',
    'light_text_color'     => '#1a1a1a',
    'light_card_color'     => '#ffffff',
    'og_image_url'         => '',
    'meta_description'     => '',
    'custom_css'           => '',
    'social_links'         => [],
];

if (!empty($profile->theme_settings)) {
    $saved = json_decode($profile->theme_settings, true);
    if ($saved) {
        $theme = array_merge($theme, $saved);
    }
}

// Allow authenticated preview override via GET params (auth validated in BLP_Frontend)
if (isset($_GET['blp_preview'])) {
    foreach ($_GET as $k => $v) {
        if (strpos($k, 'd_') === 0) {
            $key = substr($k, 2);
            if (array_key_exists($key, $theme)) {
                if ($key === 'social_links') {
                    $decoded = json_decode(wp_unslash((string) $v), true);
                    $safe_social = [];
                    if (is_array($decoded)) {
                        foreach ($decoded as $platform => $url) {
                            $safe_platform = sanitize_key($platform);
                            $safe_url = esc_url_raw((string) $url, ['http', 'https']);
                            if ($safe_platform && $safe_url) {
                                $safe_social[$safe_platform] = $safe_url;
                            }
                        }
                    }
                    $theme[$key] = $safe_social;
                } elseif (in_array($key, ['enable_particles', 'enable_gradient_anim', 'show_social_icons'], true)) {
                    $theme[$key] = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                } elseif (in_array($key, ['card_opacity', 'bg_gradient_angle', 'effect_intensity'], true)) {
                    $theme[$key] = (int) $v;
                } else {
                    $theme[$key] = sanitize_text_field($v);
                }
            }
        }
    }
}

// Helpers (must be defined before first use)
if (!function_exists('blp_hex_to_rgba')) {
    function blp_hex_to_rgba($hex, $opacity = 1) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = str_repeat($hex[0], 2) . str_repeat($hex[1], 2) . str_repeat($hex[2], 2);
        if (strlen($hex) !== 6) return "rgba(0,0,0,$opacity)";
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r,$g,$b,$opacity)";
    }
}

if (!function_exists('blp_safe_color')) {
    /**
     * Validate a color value for CSS output.
     * Only hex colors (#RGB or #RRGGBB) are allowed.
     * Returns the fallback on invalid input.
     */
    function blp_safe_color($color, $fallback = '#000000') {
        $clean = sanitize_hex_color(trim((string) $color));
        return $clean ?: $fallback;
    }
}

// Build background CSS (all color values validated to hex-only before CSS output)
$bg_css = '';
if ($theme['bg_type'] === 'gradient' || $theme['bg_type'] === 'animated') {
    $angle     = (int) $theme['bg_gradient_angle'];
    $grad_from = blp_safe_color($theme['bg_gradient_start'], '#0a0a1a');
    $grad_to   = blp_safe_color($theme['bg_gradient_end'],   '#1a0a3a');
    if (!empty($theme['bg_gradient_mid'])) {
        $grad_mid = blp_safe_color($theme['bg_gradient_mid'], '');
        if ($grad_mid) {
            $bg_css = "background: linear-gradient({$angle}deg, {$grad_from}, {$grad_mid}, {$grad_to});";
        } else {
            $bg_css = "background: linear-gradient({$angle}deg, {$grad_from}, {$grad_to});";
        }
    } else {
        $bg_css = "background: linear-gradient({$angle}deg, {$grad_from}, {$grad_to});";
    }
} else {
    $bg_css = 'background: ' . blp_safe_color($theme['bg_color'], '#0a0a1a') . ';';
}

// Background image overlay
$bg_image_css = '';
if (!empty($theme['bg_image_url'])) {
    $bg_img_url = esc_url($theme['bg_image_url']);
    $bg_overlay = max(0, min(100, (int)($theme['bg_image_overlay'] ?? 50)));
    $bg_img_blur = max(0, min(20, (int)($theme['bg_image_blur'] ?? 0)));
    $overlay_rgba = "rgba(0,0,0," . ($bg_overlay / 100) . ")";
    $bg_image_css = "background-image: linear-gradient({$overlay_rgba},{$overlay_rgba}), url('{$bg_img_url}'); background-size: cover; background-position: center; background-attachment: fixed;";
    if ($bg_img_blur > 0) {
        $bg_image_css .= " filter: blur({$bg_img_blur}px);";
    }
}

// Card opacity helper
$opacity    = max(0.2, min(1.0, (int) $theme['card_opacity'] / 100));
$card_rgba  = blp_hex_to_rgba(ltrim(blp_safe_color($theme['card_color'], '#1a1a2e'), '#'), $opacity);

// Card glassmorphism blur
$card_blur = max(0, min(30, (int)($theme['card_blur'] ?? 0)));
$card_blur_css = $card_blur > 0 ? "backdrop-filter: blur({$card_blur}px); -webkit-backdrop-filter: blur({$card_blur}px);" : '';

// Avatar shape CSS
$is_hexagon = $theme['avatar_shape'] === 'hexagon';
$avatar_radius = $theme['avatar_shape'] === 'circle' ? '50%'
    : ($theme['avatar_shape'] === 'square' ? '12px' : '0');
$avatar_clip = $is_hexagon ? 'polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%)' : 'none';

// Button border-radius
$btn_radius_map = [
    'rounded' => '12px',
    'pill'    => '9999px',
    'square'  => '6px',
    'outline' => '12px',
    'glass'   => '12px',
    'solid'   => '12px',
];
$btn_radius = $btn_radius_map[$theme['button_style']] ?? '12px';

// Google Font URL
$font_encoded = urlencode($theme['font_family']);
$font_url = "https://fonts.googleapis.com/css2?family={$font_encoded}:wght@300;400;500;600;700&display=swap";

$allowed_layouts = [
    'center_classic',
    'profile_left_info_right',
    'profile_right_info_left',
    'business_card_horizontal',
    'magazine_split',
    'minimal_stack',
    'executive_clean',
    'studio_bento',
    'bold_banner_top',
    'avatar_floating_sidebar',
    'compact_contact_card',
    'spotlight_onepage',
    'hero_split_pro',
    'stacked_cards_modern',
    'cover_profile_panel',
    'creator_spotlight',
    'minimal_directory',
    'agency_brief',
    'timeline_story',
    'split_hero_cards',
    'mosaic_showcase',
    'executive_sidebar_pro',
    'press_kit_split',
    'minimal_premium_stack',
];

$allowed_bg_effects = ['none', 'mesh', 'spotlight', 'grain', 'orbs', 'aurora', 'waves', 'constellation'];

$layout_variant = sanitize_key((string) ($theme['layout_variant'] ?? 'center_classic'));
if (!in_array($layout_variant, $allowed_layouts, true)) {
    $layout_variant = 'center_classic';
}

$bg_effect = sanitize_key((string) ($theme['bg_effect'] ?? 'none'));
if (!in_array($bg_effect, $allowed_bg_effects, true)) {
    $bg_effect = 'none';
}

$effect_intensity = max(0, min(100, (int) ($theme['effect_intensity'] ?? 45)));
$effect_strength = max(0, min(1, $effect_intensity / 100));

$show_social_icons = filter_var($theme['show_social_icons'] ?? true, FILTER_VALIDATE_BOOLEAN);

$social_links = [];
if (!empty($theme['social_links']) && is_array($theme['social_links'])) {
    $social_links = $theme['social_links'];
} elseif (!empty($theme['social_links']) && is_string($theme['social_links'])) {
    $social_links = json_decode($theme['social_links'], true) ?: [];
}

$social_links = array_filter($social_links, static function ($url) {
    return !empty(esc_url_raw((string) $url, ['http', 'https']));
});

$layout_class = 'blp-layout-' . $layout_variant;

$display_name  = !empty($profile->display_name) ? $profile->display_name : $profile->username;
$og_image      = !empty($theme['og_image_url']) ? esc_url($theme['og_image_url']) : (!empty($profile->avatar_url) ? esc_url($profile->avatar_url) : '');
$og_desc       = !empty($theme['meta_description']) ? esc_attr($theme['meta_description']) : (!empty($profile->bio) ? esc_attr(wp_trim_words($profile->bio, 20, '...')) : esc_attr($display_name . '\'s BioLink page'));
$canonical_url = esc_url(blp_profile_url($profile->username));

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc_html($display_name); ?> | BioLink Pro</title>
  <meta name="description" content="<?php echo $og_desc; ?>">
  <link rel="canonical" href="<?php echo $canonical_url; ?>">
  <link rel="icon" href="<?php echo esc_url($profile->avatar_url ?: get_site_icon_url(32, '', true)); ?>">

  <!-- Open Graph -->
  <meta property="og:type"        content="profile">
  <meta property="og:title"       content="<?php echo esc_attr($display_name); ?>">
  <meta property="og:description" content="<?php echo $og_desc; ?>">
  <meta property="og:url"         content="<?php echo $canonical_url; ?>">
  <?php if ($og_image): ?>
  <meta property="og:image"       content="<?php echo $og_image; ?>">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary">
  <meta name="twitter:title"       content="<?php echo esc_attr($display_name); ?>">
  <meta name="twitter:description" content="<?php echo $og_desc; ?>">
  <?php if ($og_image): ?>
  <meta name="twitter:image"       content="<?php echo $og_image; ?>">
  <?php endif; ?>

  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": <?php echo wp_json_encode($display_name); ?>,
    "url": <?php echo wp_json_encode($canonical_url); ?>,
    <?php if (!empty($profile->bio)): ?>"description": <?php echo wp_json_encode($profile->bio); ?>,<?php endif; ?>
    <?php if ($og_image): ?>"image": <?php echo wp_json_encode($og_image); ?>,<?php endif; ?>
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": <?php echo wp_json_encode($canonical_url); ?>
    }
  }
  </script>

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?php echo esc_url(add_query_arg(['blp_manifest' => $profile->id], home_url('/'))); ?>">
  <meta name="theme-color" content="<?php echo blp_safe_color($theme['accent_color'], '#7c6df0'); ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($display_name); ?>">
  <?php if ($og_image): ?>
  <link rel="apple-touch-icon" href="<?php echo $og_image; ?>">
  <?php endif; ?>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="<?php echo esc_url($font_url); ?>" rel="stylesheet">

  <?php wp_head(); ?>

  <style>
    <?php
    // Custom font @font-face injection
    $font_family_name = $theme['font_family'];
    $custom_font_face = '';
    if (strpos($font_family_name, 'custom:') === 0) {
        $custom_font_name = substr($font_family_name, 7);
        // Look up the font URL from the profile owner's custom fonts
        $owner_fonts = get_user_meta($profile->user_id, 'blp_custom_fonts', true) ?: [];
        foreach ($owner_fonts as $cf) {
            if ($cf['name'] === $custom_font_name && !empty($cf['url'])) {
                $format_map = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];
                $fmt = $format_map[$cf['format'] ?? 'woff2'] ?? 'woff2';
                $custom_font_face = "@font-face { font-family: '" . esc_attr($custom_font_name) . "'; src: url('" . esc_url($cf['url']) . "') format('" . $fmt . "'); font-weight: 100 900; font-display: swap; }";
                $font_family_name = $custom_font_name;
                break;
            }
        }
    }
    if ($custom_font_face) echo $custom_font_face;
    ?>

    /* ── CSS Variables ── */
    :root {
      --blp-accent:       <?php echo blp_safe_color($theme['accent_color'], '#7c6df0'); ?>;
      --blp-text:         <?php echo blp_safe_color($theme['text_color'],   '#ffffff'); ?>;
      --blp-card:         <?php echo $card_rgba; ?>;
      --blp-btn-radius:   <?php echo esc_attr($btn_radius); ?>;
      --blp-font:         '<?php echo esc_attr($font_family_name); ?>', system-ui, sans-serif;
      --blp-border:       rgba(255,255,255,0.1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      min-height: 100vh;
      font-family: var(--blp-font);
      color: var(--blp-text);
      -webkit-font-smoothing: antialiased;
    }

    .blp-page {
      <?php echo $bg_css; ?>
      <?php if ($bg_image_css): echo $bg_image_css; endif; ?>
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: clamp(30px, 5vw, 52px) clamp(14px, 3.5vw, 28px) clamp(56px, 9vh, 84px);
      position: relative;
      overflow: hidden;
    }

    <?php if (filter_var($theme['enable_gradient_anim'], FILTER_VALIDATE_BOOLEAN) && $theme['bg_type'] !== 'solid'): ?>
    .blp-page {
      background-size: 400% 400%;
      animation: blp-gradient-shift 10s ease infinite;
    }
    @keyframes blp-gradient-shift {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    <?php endif; ?>

    /* Dynamic background effect layers */
    .blp-page::before,
    .blp-page::after {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      opacity: 0;
    }

    <?php if ($bg_effect === 'grain'): ?>
    .blp-page::before {
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
      background-size: 200px 200px;
      opacity: <?php echo esc_attr(0.08 + ($effect_strength * 0.34)); ?>;
    }
    <?php elseif ($bg_effect === 'mesh'): ?>
    .blp-page::before {
      background:
        radial-gradient(circle at 18% 22%, <?php echo esc_attr(blp_hex_to_rgba(ltrim(blp_safe_color($theme['accent_color'], '#7c6df0'), '#'), 0.34 + ($effect_strength * 0.26))); ?>, transparent 52%),
        radial-gradient(circle at 82% 18%, rgba(255,255,255,<?php echo esc_attr(0.1 + ($effect_strength * 0.2)); ?>), transparent 48%),
        radial-gradient(circle at 52% 82%, rgba(56,189,248,<?php echo esc_attr(0.12 + ($effect_strength * 0.24)); ?>), transparent 55%);
      opacity: 1;
    }
    .blp-page::after {
      background:
        linear-gradient(125deg, rgba(255,255,255,0.08), transparent 38%, rgba(255,255,255,0.03) 72%, transparent),
        linear-gradient(315deg, rgba(255,255,255,0.06), transparent 45%);
      mix-blend-mode: screen;
      opacity: <?php echo esc_attr(0.16 + ($effect_strength * 0.24)); ?>;
    }
    <?php elseif ($bg_effect === 'spotlight'): ?>
    .blp-page::before {
      background:
        radial-gradient(circle at 50% 12%, rgba(255,255,255,<?php echo esc_attr(0.16 + ($effect_strength * 0.28)); ?>), transparent 44%),
        radial-gradient(circle at 50% 110%, rgba(0,0,0,<?php echo esc_attr(0.18 + ($effect_strength * 0.34)); ?>), transparent 64%);
      opacity: 1;
    }
    <?php elseif ($bg_effect === 'orbs'): ?>
    .blp-page::before {
      background:
        radial-gradient(circle at 12% 18%, <?php echo esc_attr(blp_hex_to_rgba(ltrim(blp_safe_color($theme['accent_color'], '#7c6df0'), '#'), 0.26 + ($effect_strength * 0.26))); ?>, transparent 56%),
        radial-gradient(circle at 88% 24%, rgba(251,191,36,<?php echo esc_attr(0.16 + ($effect_strength * 0.22)); ?>), transparent 52%),
        radial-gradient(circle at 76% 88%, rgba(56,189,248,<?php echo esc_attr(0.2 + ($effect_strength * 0.18)); ?>), transparent 58%);
      opacity: 1;
      animation: blp-orb-drift 16s ease-in-out infinite alternate;
    }
    .blp-page::after {
      background:
        radial-gradient(circle at 26% 80%, rgba(255,255,255,<?php echo esc_attr(0.08 + ($effect_strength * 0.14)); ?>), transparent 44%);
      opacity: 1;
      animation: blp-orb-drift-rev 20s ease-in-out infinite alternate;
    }
    @keyframes blp-orb-drift {
      0% { transform: translate3d(-1.5%, -1.2%, 0) scale(1); }
      100% { transform: translate3d(2.4%, 2.8%, 0) scale(1.04); }
    }
    @keyframes blp-orb-drift-rev {
      0% { transform: translate3d(1.4%, 0.8%, 0) scale(1.02); }
      100% { transform: translate3d(-2.2%, -2.6%, 0) scale(0.98); }
    }
    <?php elseif ($bg_effect === 'aurora'): ?>
    .blp-page::before {
      background:
        radial-gradient(circle at 10% 20%, rgba(16,185,129,<?php echo esc_attr(0.18 + ($effect_strength * 0.22)); ?>), transparent 48%),
        radial-gradient(circle at 90% 18%, rgba(56,189,248,<?php echo esc_attr(0.2 + ($effect_strength * 0.22)); ?>), transparent 50%),
        radial-gradient(circle at 50% 78%, <?php echo esc_attr(blp_hex_to_rgba(ltrim(blp_safe_color($theme['accent_color'], '#7c6df0'), '#'), 0.2 + ($effect_strength * 0.24))); ?>, transparent 56%);
      opacity: 1;
      filter: blur(4px);
      animation: blp-aurora-drift 18s ease-in-out infinite alternate;
    }
    .blp-page::after {
      background: linear-gradient(115deg, rgba(255,255,255,0.06), transparent 34%, rgba(255,255,255,0.02) 68%, transparent 100%);
      mix-blend-mode: screen;
      opacity: <?php echo esc_attr(0.16 + ($effect_strength * 0.2)); ?>;
      animation: blp-aurora-sheen 14s ease-in-out infinite alternate;
    }
    @keyframes blp-aurora-drift {
      0% { transform: translate3d(-2%, 0, 0) scale(1.02); }
      100% { transform: translate3d(2%, -2%, 0) scale(1.08); }
    }
    @keyframes blp-aurora-sheen {
      0% { transform: translate3d(0, 0, 0); }
      100% { transform: translate3d(-2.4%, 1.8%, 0); }
    }
    <?php elseif ($bg_effect === 'waves'): ?>
    .blp-page::before {
      background:
        radial-gradient(120% 55% at 50% 0%, rgba(255,255,255,<?php echo esc_attr(0.06 + ($effect_strength * 0.1)); ?>), transparent 60%),
        radial-gradient(120% 55% at 50% 100%, rgba(255,255,255,<?php echo esc_attr(0.04 + ($effect_strength * 0.1)); ?>), transparent 62%),
        linear-gradient(160deg, rgba(255,255,255,0.04), transparent 42%, rgba(255,255,255,0.02) 72%, transparent);
      opacity: 1;
      animation: blp-wave-shift 14s ease-in-out infinite alternate;
    }
    .blp-page::after {
      background:
        repeating-linear-gradient(
          -12deg,
          rgba(255,255,255,<?php echo esc_attr(0.02 + ($effect_strength * 0.04)); ?>) 0,
          rgba(255,255,255,<?php echo esc_attr(0.02 + ($effect_strength * 0.04)); ?>) 2px,
          transparent 2px,
          transparent 14px
        );
      opacity: <?php echo esc_attr(0.22 + ($effect_strength * 0.18)); ?>;
      animation: blp-wave-lines 20s linear infinite;
    }
    @keyframes blp-wave-shift {
      0% { transform: translate3d(0, -2%, 0) scale(1.02); }
      100% { transform: translate3d(0, 2.2%, 0) scale(1.06); }
    }
    @keyframes blp-wave-lines {
      0% { transform: translate3d(0, 0, 0); }
      100% { transform: translate3d(0, -26px, 0); }
    }
    <?php elseif ($bg_effect === 'constellation'): ?>
    .blp-page::before {
      background:
        radial-gradient(circle at 12% 26%, rgba(255,255,255,<?php echo esc_attr(0.34 + ($effect_strength * 0.16)); ?>) 0, rgba(255,255,255,<?php echo esc_attr(0.34 + ($effect_strength * 0.16)); ?>) 1px, transparent 1.4px),
        radial-gradient(circle at 26% 72%, rgba(255,255,255,<?php echo esc_attr(0.26 + ($effect_strength * 0.14)); ?>) 0, rgba(255,255,255,<?php echo esc_attr(0.26 + ($effect_strength * 0.14)); ?>) 1px, transparent 1.6px),
        radial-gradient(circle at 54% 18%, rgba(255,255,255,<?php echo esc_attr(0.3 + ($effect_strength * 0.14)); ?>) 0, rgba(255,255,255,<?php echo esc_attr(0.3 + ($effect_strength * 0.14)); ?>) 1px, transparent 1.4px),
        radial-gradient(circle at 78% 38%, rgba(255,255,255,<?php echo esc_attr(0.22 + ($effect_strength * 0.16)); ?>) 0, rgba(255,255,255,<?php echo esc_attr(0.22 + ($effect_strength * 0.16)); ?>) 1px, transparent 1.6px),
        radial-gradient(circle at 88% 76%, rgba(255,255,255,<?php echo esc_attr(0.34 + ($effect_strength * 0.16)); ?>) 0, rgba(255,255,255,<?php echo esc_attr(0.34 + ($effect_strength * 0.16)); ?>) 1px, transparent 1.4px);
      background-size: 220px 220px;
      opacity: 1;
      animation: blp-stars-drift 26s linear infinite;
    }
    .blp-page::after {
      background:
        linear-gradient(120deg, transparent 0%, rgba(255,255,255,<?php echo esc_attr(0.04 + ($effect_strength * 0.08)); ?>) 50%, transparent 100%);
      opacity: <?php echo esc_attr(0.12 + ($effect_strength * 0.18)); ?>;
      animation: blp-stars-scan 12s ease-in-out infinite alternate;
    }
    @keyframes blp-stars-drift {
      0% { transform: translate3d(0, 0, 0); }
      100% { transform: translate3d(-22px, 18px, 0); }
    }
    @keyframes blp-stars-scan {
      0% { transform: translate3d(-2%, 0, 0); }
      100% { transform: translate3d(2%, 0, 0); }
    }
    <?php endif; ?>

    /* Particle canvas */
    #blp-particles {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
    }

    /* Container */
    .blp-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: min(100%, 520px);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: clamp(6px, 1.6vw, 14px);
    }

    /* ── Profile Header ── */
    .blp-header {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      margin-bottom: clamp(22px, 4.4vw, 36px);
      animation: blp-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .blp-avatar-wrap {
      position: relative;
      margin-bottom: 20px;
    }

    .blp-avatar {
      width: 96px;
      height: 96px;
      border-radius: <?php echo $avatar_radius; ?>;
      <?php if ($is_hexagon): ?>clip-path: <?php echo $avatar_clip; ?>; border: none;<?php else: ?>border: 3px solid var(--blp-accent);<?php endif; ?>
      object-fit: cover;
      display: block;
      box-shadow: 0 0 0 4px rgba(255,255,255,0.05), 0 8px 32px rgba(0,0,0,0.3);
    }

    .blp-avatar-placeholder {
      width: 96px;
      height: 96px;
      border-radius: <?php echo $avatar_radius; ?>;
      <?php if ($is_hexagon): ?>clip-path: <?php echo $avatar_clip; ?>; border: none;<?php else: ?>border: 3px solid var(--blp-accent);<?php endif; ?>
      background: var(--blp-card);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      color: var(--blp-text);
      box-shadow: 0 0 0 4px rgba(255,255,255,0.05), 0 8px 32px rgba(0,0,0,0.3);
    }

    .blp-avatar-ring {
      position: absolute;
      inset: -5px;
      <?php if ($is_hexagon): ?>clip-path: <?php echo $avatar_clip; ?>;<?php else: ?>border-radius: <?php echo $avatar_radius === '50%' ? '50%' : 'calc(' . $avatar_radius . ' + 5px)'; ?>;<?php endif; ?>
      background: conic-gradient(var(--blp-accent), transparent, var(--blp-accent));
      animation: blp-spin 3s linear infinite;
      opacity: 0.6;
      z-index: -1;
    }

    @keyframes blp-spin { to { transform: rotate(360deg); } }

    .blp-name {
      font-size: clamp(22px, 3.6vw, 32px);
      font-weight: 700;
      color: var(--blp-text);
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }

    .blp-bio {
      font-size: clamp(13px, 1.7vw, 15px);
      line-height: 1.65;
      color: var(--blp-text);
      opacity: 0.65;
      max-width: 52ch;
    }

    .blp-header-meta {
      display: flex;
      flex-direction: column;
      align-items: inherit;
      width: 100%;
    }

    /* ── Layout Variants ── */
    .blp-page.blp-layout-profile_left_info_right .blp-container,
    .blp-page.blp-layout-profile_right_info_left .blp-container,
    .blp-page.blp-layout-business_card_horizontal .blp-container,
    .blp-page.blp-layout-magazine_split .blp-container,
    .blp-page.blp-layout-executive_clean .blp-container,
    .blp-page.blp-layout-studio_bento .blp-container,
    .blp-page.blp-layout-bold_banner_top .blp-container,
    .blp-page.blp-layout-avatar_floating_sidebar .blp-container,
    .blp-page.blp-layout-spotlight_onepage .blp-container,
    .blp-page.blp-layout-hero_split_pro .blp-container,
    .blp-page.blp-layout-cover_profile_panel .blp-container,
    .blp-page.blp-layout-creator_spotlight .blp-container,
    .blp-page.blp-layout-minimal_directory .blp-container,
    .blp-page.blp-layout-agency_brief .blp-container,
    .blp-page.blp-layout-press_kit_split .blp-container {
      max-width: 880px;
    }

    .blp-page.blp-layout-profile_left_info_right .blp-header,
    .blp-page.blp-layout-profile_right_info_left .blp-header,
    .blp-page.blp-layout-business_card_horizontal .blp-header,
    .blp-page.blp-layout-magazine_split .blp-header,
    .blp-page.blp-layout-executive_clean .blp-header,
    .blp-page.blp-layout-avatar_floating_sidebar .blp-header,
    .blp-page.blp-layout-minimal_directory .blp-header {
      width: 100%;
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 20px;
      align-items: center;
      text-align: left;
      margin-bottom: 26px;
    }

    .blp-page.blp-layout-profile_left_info_right .blp-avatar-wrap,
    .blp-page.blp-layout-profile_right_info_left .blp-avatar-wrap,
    .blp-page.blp-layout-business_card_horizontal .blp-avatar-wrap,
    .blp-page.blp-layout-magazine_split .blp-avatar-wrap,
    .blp-page.blp-layout-executive_clean .blp-avatar-wrap,
    .blp-page.blp-layout-avatar_floating_sidebar .blp-avatar-wrap,
    .blp-page.blp-layout-minimal_directory .blp-avatar-wrap {
      margin-bottom: 0;
    }

    .blp-page.blp-layout-profile_left_info_right .blp-header-meta,
    .blp-page.blp-layout-profile_right_info_left .blp-header-meta,
    .blp-page.blp-layout-business_card_horizontal .blp-header-meta,
    .blp-page.blp-layout-magazine_split .blp-header-meta,
    .blp-page.blp-layout-executive_clean .blp-header-meta,
    .blp-page.blp-layout-avatar_floating_sidebar .blp-header-meta,
    .blp-page.blp-layout-minimal_directory .blp-header-meta {
      align-items: flex-start;
    }

    .blp-page.blp-layout-profile_left_info_right .blp-social-icons,
    .blp-page.blp-layout-profile_right_info_left .blp-social-icons,
    .blp-page.blp-layout-business_card_horizontal .blp-social-icons,
    .blp-page.blp-layout-magazine_split .blp-social-icons,
    .blp-page.blp-layout-executive_clean .blp-social-icons,
    .blp-page.blp-layout-minimal_directory .blp-social-icons {
      justify-content: flex-start;
    }

    .blp-page.blp-layout-profile_right_info_left .blp-header {
      grid-template-columns: 1fr auto;
    }
    .blp-page.blp-layout-profile_right_info_left .blp-avatar-wrap {
      order: 2;
    }
    .blp-page.blp-layout-profile_right_info_left .blp-header-meta {
      order: 1;
    }

    .blp-page.blp-layout-business_card_horizontal .blp-header,
    .blp-page.blp-layout-executive_clean .blp-header {
      background: var(--blp-card);
      border: 1px solid var(--blp-border);
      border-radius: 24px;
      padding: 20px 22px;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }

    .blp-page.blp-layout-business_card_horizontal .blp-links,
    .blp-page.blp-layout-magazine_split .blp-links,
    .blp-page.blp-layout-studio_bento .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-studio_bento .blp-link-card:first-child {
      grid-column: span 2;
    }

    .blp-page.blp-layout-bold_banner_top .blp-header {
      width: 100%;
      border-radius: 26px;
      padding: 34px 20px 26px;
      background: linear-gradient(135deg, rgba(255,255,255,0.14), rgba(255,255,255,0.03));
      border: 1px solid rgba(255,255,255,0.16);
      box-shadow: 0 16px 44px rgba(0,0,0,0.25);
    }

    .blp-page.blp-layout-bold_banner_top .blp-avatar-wrap {
      margin-bottom: 16px;
    }

    .blp-page.blp-layout-avatar_floating_sidebar .blp-container {
      display: grid;
      grid-template-columns: 280px 1fr;
      align-items: start;
      gap: 18px;
    }

    .blp-page.blp-layout-avatar_floating_sidebar .blp-header {
      position: sticky;
      top: 22px;
      background: var(--blp-card);
      border: 1px solid var(--blp-border);
      border-radius: 24px;
      padding: 16px;
      grid-template-columns: 1fr;
      text-align: center;
    }

    .blp-page.blp-layout-avatar_floating_sidebar .blp-header-meta {
      align-items: center;
    }

    .blp-page.blp-layout-avatar_floating_sidebar .blp-links {
      padding-top: 6px;
    }

    .blp-page.blp-layout-compact_contact_card .blp-container {
      max-width: 420px;
    }

    .blp-page.blp-layout-compact_contact_card .blp-header {
      background: var(--blp-card);
      border: 1px solid var(--blp-border);
      border-radius: 20px;
      padding: 18px;
      margin-bottom: 18px;
    }

    .blp-page.blp-layout-compact_contact_card .blp-link-card {
      padding: 13px 15px;
    }

    .blp-page.blp-layout-spotlight_onepage .blp-avatar,
    .blp-page.blp-layout-spotlight_onepage .blp-avatar-placeholder {
      width: 112px;
      height: 112px;
    }

    .blp-page.blp-layout-spotlight_onepage .blp-name {
      font-size: 30px;
      letter-spacing: -0.03em;
    }

    .blp-page.blp-layout-minimal_stack .blp-link-card {
      backdrop-filter: none;
      -webkit-backdrop-filter: none;
      box-shadow: none;
    }

    .blp-page.blp-layout-executive_clean .blp-link-card {
      border-width: 1.5px;
      box-shadow: 0 6px 22px rgba(0,0,0,0.22);
    }

    .blp-page.blp-layout-hero_split_pro .blp-container {
      max-width: 980px;
      display: grid;
      grid-template-columns: minmax(280px, 340px) 1fr;
      align-items: start;
      gap: 20px;
    }

    .blp-page.blp-layout-hero_split_pro .blp-header {
      position: sticky;
      top: 20px;
      margin-bottom: 0;
      border-radius: 24px;
      border: 1px solid var(--blp-border);
      background: var(--blp-card);
      padding: 22px 20px;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }

    .blp-page.blp-layout-hero_split_pro .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-stacked_cards_modern .blp-container {
      max-width: 640px;
    }

    .blp-page.blp-layout-stacked_cards_modern .blp-link-card {
      border-radius: 18px;
      border-color: rgba(255,255,255,0.18);
      padding: 18px 20px;
      box-shadow: 0 14px 36px rgba(0,0,0,0.22);
    }

    .blp-page.blp-layout-stacked_cards_modern .blp-link-card:nth-child(even) {
      margin-inline-start: 14px;
    }

    .blp-page.blp-layout-cover_profile_panel .blp-container {
      max-width: 900px;
    }

    .blp-page.blp-layout-cover_profile_panel .blp-header {
      width: 100%;
      padding: 28px 24px 22px;
      border-radius: 30px;
      border: 1px solid rgba(255,255,255,0.18);
      background: linear-gradient(140deg, rgba(255,255,255,0.18), rgba(255,255,255,0.03));
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      box-shadow: 0 20px 42px rgba(0,0,0,0.24);
      margin-bottom: 20px;
    }

    .blp-page.blp-layout-cover_profile_panel .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-creator_spotlight .blp-container {
      max-width: 740px;
    }

    .blp-page.blp-layout-creator_spotlight .blp-name {
      font-size: clamp(30px, 5vw, 44px);
      letter-spacing: -0.04em;
      margin-bottom: 10px;
    }

    .blp-page.blp-layout-creator_spotlight .blp-header {
      margin-bottom: 16px;
    }

    .blp-page.blp-layout-creator_spotlight .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-minimal_directory .blp-container {
      max-width: 740px;
    }

    .blp-page.blp-layout-minimal_directory .blp-link-card {
      padding: 14px 16px;
      border-radius: 12px;
      backdrop-filter: none;
      -webkit-backdrop-filter: none;
      box-shadow: none;
    }

    .blp-page.blp-layout-minimal_directory .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .blp-page.blp-layout-agency_brief .blp-container {
      max-width: 980px;
    }

    .blp-page.blp-layout-timeline_story .blp-container {
      max-width: 760px;
    }

    .blp-page.blp-layout-timeline_story .blp-links {
      position: relative;
      padding-left: 18px;
      gap: 14px;
    }

    .blp-page.blp-layout-timeline_story .blp-links::before {
      content: '';
      position: absolute;
      left: 8px;
      top: 8px;
      bottom: 8px;
      width: 2px;
      background: linear-gradient(180deg, rgba(255,255,255,0.45), rgba(255,255,255,0.08));
      border-radius: 999px;
    }

    .blp-page.blp-layout-timeline_story .blp-link-card {
      margin-left: 14px;
      border-radius: 16px;
    }

    .blp-page.blp-layout-timeline_story .blp-link-card::after {
      content: '';
      position: absolute;
      left: -17px;
      top: 50%;
      width: 10px;
      height: 10px;
      transform: translateY(-50%);
      border-radius: 50%;
      background: var(--blp-accent);
      box-shadow: 0 0 0 4px rgba(255,255,255,0.09);
    }

    .blp-page.blp-layout-split_hero_cards .blp-container {
      max-width: 980px;
    }

    .blp-page.blp-layout-split_hero_cards .blp-header {
      width: 100%;
      display: grid;
      grid-template-columns: auto 1fr;
      align-items: center;
      gap: 20px;
      text-align: left;
      border-radius: 24px;
      border: 1px solid var(--blp-border);
      background: var(--blp-card);
      padding: 22px;
      margin-bottom: 18px;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
    }

    .blp-page.blp-layout-split_hero_cards .blp-header-meta {
      align-items: flex-start;
    }

    .blp-page.blp-layout-split_hero_cards .blp-social-icons {
      justify-content: flex-start;
    }

    .blp-page.blp-layout-split_hero_cards .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-split_hero_cards .blp-link-card:first-child {
      grid-column: 1 / -1;
      padding: 20px 22px;
    }

    .blp-page.blp-layout-mosaic_showcase .blp-container {
      max-width: 940px;
    }

    .blp-page.blp-layout-mosaic_showcase .blp-links {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-mosaic_showcase .blp-link-card {
      grid-column: span 3;
      min-height: 86px;
    }

    .blp-page.blp-layout-mosaic_showcase .blp-link-card:nth-child(4n + 1) {
      grid-column: span 4;
    }

    .blp-page.blp-layout-mosaic_showcase .blp-link-card:nth-child(4n + 2) {
      grid-column: span 2;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-container {
      max-width: 1020px;
      display: grid;
      grid-template-columns: minmax(260px, 320px) 1fr;
      align-items: start;
      gap: 20px;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-header {
      position: sticky;
      top: 20px;
      margin-bottom: 0;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
      gap: 14px;
      border-radius: 24px;
      border: 1px solid var(--blp-border);
      background: var(--blp-card);
      padding: 22px;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-avatar-wrap {
      margin-bottom: 0;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-header-meta {
      align-items: flex-start;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-social-icons {
      justify-content: flex-start;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-executive_sidebar_pro .blp-link-card:first-child {
      grid-column: 1 / -1;
    }

    .blp-page.blp-layout-press_kit_split .blp-container {
      max-width: 980px;
    }

    .blp-page.blp-layout-press_kit_split .blp-header {
      width: 100%;
      display: grid;
      grid-template-columns: auto 1fr;
      align-items: center;
      gap: 22px;
      text-align: left;
      border-radius: 24px;
      border: 1px solid var(--blp-border);
      background: linear-gradient(140deg, rgba(255,255,255,0.16), rgba(255,255,255,0.04));
      padding: 22px;
      margin-bottom: 18px;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }

    .blp-page.blp-layout-press_kit_split .blp-avatar-wrap {
      margin-bottom: 0;
    }

    .blp-page.blp-layout-press_kit_split .blp-header-meta {
      align-items: flex-start;
    }

    .blp-page.blp-layout-press_kit_split .blp-social-icons {
      justify-content: flex-start;
    }

    .blp-page.blp-layout-press_kit_split .blp-links {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-page.blp-layout-press_kit_split .blp-link-card:first-child {
      grid-column: 1 / -1;
      padding: 20px 22px;
    }

    .blp-page.blp-layout-minimal_premium_stack .blp-container {
      max-width: 620px;
    }

    .blp-page.blp-layout-minimal_premium_stack .blp-header {
      margin-bottom: 18px;
    }

    .blp-page.blp-layout-minimal_premium_stack .blp-link-card {
      border-radius: 14px;
      border-color: rgba(255,255,255,0.16);
      backdrop-filter: none;
      -webkit-backdrop-filter: none;
      box-shadow: none;
      padding: 15px 17px;
    }

    .blp-page.blp-layout-minimal_premium_stack .blp-link-card:hover {
      border-color: rgba(255,255,255,0.32);
      box-shadow: 0 10px 24px rgba(0,0,0,0.2);
    }

    .blp-page.blp-layout-agency_brief .blp-header {
      width: 100%;
      display: grid;
      grid-template-columns: auto 1fr;
      align-items: center;
      gap: 22px;
      text-align: left;
      border-radius: 24px;
      border: 1px solid var(--blp-border);
      background: var(--blp-card);
      padding: 22px;
      margin-bottom: 20px;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
    }

    .blp-page.blp-layout-agency_brief .blp-header-meta {
      align-items: flex-start;
    }

    .blp-page.blp-layout-agency_brief .blp-social-icons {
      justify-content: flex-start;
    }

    .blp-page.blp-layout-agency_brief .blp-links {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
    }

    .blp-link-featured {
      border-color: rgba(255,255,255,0.32);
      box-shadow: 0 16px 34px rgba(0,0,0,0.28), 0 0 0 1px rgba(255,255,255,0.09);
      background: linear-gradient(140deg, rgba(255,255,255,0.14), rgba(255,255,255,0.04));
    }

    .blp-page.blp-layout-creator_spotlight .blp-link-featured,
    .blp-page.blp-layout-hero_split_pro .blp-link-featured,
    .blp-page.blp-layout-cover_profile_panel .blp-link-featured,
    .blp-page.blp-layout-agency_brief .blp-link-featured {
      grid-column: 1 / -1;
      padding: 20px 22px;
    }

    /* ── Links ── */
    .blp-links {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: clamp(10px, 1.8vw, 14px);
    }

    .blp-link-card {
      display: flex;
      align-items: center;
      gap: 14px;
      width: 100%;
      padding: clamp(14px, 1.8vw, 18px) clamp(16px, 2.1vw, 22px);
      background: var(--blp-card);
      backdrop-filter: blur(<?php echo $card_blur > 0 ? $card_blur : 20; ?>px);
      -webkit-backdrop-filter: blur(<?php echo $card_blur > 0 ? $card_blur : 20; ?>px);
      border: 1px solid var(--blp-border);
      border-radius: var(--blp-btn-radius);
      color: var(--blp-text);
      text-decoration: none;
      font-family: var(--blp-font);
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
      animation: blp-enter var(--delay, 0.3s) cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .blp-empty-state {
      width: 100%;
      max-width: 500px;
      padding: clamp(20px, 4vw, 28px);
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.14);
      background: linear-gradient(150deg, rgba(255,255,255,0.1), rgba(255,255,255,0.03));
      text-align: center;
      color: var(--blp-text);
      opacity: 0.72;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      margin-top: 4px;
    }

    .blp-empty-state-title {
      font-size: 17px;
      font-weight: 700;
      margin-bottom: 8px;
      opacity: 0.92;
    }

    .blp-empty-state-sub {
      font-size: 13px;
      line-height: 1.6;
      opacity: 0.72;
    }

    /* Shimmer effect */
    .blp-link-card::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 60%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
      transition: left 0.5s;
    }

    /* Button style variants */
    <?php if ($theme['button_style'] === 'outline'): ?>
    .blp-link-card { background: transparent; border: 2px solid rgba(255,255,255,0.25); }
    <?php elseif ($theme['button_style'] === 'glass'): ?>
    .blp-link-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); }
    <?php elseif ($theme['button_style'] === 'solid'): ?>
    .blp-link-card { background: var(--blp-accent); border-color: transparent; }
    <?php endif; ?>

    /* Hover effects */
    <?php if ($theme['button_effect'] === 'lift'): ?>
    .blp-link-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.1); }
    <?php elseif ($theme['button_effect'] === 'glow'): ?>
    .blp-link-card:hover { border-color: var(--blp-accent); box-shadow: 0 0 0 1px var(--blp-accent), 0 0 24px rgba(124,109,240,0.3); }
    <?php elseif ($theme['button_effect'] === 'slide'): ?>
    .blp-link-card:hover::before { left: 100%; }
    .blp-link-card:hover { background: var(--blp-accent); border-color: transparent; }
    <?php elseif ($theme['button_effect'] === 'bounce'): ?>
    .blp-link-card:hover { animation: blp-bounce 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
    @keyframes blp-bounce { 0%,100%{transform:translateY(0)} 30%{transform:translateY(-5px)} 60%{transform:translateY(-2px)} }
    <?php elseif ($theme['button_effect'] === 'pulse'): ?>
    .blp-link-card:hover { animation: blp-pulse 1s ease infinite; }
    @keyframes blp-pulse { 0%,100%{box-shadow:0 0 0 0 var(--blp-accent)} 50%{box-shadow:0 0 0 8px transparent} }
    <?php elseif ($theme['button_effect'] === 'tilt'): ?>
    .blp-link-card { transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s; }
    .blp-link-card:hover { transform: perspective(600px) rotateY(-2deg) translateY(-2px); box-shadow: 4px 8px 24px rgba(0,0,0,0.25); }
    <?php else: ?>
    .blp-link-card:hover { border-color: rgba(255,255,255,0.2); }
    <?php endif; ?>

    .blp-link-card:active { transform: scale(0.98); }

    .blp-link-icon-wrap {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(255,255,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .blp-link-text { flex: 1; min-width: 0; }
    .blp-link-title { font-weight: 600; line-height: 1.3; }
    .blp-link-subtitle { font-size: 12px; opacity: 0.5; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .blp-link-badge-wrap {
      flex-shrink: 0;
    }
    .blp-link-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 20px;
      letter-spacing: 0.06em;
      color: #fff;
      text-transform: uppercase;
    }

    .blp-link-arrow {
      flex-shrink: 0;
      opacity: 0.3;
      transition: opacity 0.2s, transform 0.2s;
    }
    .blp-link-card:hover .blp-link-arrow { opacity: 0.8; transform: translateX(3px); }

    /* ── Footer ── */
    .blp-footer {
      margin-top: 40px;
      font-size: 12px;
      opacity: 0.35;
      color: var(--blp-text);
      animation: blp-enter 1s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    .blp-footer a { color: inherit; text-decoration: none; }
    .blp-footer a:hover { opacity: 1; }

    /* ── Payment Card ── */
    .blp-link-pay {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%) !important;
      color: #fff !important;
      border: 1px solid rgba(255,255,255,0.1) !important;
      position: relative;
      overflow: hidden;
    }
    .blp-link-pay::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 60%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
      animation: blp-pay-shimmer 3s ease-in-out infinite;
    }
    .blp-link-pay .blp-link-title { color: #fff; font-weight: 600; }
    .blp-link-pay .blp-link-subtitle { color: rgba(255,255,255,0.5); }
    .blp-link-pay .blp-link-arrow { color: rgba(255,255,255,0.6); }
    .blp-link-pay .blp-link-icon-wrap { color: #fff; }
    @keyframes blp-pay-shimmer {
      0% { left: -100%; }
      50% { left: 150%; }
      100% { left: 150%; }
    }

    /* ── Animations ── */
    @keyframes blp-enter {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Responsive ── */
    @media (min-width: 1024px) {
      .blp-container { max-width: 580px; }
      .blp-link-card { padding: 18px 24px; font-size: 16px; }
      .blp-name { font-size: 28px; }
    }
    @media (max-width: 1120px) {
      #blp-page.blp-page.blp-layout-hero_split_pro .blp-container {
        display: flex;
        max-width: 760px;
      }

      #blp-page.blp-page.blp-layout-hero_split_pro .blp-header {
        position: relative;
        top: auto;
      }

      #blp-page.blp-page.blp-layout-agency_brief .blp-links {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      #blp-page.blp-page.blp-layout-executive_sidebar_pro .blp-container {
        display: flex;
        max-width: 760px;
      }

      #blp-page.blp-page.blp-layout-executive_sidebar_pro .blp-header {
        position: relative;
        top: auto;
      }

      #blp-page.blp-page.blp-layout-mosaic_showcase .blp-links {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }
    }

    @media (max-width: 860px) {
      #blp-page.blp-page[class*="blp-layout-"] .blp-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 14px;
      }

      #blp-page.blp-page[class*="blp-layout-"] .blp-header-meta {
        align-items: center;
      }

      #blp-page.blp-page[class*="blp-layout-"] .blp-links {
        display: flex;
        flex-direction: column;
      }

      #blp-page.blp-page.blp-layout-avatar_floating_sidebar .blp-container {
        display: flex;
        flex-direction: column;
      }

      #blp-page.blp-page.blp-layout-avatar_floating_sidebar .blp-header {
        position: relative;
        top: auto;
      }

      #blp-page.blp-page.blp-layout-stacked_cards_modern .blp-link-card:nth-child(even) {
        margin-inline-start: 0;
      }

      #blp-page.blp-page.blp-layout-agency_brief .blp-header,
      #blp-page.blp-page.blp-layout-hero_split_pro .blp-header,
      #blp-page.blp-page.blp-layout-split_hero_cards .blp-header,
      #blp-page.blp-page.blp-layout-press_kit_split .blp-header,
      #blp-page.blp-page.blp-layout-executive_sidebar_pro .blp-header {
        width: 100%;
      }

      #blp-page.blp-page.blp-layout-timeline_story .blp-links::before {
        display: none;
      }

      #blp-page.blp-page.blp-layout-timeline_story .blp-link-card {
        margin-left: 0;
      }

      #blp-page.blp-page.blp-layout-timeline_story .blp-link-card::after {
        display: none;
      }
    }

    @media (max-width: 768px) {
      .blp-page { padding: 40px 18px 70px; }
      .blp-container { max-width: 100%; }
      .blp-name { font-size: 22px; }
      .blp-bio { font-size: 13px; }
    }
    @media (max-width: 480px) {
      .blp-page { padding: 32px 14px 56px; }
      .blp-name { font-size: 19px; }
      .blp-avatar, .blp-avatar-placeholder { width: 76px; height: 76px; font-size: 30px; }
      .blp-link-card { padding: 14px 16px; gap: 10px; font-size: 14px; }
      .blp-link-icon-wrap { width: 34px; height: 34px; font-size: 15px; border-radius: 8px; }
      .blp-bio { max-width: 100%; }

      .blp-empty-state {
        padding: 18px 14px;
        border-radius: 16px;
      }

      .blp-empty-state-title {
        font-size: 15px;
      }

      .blp-empty-state-sub {
        font-size: 12px;
      }
    }

    <?php if (filter_var($theme['enable_darklight'], FILTER_VALIDATE_BOOLEAN)): ?>
    /* === Light Mode Variables === */
    .blp-page.blp-light-mode {
      --blp-light-bg: <?php echo blp_safe_color($theme['light_bg_color'], '#f5f5f5'); ?>;
      --blp-light-text: <?php echo blp_safe_color($theme['light_text_color'], '#1a1a1a'); ?>;
      --blp-light-card: <?php echo blp_safe_color($theme['light_card_color'], '#ffffff'); ?>;
      background: var(--blp-light-bg) !important;
      color: var(--blp-light-text);
    }
    <?php endif; ?>

    <?php if (!empty($theme['custom_css'])): ?>
    /* === Custom CSS === */
    <?php echo $theme['custom_css']; // Already sanitized on save in class-blp-ajax.php ?>
    <?php endif; ?>
  </style>
</head>
<body>
<div class="blp-page <?php echo esc_attr($layout_class); ?>" id="blp-page" data-layout="<?php echo esc_attr($layout_variant); ?>"
  <?php if (filter_var($theme['enable_darklight'], FILTER_VALIDATE_BOOLEAN)): ?>
  data-light-bg="<?php echo esc_attr($theme['light_bg_color']); ?>"
  data-light-text="<?php echo esc_attr($theme['light_text_color']); ?>"
  data-light-card="<?php echo esc_attr($theme['light_card_color']); ?>"
  <?php endif; ?>
>

  <?php if (filter_var($theme['enable_particles'], FILTER_VALIDATE_BOOLEAN)): ?>
  <canvas id="blp-particles"></canvas>
  <?php endif; ?>

  <?php
  // Video background
  $video_embed_id = '';
  $video_type = '';
  if (!empty($theme['bg_video_url'])) {
      if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $theme['bg_video_url'], $yt)) {
          $video_embed_id = $yt[1];
          $video_type = 'youtube';
      } elseif (preg_match('/vimeo\.com\/(\d+)/', $theme['bg_video_url'], $vm)) {
          $video_embed_id = $vm[1];
          $video_type = 'vimeo';
      }
  }
  if ($video_embed_id): ?>
  <div class="blp-video-bg">
    <?php if ($video_type === 'youtube'): ?>
    <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_embed_id); ?>?autoplay=1&mute=1&loop=1&controls=0&showinfo=0&playlist=<?php echo esc_attr($video_embed_id); ?>&playsinline=1" frameborder="0" allow="autoplay; fullscreen" loading="lazy"></iframe>
    <?php else: ?>
    <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($video_embed_id); ?>?autoplay=1&muted=1&loop=1&background=1" frameborder="0" allow="autoplay; fullscreen" loading="lazy"></iframe>
    <?php endif; ?>
    <div class="blp-video-overlay"></div>
  </div>
  <?php endif; ?>

  <div class="blp-container">

    <?php if (!empty($theme['banner_url'])): ?>
    <!-- ── Banner / Cover ── -->
    <div class="blp-banner" style="height:<?php echo (int)$theme['banner_height']; ?>px; background-image:url('<?php echo esc_url($theme['banner_url']); ?>'); background-size:cover; background-position:center; border-radius:var(--blp-btn-radius); margin-bottom:20px; overflow:hidden;"></div>
    <?php endif; ?>

    <!-- ── Profile Header ── -->
    <div class="blp-header">
      <div class="blp-avatar-wrap">
        <div class="blp-avatar-ring"></div>
        <?php if (!empty($profile->avatar_url)): ?>
          <img class="blp-avatar" src="<?php echo esc_url($profile->avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
        <?php else: ?>
          <div class="blp-avatar-placeholder">
            <?php echo esc_html(mb_substr($display_name, 0, 1)); ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="blp-header-meta">
        <h1 class="blp-name"><?php echo esc_html($display_name); ?></h1>
        <?php if (!empty($profile->bio)): ?>
          <p class="blp-bio"><?php echo nl2br(esc_html($profile->bio)); ?></p>
        <?php endif; ?>

      <?php
      // Social icons
      if ($show_social_icons && !empty($social_links)):
        $social_svgs = [
          'instagram'  => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
          'tiktok'     => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
          'youtube'    => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
          'twitter'    => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
          'linkedin'   => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
          'spotify'    => 'M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z',
          'discord'    => 'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189z',
          'twitch'     => 'M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714z',
          'github'     => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
          'pinterest'  => 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641 0 12.017 0z',
          'snapchat'   => 'M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.21-.061.524.12.869l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.008c-.18 0-.39-.044-.63-.104a5.478 5.478 0 00-1.47-.207c-.36 0-.72.044-1.048.164-.81.314-1.455.899-2.22 1.605-.96.885-2.07 1.891-3.766 1.891-.045 0-.09 0-.135-.004h-.105c-1.695 0-2.805-1.005-3.766-1.891-.764-.706-1.41-1.291-2.22-1.605a3.68 3.68 0 00-1.048-.164c-.57 0-1.095.12-1.47.207-.24.06-.45.104-.63.104h-.006c-.285 0-.48-.135-.555-.405a4.1 4.1 0 01-.134-.553c-.045-.195-.105-.479-.165-.57-1.872-.283-2.905-.702-3.146-1.271a.504.504 0 01-.044-.225c-.015-.24.165-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.21-.659.119-.869-.195-.434-.884-.659-1.332-.809a3.72 3.72 0 01-.346-.12c-.6-.239-1.166-.585-1.166-1.05 0-.3.18-.599.476-.748a1.2 1.2 0 01.51-.105c.135 0 .33.03.494.104.374.18.72.3 1.033.3.21 0 .346-.045.406-.089a27.14 27.14 0 01-.033-.57c-.104-1.628-.239-3.654.3-4.848C7.847 1.069 11.216.793 12.206.793',
          'telegram'   => 'M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z',
          'whatsapp'   => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
          'facebook'   => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
          'bereal'     => 'M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 3a7 7 0 110 14 7 7 0 010-14zm0 2a5 5 0 100 10 5 5 0 000-10z',
        ];
      ?>
      <div class="blp-social-icons">
        <?php foreach ($social_links as $platform => $url):
          $safe_platform = sanitize_key($platform);
          $safe_url = esc_url($url);
          if (empty($safe_url) || !isset($social_svgs[$safe_platform])) continue;
        ?>
        <a class="blp-social-icon" href="<?php echo $safe_url; ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr(ucfirst($safe_platform)); ?>">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="<?php echo esc_attr($social_svgs[$safe_platform]); ?>"/></svg>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($theme['status_text'])): ?>
      <!-- Status Indicator -->
      <div class="blp-status-indicator">
        <?php if (!empty($theme['status_emoji'])): ?><span class="blp-status-emoji"><?php echo esc_html($theme['status_emoji']); ?></span><?php endif; ?>
        <span class="blp-status-text"><?php echo esc_html($theme['status_text']); ?></span>
      </div>
      <?php endif; ?>

      </div>
    </div>

    <?php if (!empty($theme['countdown_date'])): ?>
    <!-- ── Countdown Timer ── -->
    <div class="blp-countdown-wrap" id="blp-countdown" data-target="<?php echo esc_attr($theme['countdown_date']); ?>">
      <?php if (!empty($theme['countdown_label'])): ?>
        <div class="blp-countdown-label"><?php echo esc_html($theme['countdown_label']); ?></div>
      <?php endif; ?>
      <div class="blp-countdown-timer">
        <div class="blp-countdown-unit"><span id="blp-cd-days">--</span><small>Days</small></div>
        <div class="blp-countdown-sep">:</div>
        <div class="blp-countdown-unit"><span id="blp-cd-hours">--</span><small>Hours</small></div>
        <div class="blp-countdown-sep">:</div>
        <div class="blp-countdown-unit"><span id="blp-cd-mins">--</span><small>Min</small></div>
        <div class="blp-countdown-sep">:</div>
        <div class="blp-countdown-unit"><span id="blp-cd-secs">--</span><small>Sec</small></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Links / Blocks ── -->
    <div class="blp-links">
      <?php foreach ($visible_links as $i => $link):
        $delay = 0.2 + ($i * 0.07);
        $link_type = $link->link_type ?? 'link';
        $icon  = !empty($link->icon) ? $link->icon : '🔗';
        $meta  = !empty($link->metadata) ? json_decode($link->metadata, true) : [];
        if (!is_array($meta)) $meta = [];

        // A/B testing: randomly pick variant B title if set
        $ab_variant = 'a';
        $display_title = $link->title;
        if (!empty($link->title_b)) {
            $ab_variant = mt_rand(0, 1) ? 'b' : 'a';
            if ($ab_variant === 'b') {
                $display_title = $link->title_b;
            }
        }
      ?>

      <?php if ($link_type === 'divider'): ?>
        <!-- Divider -->
        <div class="blp-block-divider" style="--delay: <?php echo $delay; ?>s">
          <hr>
        </div>

      <?php elseif ($link_type === 'header'): ?>
        <!-- Header -->
        <div class="blp-block-header" style="--delay: <?php echo $delay; ?>s">
          <h3 class="blp-block-header-title"><?php echo esc_html($display_title); ?></h3>
          <?php if (!empty($link->subtitle)): ?>
            <p class="blp-block-header-subtitle"><?php echo esc_html($link->subtitle); ?></p>
          <?php endif; ?>
        </div>

      <?php elseif ($link_type === 'text'): ?>
        <!-- Text Block -->
        <div class="blp-block-text" style="--delay: <?php echo $delay; ?>s">
          <?php if (!empty($link->title)): ?>
            <h4 class="blp-block-text-title"><?php echo esc_html($display_title); ?></h4>
          <?php endif; ?>
          <?php if (!empty($meta['content'])): ?>
            <p class="blp-block-text-content"><?php echo nl2br(esc_html($meta['content'])); ?></p>
          <?php endif; ?>
        </div>

      <?php elseif ($link_type === 'accordion'): ?>
        <!-- Accordion / FAQ -->
        <details class="blp-block-accordion" style="--delay: <?php echo $delay; ?>s">
          <summary class="blp-block-accordion-title">
            <?php if (!empty($link->icon)): ?>
              <span class="blp-link-icon-wrap"><?php echo esc_html($link->icon); ?></span>
            <?php endif; ?>
            <?php echo esc_html($display_title); ?>
            <svg class="blp-accordion-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="blp-block-accordion-content">
            <?php echo nl2br(esc_html($meta['content'] ?? '')); ?>
          </div>
        </details>

      <?php elseif ($link_type === 'embed'): ?>
        <!-- Embed Block -->
        <?php
          $embed_url = esc_url($link->url);
          $embed_html = '';
          // YouTube
          if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/', $link->url, $m)) {
            $embed_html = '<iframe src="https://www.youtube.com/embed/' . esc_attr($m[1]) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
          }
          // Spotify
          elseif (preg_match('/open\.spotify\.com\/(track|album|playlist|episode)\/([\w]+)/', $link->url, $m)) {
            $embed_html = '<iframe src="https://open.spotify.com/embed/' . esc_attr($m[1]) . '/' . esc_attr($m[2]) . '" frameborder="0" allow="encrypted-media" loading="lazy"></iframe>';
          }
          // Vimeo
          elseif (preg_match('/vimeo\.com\/(\d+)/', $link->url, $m)) {
            $embed_html = '<iframe src="https://player.vimeo.com/video/' . esc_attr($m[1]) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
          }
          // SoundCloud
          elseif (strpos($link->url, 'soundcloud.com') !== false) {
            $embed_html = '<iframe src="https://w.soundcloud.com/player/?url=' . rawurlencode($link->url) . '&color=%23' . ltrim($theme['accent_color'] ?? '7c6df0', '#') . '&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false" frameborder="0" allow="autoplay" loading="lazy"></iframe>';
          }
        ?>
        <div class="blp-block-embed" style="--delay: <?php echo $delay; ?>s"
             data-link-id="<?php echo (int)$link->id; ?>"
             data-profile-id="<?php echo (int)$profile->id; ?>">
          <?php if (!empty($link->title)): ?>
            <h4 class="blp-block-embed-title"><?php echo esc_html($display_title); ?></h4>
          <?php endif; ?>
          <?php if ($embed_html): ?>
            <div class="blp-embed-wrap"><?php echo $embed_html; ?></div>
          <?php else: ?>
            <a class="blp-link-card" href="<?php echo $embed_url; ?>" target="_blank" rel="noopener noreferrer">
              <div class="blp-link-icon-wrap"><?php echo esc_html($icon); ?></div>
              <div class="blp-link-text"><div class="blp-link-title"><?php echo esc_html($display_title); ?></div></div>
              <svg class="blp-link-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
          <?php endif; ?>
        </div>

      <?php elseif ($link_type === 'vcard'): ?>
        <!-- vCard / Contact Card -->
        <div class="blp-block-vcard" style="--delay: <?php echo $delay; ?>s">
          <div class="blp-vcard-header">
            <span class="blp-link-icon-wrap">📇</span>
            <h4 class="blp-vcard-title"><?php echo esc_html($display_title); ?></h4>
          </div>
          <div class="blp-vcard-details">
            <?php if (!empty($meta['full_name'])): ?><div class="blp-vcard-row">👤 <?php echo esc_html($meta['full_name']); ?></div><?php endif; ?>
            <?php if (!empty($meta['email'])): ?><div class="blp-vcard-row">✉️ <?php echo esc_html($meta['email']); ?></div><?php endif; ?>
            <?php if (!empty($meta['phone'])): ?><div class="blp-vcard-row">📱 <?php echo esc_html($meta['phone']); ?></div><?php endif; ?>
            <?php if (!empty($meta['company'])): ?><div class="blp-vcard-row">🏢 <?php echo esc_html($meta['company']); ?><?php if (!empty($meta['job_title'])): ?> — <?php echo esc_html($meta['job_title']); ?><?php endif; ?></div><?php endif; ?>
            <?php if (!empty($meta['address'])): ?><div class="blp-vcard-row">📍 <?php echo esc_html($meta['address']); ?></div><?php endif; ?>
          </div>
          <?php
            // Build vCard data URI for download
            $vcard_parts = ["BEGIN:VCARD", "VERSION:3.0"];
            if (!empty($meta['full_name'])) $vcard_parts[] = "FN:" . $meta['full_name'];
            if (!empty($meta['email']))     $vcard_parts[] = "EMAIL:" . $meta['email'];
            if (!empty($meta['phone']))     $vcard_parts[] = "TEL:" . $meta['phone'];
            if (!empty($meta['company']))   $vcard_parts[] = "ORG:" . $meta['company'];
            if (!empty($meta['job_title'])) $vcard_parts[] = "TITLE:" . $meta['job_title'];
            if (!empty($meta['address']))   $vcard_parts[] = "ADR;TYPE=WORK:;;" . $meta['address'];
            if (!empty($meta['website']))   $vcard_parts[] = "URL:" . $meta['website'];
            $vcard_parts[] = "END:VCARD";
            $vcard_data = implode("\n", $vcard_parts);
          ?>
          <a class="blp-vcard-download" href="data:text/vcard;charset=utf-8,<?php echo rawurlencode($vcard_data); ?>" download="<?php echo esc_attr($meta['full_name'] ?? 'contact'); ?>.vcf">
            📥 Save to Contacts
          </a>
        </div>

      <?php elseif ($link_type === 'testimonial'): ?>
        <!-- Testimonial -->
        <div class="blp-block-testimonial" style="--delay: <?php echo $delay; ?>s">
          <?php if (!empty($meta['rating'])): ?>
            <div class="blp-testimonial-stars">
              <?php for ($s = 0; $s < (int)$meta['rating']; $s++): ?>⭐<?php endfor; ?>
            </div>
          <?php endif; ?>
          <blockquote class="blp-testimonial-text">"<?php echo esc_html($meta['content'] ?? ''); ?>"</blockquote>
          <div class="blp-testimonial-author">
            <?php if (!empty($meta['avatar_url'])): ?>
              <img class="blp-testimonial-avatar" src="<?php echo esc_url($meta['avatar_url']); ?>" alt="" width="32" height="32" loading="lazy">
            <?php endif; ?>
            <div>
              <?php if (!empty($meta['author'])): ?><strong><?php echo esc_html($meta['author']); ?></strong><?php endif; ?>
              <?php if (!empty($meta['company'])): ?><span class="blp-testimonial-company"><?php echo esc_html($meta['company']); ?></span><?php endif; ?>
            </div>
          </div>
        </div>

      <?php elseif ($link_type === 'gallery'): ?>
        <!-- Gallery / Carousel -->
        <?php $images = !empty($meta['images']) && is_array($meta['images']) ? $meta['images'] : []; ?>
        <?php if ($images): ?>
        <div class="blp-block-gallery" style="--delay: <?php echo $delay; ?>s">
          <?php if ($display_title): ?>
          <h4 class="blp-gallery-title"><?php echo esc_html($display_title); ?></h4>
          <?php endif; ?>
          <div class="blp-gallery-track" data-gallery="<?php echo (int)$link->id; ?>">
            <?php foreach ($images as $img_url): ?>
            <img class="blp-gallery-img" src="<?php echo esc_url($img_url); ?>" alt="" loading="lazy">
            <?php endforeach; ?>
          </div>
          <?php if (count($images) > 1): ?>
          <div class="blp-gallery-nav">
            <button class="blp-gallery-prev" onclick="blpGalleryNav(<?php echo (int)$link->id; ?>,-1)" aria-label="Previous">&lsaquo;</button>
            <button class="blp-gallery-next" onclick="blpGalleryNav(<?php echo (int)$link->id; ?>,1)" aria-label="Next">&rsaquo;</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- Standard Link Card -->
        <?php
          $host  = wp_parse_url($link->url, PHP_URL_HOST) ?? $link->url;
          $is_pay = ($icon === '💳') || (stripos($link->badge ?? '', 'pay') !== false);
          $is_featured_layout = in_array($layout_variant, ['creator_spotlight', 'hero_split_pro', 'cover_profile_panel', 'agency_brief', 'split_hero_cards', 'mosaic_showcase', 'executive_sidebar_pro', 'press_kit_split'], true);
          $is_featured = (isset($link->is_featured) && (int)$link->is_featured === 1) || ($i === 0 && $is_featured_layout);
          $card_class = 'blp-link-card' . ($is_pay ? ' blp-link-pay' : '') . ($is_featured ? ' blp-link-featured' : '');
          // Build URL with UTM params if set
          $link_url = $link->url;
          $utm_parts = [];
          if (!empty($meta['utm_source']))   $utm_parts['utm_source']   = $meta['utm_source'];
          if (!empty($meta['utm_medium']))   $utm_parts['utm_medium']   = $meta['utm_medium'];
          if (!empty($meta['utm_campaign'])) $utm_parts['utm_campaign'] = $meta['utm_campaign'];
          if ($utm_parts) {
              $sep = (strpos($link_url, '?') !== false) ? '&' : '?';
              $link_url .= $sep . http_build_query($utm_parts);
          }
        ?>
        <a class="<?php echo esc_attr($card_class); ?>"
           href="<?php echo esc_url($link_url); ?>"
           target="_blank"
           rel="noopener noreferrer"
           data-link-id="<?php echo (int)$link->id; ?>"
           data-profile-id="<?php echo (int)$profile->id; ?>"
           data-ab="<?php echo esc_attr($ab_variant); ?>"
           style="--delay: <?php echo $delay; ?>s"
           onclick="blpTrackClick(event, this)">

          <?php if (!empty($link->thumbnail_url)): ?>
            <img class="blp-link-thumb" src="<?php echo esc_url($link->thumbnail_url); ?>" alt="" width="40" height="40" loading="lazy">
          <?php else: ?>
          <div class="blp-link-icon-wrap">
            <?php
            $link_host = wp_parse_url($link->url, PHP_URL_HOST);
            if ($link_host && $icon === '🔗'):
            ?>
              <img class="blp-link-favicon" src="https://www.google.com/s2/favicons?domain=<?php echo esc_attr($link_host); ?>&sz=32" alt="" width="20" height="20" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <span style="display:none"><?php echo esc_html($icon); ?></span>
            <?php else: ?>
              <?php echo esc_html($icon); ?>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="blp-link-text">
            <div class="blp-link-title"><?php echo esc_html($display_title); ?></div>
            <div class="blp-link-subtitle"><?php echo esc_html(!empty($link->subtitle) ? $link->subtitle : $host); ?></div>
          </div>

          <?php if (!empty($link->badge)): ?>
          <div class="blp-link-badge-wrap">
            <span class="blp-link-badge" style="background:<?php echo esc_attr($link->badge_color); ?>">
              <?php echo esc_html($link->badge); ?>
            </span>
          </div>
          <?php endif; ?>

          <svg class="blp-link-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </a>
      <?php endif; ?>

      <?php endforeach; ?>

    </div>

    <?php
    // Music player widget
    $music_embed = '';
    if (!empty($theme['music_url'])) {
        if (strpos($theme['music_url'], 'spotify.com') !== false) {
            $spotify_uri = str_replace(['https://open.spotify.com/', 'http://open.spotify.com/'], '', $theme['music_url']);
            $spotify_uri = strtok($spotify_uri, '?');
            $music_embed = '<iframe src="https://open.spotify.com/embed/' . esc_attr($spotify_uri) . '?theme=0" height="80" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="width:100%;border-radius:12px"></iframe>';
        } elseif (strpos($theme['music_url'], 'soundcloud.com') !== false) {
            $music_embed = '<iframe height="80" src="https://w.soundcloud.com/player/?url=' . rawurlencode($theme['music_url']) . '&color=%23' . ltrim($theme['accent_color'] ?? '7c6df0', '#') . '&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false&visual=false" frameborder="0" allow="autoplay" loading="lazy" style="width:100%;border-radius:12px"></iframe>';
        }
    }
    if ($music_embed): ?>
    <!-- ── Music Player ── -->
    <div class="blp-music-player" style="margin-bottom:16px">
      <?php echo $music_embed; ?>
    </div>
    <?php endif; ?>

    <!-- ── Share Button ── -->
    <div class="blp-share-wrap">
      <button class="blp-share-btn" id="blp-share-btn" title="Share this page">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
        <?php esc_html_e('Share', 'biolink-pro'); ?>
      </button>
      <span class="blp-share-toast" id="blp-share-toast"><?php esc_html_e('Link copied!', 'biolink-pro'); ?></span>
    </div>

    <!-- ── Footer ── -->
    <div class="blp-footer">
      <a href="<?php echo esc_url(home_url()); ?>" target="_blank"><?php echo esc_html__('Made with BioLink Pro', 'biolink-pro'); ?> ⚡</a>
    </div>

  </div>

  <?php if (filter_var($theme['enable_darklight'], FILTER_VALIDATE_BOOLEAN)): ?>
  <!-- ── Dark/Light Mode Toggle ── -->
  <button class="blp-darklight-toggle" id="blp-darklight-btn" title="Toggle dark/light mode" aria-label="Toggle theme">
    <svg class="blp-dl-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    <svg class="blp-dl-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
  </button>
  <?php endif; ?>

</div>

<?php wp_footer(); ?>

<script>
const BLP_DEBUG = <?php echo (defined('WP_DEBUG') && WP_DEBUG) ? 'true' : 'false'; ?>;
function blpDebugTrace(message, error) {
  if (!BLP_DEBUG || typeof console === 'undefined' || typeof console.debug !== 'function') return;
  if (typeof error !== 'undefined') {
    console.debug('[BLP]', message, error);
    return;
  }
  console.debug('[BLP]', message);
}

/* Gallery carousel navigation */
function blpGalleryNav(id, dir) {
  var track = document.querySelector('[data-gallery="' + id + '"]');
  if (!track) return;
  var imgW = track.querySelector('.blp-gallery-img');
  if (!imgW) return;
  var scrollAmount = imgW.offsetWidth + 8;
  track.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
}

/* 3D card tilt effect */
(function() {
  document.querySelectorAll('.blp-link-card').forEach(function(card) {
    card.classList.add('blp-3d-card');
    card.addEventListener('mousemove', function(e) {
      var rect = card.getBoundingClientRect();
      var x = e.clientX - rect.left;
      var y = e.clientY - rect.top;
      var centerX = rect.width / 2;
      var centerY = rect.height / 2;
      var rotateX = ((y - centerY) / centerY) * -4;
      var rotateY = ((x - centerX) / centerX) * 4;
      card.style.transform = 'perspective(800px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) scale(1.02)';
    });
    card.addEventListener('mouseleave', function() {
      card.style.transform = '';
    });
  });
})();

/* Dark/Light mode toggle */
(function() {
  var btn = document.getElementById('blp-darklight-btn');
  if (!btn) return;
  var page = document.getElementById('blp-page');
  var key = 'blp_theme_mode';

  // Auto-detect preference or restore saved
  var saved = localStorage.getItem(key);
  if (saved === 'light' || (!saved && window.matchMedia('(prefers-color-scheme: light)').matches)) {
    page.classList.add('blp-light-mode');
  }

  btn.addEventListener('click', function() {
    page.classList.toggle('blp-light-mode');
    localStorage.setItem(key, page.classList.contains('blp-light-mode') ? 'light' : 'dark');
  });
})();

/* Click tracking */
function blpTrackClick(e, el) {
  const linkId    = el.dataset.linkId;
  const profileId = el.dataset.profileId;
  const endpoint = '<?php echo admin_url("admin-ajax.php"); ?>';

  const abVariant = el.dataset.ab || 'a';
  const payload = new URLSearchParams({
    action:     'blp_track_click',
    nonce:      '<?php echo wp_create_nonce("blp_public_nonce"); ?>',
    link_id:    linkId,
    profile_id: profileId,
    ab_variant: abVariant,
  });

  if (navigator.sendBeacon) {
    try {
      const body = new Blob([payload.toString()], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
      navigator.sendBeacon(endpoint, body);
      return true;
    } catch (err) {
      blpDebugTrace('sendBeacon click tracking failed.', err);
    }
  }

  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload,
    keepalive: true,
  }).catch(function(err) {
    blpDebugTrace('Fetch click tracking failed.', err);
  });

  return true;
}

/* Share button */
(function() {
  var btn = document.getElementById('blp-share-btn');
  var toast = document.getElementById('blp-share-toast');
  if (!btn) return;
  btn.addEventListener('click', function() {
    var pageUrl = '<?php echo esc_js($canonical_url); ?>';
    var pageTitle = '<?php echo esc_js($display_name); ?>';
    if (navigator.share) {
      navigator.share({ title: pageTitle, url: pageUrl }).catch(function(err){
        blpDebugTrace('Web Share API failed.', err);
      });
    } else {
      navigator.clipboard.writeText(pageUrl).then(function() {
        if (toast) {
          toast.classList.add('visible');
          setTimeout(function() { toast.classList.remove('visible'); }, 2000);
        }
      }).catch(function(){
        // Fallback for older browsers
        var ta = document.createElement('textarea');
        ta.value = pageUrl;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        if (toast) {
          toast.classList.add('visible');
          setTimeout(function() { toast.classList.remove('visible'); }, 2000);
        }
      });
    }
  });
})();

/* Countdown timer */
(function() {
  var el = document.getElementById('blp-countdown');
  if (!el) return;
  var target = new Date(el.dataset.target).getTime();
  if (isNaN(target)) return;
  function tick() {
    var now = Date.now();
    var diff = Math.max(0, target - now);
    var d = Math.floor(diff / 86400000);
    var h = Math.floor((diff % 86400000) / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    var de = document.getElementById('blp-cd-days');
    var he = document.getElementById('blp-cd-hours');
    var me = document.getElementById('blp-cd-mins');
    var se = document.getElementById('blp-cd-secs');
    if (de) de.textContent = String(d).padStart(2, '0');
    if (he) he.textContent = String(h).padStart(2, '0');
    if (me) me.textContent = String(m).padStart(2, '0');
    if (se) se.textContent = String(s).padStart(2, '0');
    if (diff > 0) setTimeout(tick, 1000);
  }
  tick();
})();

<?php if (filter_var($theme['enable_particles'], FILTER_VALIDATE_BOOLEAN)): ?>
/* Particle system */
(function() {
  const canvas = document.getElementById('blp-particles');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let particles = [];

  function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  const accentHex  = '<?php echo esc_js($theme['accent_color']); ?>';
  const r = parseInt(accentHex.slice(1,3), 16);
  const g = parseInt(accentHex.slice(3,5), 16);
  const b = parseInt(accentHex.slice(5,7), 16);

  for (let i = 0; i < 60; i++) {
    particles.push({
      x:  Math.random() * window.innerWidth,
      y:  Math.random() * window.innerHeight,
      r:  Math.random() * 1.5 + 0.5,
      vx: (Math.random() - 0.5) * 0.4,
      vy: (Math.random() - 0.5) * 0.4,
      a:  Math.random() * 0.6 + 0.1,
    });
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0) p.x = canvas.width;
      if (p.x > canvas.width) p.x = 0;
      if (p.y < 0) p.y = canvas.height;
      if (p.y > canvas.height) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${r},${g},${b},${p.a})`;
      ctx.fill();
    });
    requestAnimationFrame(animate);
  }
  animate();
})();
<?php endif; ?>

/* Service Worker registration for PWA */
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('<?php echo esc_url(add_query_arg(['blp_sw' => 1], home_url('/'))); ?>').catch(function(err){
    blpDebugTrace('Service worker registration failed.', err);
  });
}
</script>
</body>
</html>
