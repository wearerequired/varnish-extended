# Varnish Extended

Extends [Varnish HTTP Purge](https://wordpress.org/plugins/varnish-http-purge/) to purge the cache on multiple backends.
 
## Installation

1. Install the plugin.
1. Define `VARNISH_BACKENDS` in your wp-config.php file. Example: `define( 'VARNISH_BACKENDS', [ '127.0.0.1:6081', '127.0.0.2:6081' ] );`

## Changelog

### 1.0.0
* Initial Release
