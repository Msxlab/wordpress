<?php
defined('ABSPATH') || exit();
//INCLUDED IN CLASS CSS

$css .='    
body{
    ' . self::generate_typography_css('h1', $json_settings) . '
    ' . self::generate_typography_css('h2', $json_settings) . '
    ' . self::generate_typography_css('h3', $json_settings) . '
    ' . self::generate_typography_css('h4', $json_settings) . '
    ' . self::generate_typography_css('h5', $json_settings) . '
    ' . self::generate_typography_css('h6', $json_settings) . '
    ' . self::generate_typography_css('p', $json_settings) . '
}

@media (max-width: ' . $br_points['lg'] . 'px) {
    body{
        ' . self::generate_responsive_typography_css('h1', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('h2', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('h3', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('h4', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('h5', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('h6', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('p', $json_settings, 't') . '
    }
    .uicore-single-header h1.entry-title{
        ' . self::get_size_and_unit($json_settings['h1']['s']['t'], '--uicore-typography--h1-s') . '
    }
    .uicore-blog .uicore-post-content:not(.uicore-archive) .entry-content{
        ' . self::generate_responsive_typography_css('blog_h1', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_h2', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_h3', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_h4', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_h5', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_h6', $json_settings, 't') . '
        ' . self::generate_responsive_typography_css('blog_p', $json_settings, 't') . '
    }
    .uicore-blog-grid {
        --uicore-typography--blog_title-s:' . $json_settings['blog_title']['s']['t'] . 'px;
        --uicore-typography--p-s:' . $json_settings['blog_ex']['s']['t'] . 'px;
    }
}
@media (max-width: ' . $br_points['md'] . 'px) {
    body{
       ' . self::generate_responsive_typography_css('h1', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('h2', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('h3', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('h4', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('h5', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('h6', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('p', $json_settings, 'm') . '
    }
    .uicore-single-header h1.entry-title{
         ' . self::get_size_and_unit($json_settings['h1']['s']['m'], '--uicore-typography--h1-s') . '
    }
    .uicore-blog .uicore-post-content:not(.uicore-archive) .entry-content{
        ' . self::generate_responsive_typography_css('blog_h1', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_h2', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_h3', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_h4', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_h5', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_h6', $json_settings, 'm') . '
        ' . self::generate_responsive_typography_css('blog_p', $json_settings, 'm') . '
    }
    .uicore-blog-grid {
        ' . (!empty($json_settings['blog_title']['s']['m']) ? '--uicore-typography--blog_title-s:' . $json_settings['blog_title']['s']['m'] . 'px;' : '') . '
        ' . (!empty($json_settings['blog_ex']['s']['m']) ? '--uicore-typography--p-s:' . $json_settings['blog_ex']['s']['m'] . 'px;' : '') . '
    }
}
';