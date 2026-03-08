<?php
namespace UiCore\Settings;

defined('ABSPATH') || exit();

/**
 * Button settings.
 *
 * @return array
 */

$category = _x('Buttons', 'Admin - Theme Options', 'uicore-framework');
$slug = 'buttons';

return [
    self::add_typography([
        'id' => 198,
        'index' => 'button_typography_typography',
        'adv' => true,
        'name' => _x('Typography', 'Admin - Theme Options', 'uicore-framework'),
        'default' => [
            'f' => 'Accent',
            's' => [
                'd' => ['value' => '15', 'unit' => 'px'],
                't' => ['value' => '15', 'unit' => 'px'],
                'm' => ['value' => '14', 'unit' => 'px'],
            ],
            'h' => [
                'd' => ['value' => '1', 'unit' => 'em'],
                't' => ['value' => '1', 'unit' => 'em'],
                'm' => ['value' => '1', 'unit' => 'em'],
            ],
            'ls' => [
                'd' => ['value' => '0', 'unit' => 'em'],
                't' => ['value' => '0', 'unit' => 'em'],
                'm' => ['value' => '0', 'unit' => 'em'],
            ],
            't'  => 'None',
            'st' => '500',
            'c'  => '#FFFFFF',
        ],
        'module' => 'frontend',
        'hover' => true,
        'family' => true,
        'line_height' => false,
        'responsive' => true,
        'full_responsive' => false,
        'color' => true,
        'category' => $category,
        'category_slug' => $slug,
        'desc' => _x('Set global typography options for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons typography', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_color2([
        'id' => 199,
        'index' => 'button_background_color',
        'module' => 'frontend',
        'adv' => true,
        'name' => _x('Background', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'default' => [
            'm' => 'Primary',
            'h' => 'Secondary',
        ],
        'desc' => _x('Set the global background colors for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons background color', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_select([
        'id' => 200,
        'adv' => true,
        'index' => 'button_border_border',
        'module' => 'frontend',
        'name' => _x('Border Type', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'desc' => _x('Set the global border type for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons border type', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
        'default' => 'none',
        'options' => [
            [
                'name' =>_x('None', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'none',
            ],
            [
                'name' =>_x('Solid', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'solid',
            ],
            [
                'name' =>_x('Dashed', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'dashed',
            ],
            [
                'name' =>_x('Dotted', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'dotted',
            ],
            [
                'name' =>_x('Double', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'double',
            ],
            [
                'name' =>_x('Groove', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'groove',
            ],
        ],
        'size' => 'm',
    ]),
    self::add_input([
        'id' => 201,
        'adv' => true,
        'index' => 'button_border_width',
        'name' => _x('Border Width', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'module' => 'frontend',
        'desc' => _x('Set the global border width for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons border width', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => 200,
        'visible' => ['!button_border_border' => 'none'],
        'responsive' => false,
        'default' => '1',
        'accept' => 'number',
        'suffix' => 'px',
        'max' => 999,
        'min' => 0,
        'size' => 's',
    ]),
    self::add_color2([
        'id' => 202,
        'index' => 'button_border_color',
        'adv' => true,
        'name' => _x('Border Color', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'module' => 'frontend',
        'desc' => _x('Set the global border color for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons border color', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => 200,
        'visible' => ['!button_border_border' => 'none'],
        'default' => [
            'm' => 'Primary',
            'h' => 'Secondary',
        ],
    ]),
    self::add_input([
        'id' => 203,
        'index' => 'button_border_radius',
        'adv' => true,
        'name' => _x('Border Radius', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'module' => 'frontend',
        'desc' => _x('Set the global border radius for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons border radius', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
        'responsive' => false,
        'suffix' => 'px',
        'default' => '6',
        'accept' => 'number',
        'max' => 999,
        'min' => 0,
        'size' => 's',
    ]),
    // TODO: multiple inputs
    self::add_input([
        'id' => 204,
        'index' => 'button_padding',
        'adv' => true,
        'name' => _x('Padding', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'module' => 'frontend',
        'desc' => _x('Set the global padding for buttons.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('button buttons padding', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
        'responsive' => true,
        'default' => [
            'd' => [
                'top' => '17',
                'right' => '40',
                'bottom' => '17',
                'left' => '40',
            ],
            't' => [
                'top' => '17',
                'right' => '40',
                'bottom' => '17',
                'left' => '40',
            ],
            'm' => [
                'top' => '13',
                'right' => '35',
                'bottom' => '13',
                'left' => '35',
            ],
        ],
        'suffix' => 'px',
        'accept' => 'number',
        'max' => 999,
        'min' => 0,
        'size' => 's',
    ]),
    self::add_shadow([
        'id' => 374,
        'adv' => true,
        'index' => 'button_shadow',
        'module' => 'frontend',
        'default' => [],
        'name' => _x('Button Shadow', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Show / Hide the button shadow.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'tags' => _x('button shadow', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_select([
        'id' => 318,
        'index' => 'button_interaction',
        'adv' => true,
        'module' => 'frontend',
        'name' => _x('Hover Interaction', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set the button hover effect.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $slug,
        'tags' => _x('hover interaction', 'Admin - Theme Options Search', 'uicore-framework'),
        'dependecies' => NULL,
        'visible' => true,
        'default' => '',
        'options' => [
            [
                'name' => _x('None', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'none',
            ],
            [
                'name' => _x('Grow', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'grow',
            ],
            [
                'name' => _x('Shrink', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'shrink',
            ],
            [
                'name' => _x('Attract', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'attract',
            ],
            [
                'name' => _x('Float', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'float',
            ],
            [
                'name' => _x('Text Flip', 'Admin - Theme Options', 'uicore-framework'),
                'value' => 'text flip', // todo: is text flip in buttons.vue, should we change to text_flip?
            ],
        ],
        'size' => 'm',
    ])
];

