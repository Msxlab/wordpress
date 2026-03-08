<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '&q-p_V9-?W=zj6eN.{;=GXk`ooCI1LDM=sE~!@IZ;{_6%1)M}R=K<#7(x_A]`i2V' );
define( 'SECURE_AUTH_KEY',   '2<=Mhz?y)8bNq)%vjPu-Bu5,!a7!KIhJ;Jg_=0ydLc&q1$YxzI!Tq_B]]k{vHRD&' );
define( 'LOGGED_IN_KEY',     '5I@M$B+Shuk;9eYvtxsyMEtkBesV*.iV)E,2r3O&7HvWlPwbI3M>ybjD{G{D{RqN' );
define( 'NONCE_KEY',         '7u*j+ZP)pG06R;Y;u03KkM]Yq9/Rz)&hJ[cSC!Y%BJ9*<h=5LD4f_YclqF#iRoN%' );
define( 'AUTH_SALT',         'E~/]35| G~<wI1[ =J@FjHm9^K;wTBi5}H`Kb^,Dm3AZ=zT1T]9|My?BiUDW>I*g' );
define( 'SECURE_AUTH_SALT',  'bO(W3L: B/#DXe<gDFqN3N6bvM1?tdh%O~WM8b itGJaIS9 A2]u#*$4^:na1$)Q' );
define( 'LOGGED_IN_SALT',    'e2>Zd/l4A_Bf=m!l~5]8MZg=zP_9#&+}Fk~PzM}#zRa+__taj4`KLV,:<_8f6::i' );
define( 'NONCE_SALT',        '?NV@$nl0~C%Uh{#)pJ]u}rz!eUyEpWbexD&&*!$dtEZ/HfJdP&cKg@1Tsu8E[zIK' );
define( 'WP_CACHE_KEY_SALT', 'MsMb-@nwp)M%VRV5J(P@RTe >V<UE9EOYzG%x>,~N]bh1% IrbI!UD}E*uH1,Iy{' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */

/* ── Security Lab Configuration ── */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'SAVEQUERIES', true );          // Log all SQL queries for SQLi detection
define( 'SCRIPT_DEBUG', true );         // Load unminified JS/CSS for analysis
define( 'CONCATENATE_SCRIPTS', false ); // Individual script paths visible
define( 'DISALLOW_FILE_EDIT', false );  // Allow file editor for testing



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
// WP_DEBUG already set in Security Lab Configuration above

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
