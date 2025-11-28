<?php
/**
 * Shadcn WP Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WP_Shadcn
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/inc/Core.php';
require_once __DIR__ . '/inc/DarkMode.php';
require_once __DIR__ . '/inc/Integrations.php';

// Development Tools (only load in development environment)
if ( is_admin() ) {
	// JSX to Gutenberg Converter (development tool)
	if ( file_exists( __DIR__ . '/inc/Admin/JSXConverter.php' ) ) {
		require_once __DIR__ . '/inc/Admin/JSXConverter.php';
	}
}
