<?php
namespace UiCore\Settings;

defined('ABSPATH') || exit();

/**
 * Page Title settings.
 *
 * @return array
 */

$category = _x('Page Title', 'Admin - Theme Options', 'uicore-framework');
$category_slug = 'pagetitle';

return [
    self::add_toggle([
        'id' => 96,
        'index' => 'pagetitle',
        'adv' => false,
        'name' => _x('Enable Page Title', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Enable or disable the page title section.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('enable page title', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'true',
        'visible' => true,
        'module' => 'frontend',
    ]),
    self::add_background([
        'id' => 97,
        'index' => 'pagetitle_bg',
        'adv' => false,
        'name' => _x('Page Title Background', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the background for the page title section.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title background', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => [
            'type' 			=> 'Dark Neutral',
            'solid' 		=> 'Dark Neutral',
            'gradient' 		=> [
                'angle' 		=> '90',
                'color1' 		=> '#ffffff',
                'color2' 		=> '#222222',
            ],
            'image' 		=> [
                'url' 			=> '',
                'attachment' 	=> 'scroll',
                'position' 		=> [
                    'd' => 'bottom center',
                    't' => 'center center',
                    'm' => 'center center',
                ],
                'repeat' 		=> 'no-repeat',
                'size' 			=> [
                    'd' => 'cover',
                    't' => 'cover',
                    'm' => 'contain',
                ],
            ],
        ],
        'visible' => true,
        'module' => 'admin',
        'blur' => false,
    ]),
    self::add_color2([
        'id' => 98,
        'index' => 'pagetitle_overlay',
        'adv' => false,
        'name' => _x('Page Title Overlay', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the overlay gradient for the page title background.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title overlay', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => [],
        'visible' => true,
        'module' => 'admin',
    ]),
    self::add_toggle([
        'id' => 99,
        'index' => 'pagetitle_i',
        'adv' => true,
        'name' => _x('Show Icon', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Show an icon in the page title section.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title icon', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'true',
        'visible' => true,
        'module' => 'frontend',
    ]),
    self::add_input([
        'id' => 100,
        'index' => 'pagetitle_padding',
        'adv' => false,
        'name' => _x('Page Title Padding', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the vertical padding for the page title section.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title padding', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => [ 'd' => '50', 't' => '40', 'm' => '30' ],
        'visible' => true,
        'module' => 'admin',
        'responsive' => true,
        'accept' => 'number',
        'size' => 's',
        'min' => 0,
        'max' => 1000,
        'suffix' => 'px',
    ]),
    self::add_select([
        'id' => 101,
        'index' => 'pagetitle_tag',
        'adv' => true,
        'name' => _x('Page Title Tag', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the HTML tag for the page title.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title tag', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'h1',
        'visible' => true,
        'module' => 'admin',
        'options' => [
            [
                'name' => 'H1',
                'value' => 'h1'
            ],
            [
                'name' => 'H2',
                'value' => 'h2'
            ],
            [
                'name' => 'H3',
                'value' => 'h3'
            ],
            [
                'name' => 'H4',
                'value' => 'h4'
            ],
            [
                'name' => 'H5',
                'value' => 'h5'
            ],
            [
                'name' => 'H6',
                'value' => 'h6'
            ],
            [
                'name' => 'DIV',
                'value' => 'div'
            ],
            [
                'name' => 'SPAN',
                'value' => 'span'
            ],
        ],
        'size' => 's',
    ]),
    self::add_color([
        'id' => 102,
        'index' => 'pagetitle_color',
        'adv' => true,
        'name' => _x('Page Title Color', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the color for the page title text.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title color', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => '#FFFFFF',
        'visible' => true,
        'module' => 'admin',
    ]),
    self::add_select([
        'id' => 103,
        'index' => 'pagetitle_transform',
        'adv' => true,
        'name' => _x('Page Title Transform', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the text transform for the page title.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title transform', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'none',
        'visible' => true,
        'module' => 'admin',
        'options' => [
            [
                'name' => _x('None', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'none'
            ],
            [
                'name' => _x('Capitalize', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'capitalize'
            ],
            [
                'name' => _x('Uppercase', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'uppercase'
            ],
            [
                'name' => _x('Lowercase', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'lowercase'
            ],
            [
                'name' => _x('Inherit', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'inherit'
            ],
        ],
        'size' => 's',
    ]),
    self::add_select([
        'id' => 104,
        'index' => 'pagetitle_align',
        'adv' => true,
        'name' => _x('Page Title Alignment', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the alignment for the page title.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title alignment', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'left',
        'visible' => true,
        'module' => 'admin',
        'options' => [
            [
                'name' => _x('Left', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'left'
            ],
            [
                'name' => _x('Center', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'center'
            ],
            [
                'name' => _x('Right', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'right'
            ],
        ],
        'size' => 's',
    ]),
    self::add_input([
        'id' => 105,
        'index' => 'pagetitle_width',
        'adv' => true,
        'name' => _x('Page Title Width', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the width for the page title section.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page title width', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => '75',
        'visible' => true,
        'module' => 'admin',
        'responsive' => false,
        'accept' => 'number',
        'size' => 's',
        'min' => 20,
        'max' => 100,
        'suffix' => '%',
    ]),
    self::add_toggle([
        'id' => 106,
        'index' => 'pagetitle_breadcrumbs',
        'adv' => false,
        'name' => _x('Breadcrumbs', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Add breadcrumb links on the right side of page title.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('breadcrumbs', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'true',
        'visible' => true,
        'module' => 'frontend',
    ]),
    // Animations
    self::add_select([
        'id' => 221,
        'index' => 'animations_title',
        'adv' => true,
        'name' => _x('Entrance Animation Type', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the animation style.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('animation type', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'none',
        'visible' => true,
        'module' => 'admin',
        'options' => [
            [
                'value' => 'none'
            ],
            [
                'value' => 'fade'
            ],
            [
                'value' => 'fade down'
            ],
            [
                'value' => 'fade up'
            ],
        ],
        'size' => 'm',
    ]),
    self::add_select([
        'id' => 222,
        'index' => 'animations_title_duration',
        'adv' => true,
        'name' => _x('Animation Duration', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the animation speed.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('animation duration', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => 'normal',
        'visible' => true,
        'module' => 'admin',
        'options' => [
            [
                'value' => 'slow'
            ],
            [
                'name' => _x('Normal', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'normal'
            ],
            [
                'name' => _x('Fast', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'fast'
            ],
        ],
        'size' => 's',
    ]),
    self::add_input([
        'id' => 223,
        'index' => 'animations_title_delay',
        'adv' => true,
        'name' => _x('Animation Delay', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the time before animation starts loading.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('animation delay', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => '0',
        'visible' => true,
        'module' => 'admin',
        'responsive' => false,
        'accept' => 'number',
        'size' => 's',
        'min' => 0,
        'max' => 5000,
        'suffix' => 'ms',
    ]),
    self::add_input([
        'id' => 224,
        'index' => 'animations_title_delay_child',
        'adv' => true,
        'name' => _x('Delay Between Elements', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the animation delay between elements inside page title.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('animation delay elements', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => '200',
        'visible' => true,
        'module' => 'admin',
        'responsive' => false,
        'accept' => 'number',
        'size' => 's',
        'min' => 0,
        'max' => 5000,
        'suffix' => 'ms',
    ]),
];