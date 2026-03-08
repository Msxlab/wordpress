<?php
namespace UiCore\Settings;

defined('ABSPATH') || exit();

/**
 * Animations settings.
 *
 * @return array
 */

$category = _x('Branding', 'Admin - Theme Options', 'uicore-framework');
$category_slug = 'branding';

return [
    self::add_media([
        'id' => 16,
        'index' => 'logo',
        'adv' => false,
        'name' => _x('Primary Logo', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set the default logo.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('primary logo', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => 'https://uicore.co/themeforest/uicore-logo.png',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_media([
        'id' => 17,
        'index' => 'logoS',
        'adv' => false,
        'name' => _x('Secondary Logo', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set logo for transparent headers.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('secondary logo transparent', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => '',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_media([
        'id' => 18,
        'index' => 'logoMobile',
        'adv' => true,
        'name' => _x('Mobile Logo', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set logo for mobile devices. If left blank, Primary Logo will be used.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('mobile logo', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => '',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_media([
        'id' => 19,
        'index' => 'logoSMobile',
        'adv' => true,
        'name' => _x('Secondary Mobile Logo', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set logo for mobile devices on transparent headers. If left blank, Secondary Logo will be used.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('secondary mobile logo transparent', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => '',
        'dependecies' => NULL,
        'visible' => true,
    ]),
    self::add_media([
        'id' => 20,
        'index' => 'fav',
        'adv' => false,
        'name' => _x('Favicon', 'Admin - Theme Options', 'uicore-framework'),
        'category' => $category,
        'category_slug' => $category_slug,
        'desc' => _x('Set the icon for browser tab and home screen. Recommended size: 196px x 196 px.', 'Admin - Theme Options', 'uicore-framework'),
        'tags' => _x('favicon', 'Admin - Theme Options Search', 'uicore-framework'),
        'module' => 'admin',
        'default' => '',
        'dependecies' => NULL,
        'visible' => true,
    ]),
];

