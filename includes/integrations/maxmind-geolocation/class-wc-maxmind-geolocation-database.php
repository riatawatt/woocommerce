<?php
/**
 * MaxMind Geolocation Database
 *
 * @version 3.9.0
 * @package WooCommerce/Integrations
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC MaxMind Geolocation Database
 *
 * @version 3.9.0
 */
class WC_MaxMind_Geolocation_Database {

	/**
	 * The name of the MaxMind database to utilize.
	 */
	const DATABASE = 'GeoLite2-Country';

	/**
	 * The extension for the MaxMind database.
	 */
	const DATABASE_EXTENSION = '.mmdb';

	/**
	 * Fetches the path that the database should be stored.
	 *
	 * @return string The local database path.
	 */
	public function get_database_path() {
		$database_path = WP_CONTENT_DIR . '/uploads/' . self::DATABASE . self::DATABASE_EXTENSION;

		/**
		 * Filter the geolocation database storage path.
		 *
		 * @param string $database_path The path to the database.
		 * @param int $version Deprecated since 3.4.0.
		 * @deprecated 3.9.0
		 */
		$database_path = apply_filters_deprecated(
			'woocommerce_geolocation_local_database_path',
			array( $database_path, 2 ),
			'3.9.0',
			'woocommerce_maxmind_geolocation_database_path'
		);

		/**
		 * Filter the geolocation database storage path.
		 *
		 * @since 3.9.0
		 * @param string $database_path The path to the database.
		 */
		return apply_filters( 'woocommerce_maxmind_geolocation_database_path', $database_path );
	}

	/**
	 * Fetches the database from the MaxMind service.
	 *
	 * @param string $license_key The license key to be used when downloading the database.
	 * @return string|WP_Error The path to the database file or an error if invalid.
	 */
	public function download_database( $license_key ) {
		$download_uri  = 'https://download.maxmind.com/app/geoip_download?';
		$download_uri .= http_build_query(
			array(
				'edition_id'  => self::DATABASE,
				'license_key' => $license_key,
				'suffix'      => 'tar.gz',
			)
		);

		// Needed for the download_url call right below.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$tmp_archive_path = download_url( $download_uri );
		if ( is_wp_error( $tmp_archive_path ) ) {
			// Transform the error into something more informative.
			$error_data = $tmp_archive_path->get_error_data();
			if ( isset( $error_data['code'] ) ) {
				switch ( $error_data['code'] ) {
					case 401:
						return new WP_Error(
							'woocommerce_maxmind_geolocation_database_license_key',
							__( 'The MaxMind license key is invalid.', 'woocommerce' )
						);
				}
			}

			return new WP_Error( 'woocommerce_maxmind_geolocation_database_download', __( 'Failed to download the MaxMind database.', 'woocommerce' ) );
		}

		// Extract the database from the archive.
		try {
			$file = new PharData( $tmp_archive_path );

			$tmp_database_path = trailingslashit( dirname( $tmp_archive_path ) ) . trailingslashit( $file->current()->getFilename() ) . self::DATABASE . self::DATABASE_EXTENSION;

			$file->extractTo(
				dirname( $tmp_database_path ),
				trailingslashit( $file->current()->getFilename() ) . self::DATABASE . self::DATABASE_EXTENSION,
				true
			);
		} catch ( Exception $exception ) {
			return new WP_Error( 'woocommerce_maxmind_geolocation_database_archive', $exception->getMessage() );
		} finally {
			// Remove the archive since we only care about a single file in it.
			unlink( $tmp_archive_path );
		}

		return $tmp_database_path;
	}
}