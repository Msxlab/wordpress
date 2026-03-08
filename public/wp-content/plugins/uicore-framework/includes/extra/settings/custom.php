<?php
namespace UiCore\Settings;

defined('ABSPATH') || exit();

/**
 * Custom settings.
 *
 * @return array
 */

$category = _x('Custom', 'Admin - Theme Options', 'uicore-framework');
$category_slug = 'custom';

return [
    self::add_code_editor([
        'id' => 178,
        'index' => 'customcss',
        'adv' => false,
        'name' => _x('Custom CSS', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set your custom CSS code. Loaded before &lt;/head&gt; tag.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'default' => ' /* CUSTOM CSS */',
        'tags' => _x('custom css CSS', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_code_editor([
        'id' => 179,
        'index' => 'customjs',
        'adv' => false,
        'name' => _x('Custom JS', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('Set your custom JS code. Loaded before &lt;/head&gt; tag.', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'default' => ' /* CUSTOM JS */',
        'tags' => _x('custom js JS javascript', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_code_editor([
        'id' => 316,
        'index' => 'header_content',
        'adv' => false,
        'name' => _x('Custom Content in Header', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('The content will be added in the &lt;/head&gt; section (use this for analytics code).', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('custom script markup head', 'Admin - Theme Options Search', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'default' => '',
        'module' => 'admin',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_code_editor([
        'id' => 317,
        'index' => 'footer_content',
        'adv' => false,
        'name' => _x('Custom Content in Footer', 'Admin - Theme Options', 'uicore-framework'),
        'desc' => _x('The content will be added before the closing &lt;/body&gt; tag.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('custom script markup footer', 'Admin - Theme Options Search', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'default' => '',
        'module' => 'admin',
        'dependecies' => NULL,
        'visible' => true,
    ]),
];