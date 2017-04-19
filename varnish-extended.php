<?php
/**
 * Plugin Name: Varnish Extended
 * Description: Extends Varnish HTTP Purge to purge the cache on multiple backends.
 * Version:     1.0.0
 * Author:      required
 * Author URI:  https://required.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace Required\VarnishExtended;

/**
 * Gets a list of of Varnish backends.
 *
 * @since 1.0.0
 *
 * @return array List of Varnish backends.
 */
function get_varnish_backends() {
	$varnish_backends = [];

	if ( defined( 'VARNISH_BACKENDS' ) ) {
		$varnish_backends = (array) VARNISH_BACKENDS;
	}

	return apply_filters( 'varnish_extended.varnish_backends', $varnish_backends );
}

/**
 * Overrides the default Varnish IP with the first of the
 * defined Varnish backends.
 *
 * @since 1.0.0
 *
 * @param string $varnish_ip IP of the Varnish backend.
 * @return string IP of the Varnish backend.
 */
function set_default_varnish_ip( $varnish_ip ) {
	$backends = get_varnish_backends();

	if ( ! $backends ) {
		return $varnish_ip;
	}

	return current( $backends );
}
add_filter( 'vhp_varnish_ip', __NAMESPACE__ . '\set_default_varnish_ip' );

/**
 * Adds a 'X-Forwarded-Proto' header if the current home URL uses HTTPS.
 *
 * @since 1.0.0
 *
 * @param array $headers HTTP Headers.
 * @return array HTTP Headers.
 */
function set_purge_http_headers( $headers ) {
	$scheme = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );

	if ( 'https' === $scheme ) {
		$headers['X-Forwarded-Proto'] = 'https';
	}

	return $headers;
}
add_filter( 'varnish_http_purge_headers', __NAMESPACE__ . '\set_purge_http_headers' );

/**
 * Purges caches on other Varnish backends.
 *
 * @since 1.0.0
 *
 * @param string $url      The URL to be purged.
 * @param string $purgeme  The URL sent to the Varnish backend.
 * @param array  $response The response of the previous purge.
 * @param array  $headers  HTTP headers sent to the Varnish backend.
 */
function purge_cache_on_other_backends( $url, $purgeme, $response, $headers ) {
	$backends = get_varnish_backends();
	if ( count( $backends ) < 2 ) {
		return;
	}

	$default_backend = array_shift( $backends ); // Skip the first one.
	$purgeme_orig = $purgeme;

	foreach ( $backends as $backend ) {
		$purgeme = str_replace( $default_backend, $backend, $purgeme_orig );
		wp_remote_request( $purgeme, [ 'method' => 'PURGE', 'headers' => $headers ] );
	}
}
add_action( 'after_purge_url', __NAMESPACE__ . '\purge_cache_on_other_backends', 10, 4 );
