<?php
namespace UiCore\Settings;

defined('ABSPATH') || exit();

/**
 * Animations settings.
 *
 * @return array
 */

$category = _x('Animations', 'Admin - Theme Options', 'uicore-framework');
$category_slug = 'animations';

return [
    self::add_toggle([
        'id' => 208,
        'index' => 'animations',
        'adv' => true,
        'name' => _x('Enable Animations', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'module' => 'admin',
        'desc' => _x('Enable animations engine. Turning this feature off will increase your website performance in browser (client side).', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('animations enable', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => true,
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_toggle([
        'id' => 347,
        'index' => 'uianim_disable',
        'adv' => true,
        'name' => _x('Disable Animate Controller', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Disable the animate controller from editor', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'module' => 'admin',
        'tags' => _x('animate controller disable', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => false,
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_toggle([
        'id' => 348,
        'index' => 'uianim_scroll',
        'adv' => true,
        'name' => _x('Enable Smooth Scroll', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Enable smooth scroll', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'module' => 'admin',
        'tags' => _x('smooth scroll', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => false,
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_select([
        'id' => 338,
        'index' => 'uianim_style',
        'adv' => true,
        'name' => _x('Animations Style', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'module' => 'admin',
        'desc' => _x('Set the default animations style', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('animations style', 'Admin - Theme Options Search', 'uicore-framework'),
        'default' => '',
        'options' => [
            [
                'name' => _x('Default', 'Admin - Theme Options', 'uicore-framework'),
                'value' => ''
            ],
            [
                'name' => _x('Creative', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style1'
            ],
            [
                'name' => _x('Snappy', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style2'
            ],
            [
                'name' => _x('Soft', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style3'
            ],
            [
                'name' => _x('Laser', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style4'
            ],
            [
                'name' => _x('Elastic', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style5'
            ],
            [
                'name' => _x('Linear', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style6'
            ],
            [
                'name' => _x('Magic', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style7'
            ],
            [
                'name' => _x('SCI-FI', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'style8'
            ]
        ],
        'size' => 'm',
        'dependecies' => null,
        'visible' => true,
    ]),
    self::add_select([
        'id' => 209,
        'index' => 'animations_page',
        'adv' => true,
        'name' => _x('Animation Type', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set the animation style.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('animation type', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => 'none',
        'options' => [
            [
                'name' => _x('None', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'none'
            ],
            [
                'name' => _x('Fade', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'fade'
            ],
            [
                'name' => _x('Fade In', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'fade in'
            ],
            [
                'name' => _x('Reveal', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'reveal'
            ],
            [
                'name' => _x('Fade and Reveal', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'fade and reveal'
            ],
            [
                'name' => _x('Columns', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'columns'
            ],
            [
                'name' => _x('Multilayer', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'multilayer'
            ]
        ],
        'size' => 'm',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_select([
        'id' => 210,
        'index' => 'animations_page_duration',
        'adv' => true,
        'name' => _x('Animation Duration', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set the animation speed.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('animation duration', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => 209,
        'default' => 'normal',
        'visible' => ['!animations_page' => 'none'],
        'options' => [
            [
                'name' => _x('Slow', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'slow',
            ],
            [
                'name' => _x('Normal', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'normal',
            ],
            [
                'name' => _x('Fast', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'fast',
            ],
        ],
        'size' => 'm',
        'module' => 'admin',
    ]),
    self::add_color([
        'id' => 211,
        'index' => 'animations_page_color',
        'adv' => true,
        'name' => _x('Reveal Background Color', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'default' => '',
        'desc' => _x('Set the background color for reveal animation.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('animation reveal background color', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'dependecies' => 209,
        'visible' => [
            'in_array(animations_page)' => ['reveal', 'fade and reveal']
        ],
    ]),
    self::add_select([
        'id' => 360,
        'adv' => true,
        'index' => 'animations_preloader',
        'size' => 'm',
        'default' => 'none',
        'name' => _x('Preloader', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Choose a preloader style.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'options' => self::get_preloaders(),
        'tags' => _x('page transition preloader animation preloader', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'dependecies' => null,
        'visible' => true
    ]),
    self::add_input([
        'id' => 361,
        'adv' => true,
        'index' => 'animations_preloader_text',
        'size' => 'l',
        'default' => 'Loading',
        'name' => _x('Preloader Text', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Choose a preloader text.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'options' => self::get_preloaders(),
        'tags' => _x('page transition preloader animation preloader text', 'Admin - Theme Options Search', 'uicore-framework'),
        'accept' => 'text',
        'size' => 'l',
        'module' => 'admin',
        'responsive' => false,
        'dependecies' => null,
        'visible' => [
            'strpos(animations_preloader)' => 'text',
        ]
    ]),
    self::add_color([
        'id' => 362,
        'adv' => true,
        'index' => 'animations_preloader_color',
        'default' => 'White',
        'name' => _x('Preloader Color', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Choose the preloader color.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('page transition preloader animation preloader text', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'category' => $category,
        'category_slug' => $category_slug,
        'dependecies' => null,
        'visible' => [
            '!animations_preloader' => 'none',
        ]
    ]),
    self::add_color([
        'id' => 363,
        'adv' => true,
        'index' => 'animations_preloader_text_color',
        'default' => 'White',
        'name' => _x('Preloader Text Color', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Choose the preloader text color.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'module' => 'admin',
        'tags' => _x('page transition preloader animation preloader text', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => null,
        'visible' => [
            'strpos(animations_preloader)' => 'text',
        ]
    ]),
    self::add_input([
        'id' => 364,
        'adv' => true,
        'index' => 'animations_preloader_words',
        'size' => 'l',
        'default' => 'Demo | Intro | Words',
        'name' => _x('Preloader intro words', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Use the "|" character to split the words during the animation.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'tags' => _x('page transition preloader animation preloader text intro words', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'accept' => 'text',
        'responsive' => false,
        'dependecies' => null,
        'visible' => [
            'animations_preloader' => 'intro-words',
        ]
    ]),
];

