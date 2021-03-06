<?php

defined('ABSPATH') or die('Jog on!');

/**
 * Save / Insert a shortcode
 *
 * @return bool
 */
function sh_cd_shortcodes_save_post() {

	// Capture the raw $_POST fields, the save functions will process and validate the data
	$shortcode = sh_cd_get_values_from_post( [ 'id', 'slug', 'previous_slug', 'data', 'disabled', 'multisite' ] );

	return sh_cd_db_shortcodes_save( $shortcode );
}

/**
 * Replace user parameters within a shortcode e.g. look for %%parameter%% and replace
 *
 * @param $shortcode
 * @param $user_defined_parameters
 *
 * @return mixed
 */
function sh_cd_apply_user_defined_parameters( $shortcode, $user_defined_parameters ){

    // Ensure we have something to do!
    if ( true === empty( $user_defined_parameters ) || false === is_array( $user_defined_parameters ) ) {
        return $shortcode;
    }

    foreach ( $user_defined_parameters as $key => $value ) {
        $shortcode = str_replace( '%%' . $key . '%%', $value, $shortcode );
    }

	return $shortcode;
}

/**
 * Generate a unique slug
 *
 * @param $slug
 *
 * @return string
 */
function sh_cd_slug_generate( $slug, $exising_id = NULL ) {

    if ( true === empty( $slug ) ) {
        return NULL;
    }

	$slug = sanitize_title( $slug );

    $original_slug = $slug;

    $try = 1;

    // Ensure the slug is unique
    while ( false === sh_cd_slug_is_unique( $slug, $exising_id ) ) {

	    $slug = sprintf( '%s_%d', $original_slug, $try );

        $try++;
    }

    return $slug;
}

/**
 * Clone an existing shortcode!
 *
 * @param $id
 *
 * @return bool
 */
function sh_cd_clone( $id ) {

	if( false === sh_cd_license_is_premium() ) {
		return true;
	}

	if ( false === is_numeric( $id ) ) {
		return false;
	}

	$to_be_cloned = sh_cd_db_shortcodes_by_id( $id );

	if ( true === empty( $to_be_cloned ) ) {
		return false;
	}

	unset( $to_be_cloned['id'] );

	return sh_cd_db_shortcodes_save( $to_be_cloned );
}

/**
 * Display message in admin UI
 *
 * @param $text
 * @param bool $error
 */
function sh_cd_message_display( $text, $error = false ) {

    if ( true === empty( $text ) ) {
        return;
    }

    printf( '<div class="%s"><p>%s</p></div>',
            true === $error ? 'error' : 'updated',
            esc_html( $text )
    );

    //TODO: Hook this to use admin_notices
}

/**
 * Fetch cache item
 *
 * @param $key
 *
 * @return mixed
 */
function sh_cd_cache_get( $key ) {

    $key = sh_cd_cache_generate_key( $key );

    return get_transient( $key );
}

/**
 * Set cache item
 *
 * @param $key
 * @param $data
 */
function sh_cd_cache_set( $key, $data, $expire = NULL ) {


	$expire = ( false === empty( $expire ) ) ? (int) $expire : 1 * HOUR_IN_SECONDS;

    $key = sh_cd_cache_generate_key( $key );

    set_transient( $key, $data, $expire );
}

/**
 * Delete cache for given shortcode slug / ID
 *
 * @param $slug_or_key
 */
function sh_cd_cache_delete_by_slug_or_key( $slug_or_key ) {

    if ( true === is_numeric( $slug_or_key ) ) {

	    $slug_or_key = sh_cd_db_shortcodes_get_slug_by_id( $slug_or_key );

        sh_cd_cache_delete( $slug_or_key );

    } else {
	    sh_cd_cache_delete( $slug_or_key );
    }

    // Delete site option
	$slug_or_key = SH_CD_PREFIX . $slug_or_key;

	delete_site_option( $slug_or_key );

}

/**
 * Delete cache item
 *
 * @param $key
 *
 * @return mixed
 */
function sh_cd_cache_delete( $key ) {

    $key = sh_cd_cache_generate_key( $key );

    return delete_transient( $key );
}

/**
 * Generate cache key
 *
 * @param $key
 *
 * @return string
 */
function sh_cd_cache_generate_key( $key ) {
    return SH_CD_SHORTCODE . SH_CD_PLUGIN_VERSION . $key;
}

/**
 * Return link to list own shortcodes
 *
 * @return mixed
 */
function sh_cd_link_your_shortcodes() {

	$link = admin_url('admin.php?page=sh-cd-shortcode-variables-your-shortcodes');

	return esc_url( $link );
}

/**
 * Return link to add own shortcode
 *
 * @return mixed
 */
function sh_cd_link_your_shortcodes_add() {

    $link = admin_url('admin.php?page=sh-cd-shortcode-variables-your-shortcodes&action=add');

    return esc_url( $link );
}

/**
 * Return link to edit own shortcode
 *
 * @return mixed
 */
function sh_cd_link_your_shortcodes_edit( $id ) {

	$link = admin_url('admin.php?page=sh-cd-shortcode-variables-your-shortcodes&action=edit&id=' . (int) $id );

	return esc_url( $link );
}

/**
 * Return link to delete own shortcode
 *
 * @param $id
 * @return mixed
 */
function sh_cd_link_your_shortcodes_delete( $id ) {

	$link = admin_url('admin.php?page=sh-cd-shortcode-variables-your-shortcodes&action=delete&id=' . (int) $id );

	return esc_url( $link );
}

/**
 * Either fetch data from the $_POST object or from the array passed in!
 *
 * @param $object
 * @param $key
 * @return string
 */
function sh_cd_get_value_from_post_or_obj( $object, $key ) {

	if ( true === isset( $_POST[ $key ] ) ) {
		return $_POST[ $key ];
	}

	if ( true === isset( $object[ $key ] ) ) {
		return $object[ $key ];
	}

	return '';
}

/**
 * Either fetch data from the $_POST object for the given object keys
 *
 * @param $keys
 * @return array
 */
function sh_cd_get_values_from_post( $keys ) {

	$data = [];

	foreach ( $keys as $key ) {

		if ( true === isset( $_POST[ $key ] ) ) {
			$data[ $key ] = $_POST[ $key ];
		} else {
			$data[ $key ] = '';
		}

	}

	return $data;
}

/**
 * Toggle the status of a shortcode
 *
 * @param $id
 */
function sh_cd_toggle_status( $id ) {

	$slug = sh_cd_db_shortcodes_by_id( (int) $id );

	if ( false === empty( $slug ) ) {

	    $status = ( 1 === (int) $slug['disabled'] ) ? 0 : 1 ;

		sh_cd_db_shortcodes_update_status( $id, $status );

	    return $status;
    }

	return NULL;
}

/**
 * Toggle the multisite of a shortcode
 *
 * @param $id
 * @return int|null
 */
function sh_cd_toggle_multisite( $id ) {

	$slug = sh_cd_db_shortcodes_by_id( (int) $id );

	if ( false === empty( $slug ) ) {

		$multisite = ( 1 === (int) $slug['multisite'] ) ? 0 : 1 ;

		sh_cd_db_shortcodes_update_multisite( $id, $multisite );

		return $multisite;
	}

	return NULL;
}


/**
 * Display a table of premade shortcodes
 *
 * @param string $display
 * @return string
 */
function sh_cd_display_premade_shortcodes( $display = 'all' ) {

	$premium_user = sh_cd_license_is_premium();
	$upgrade_link = sprintf( '<a class="button" href="%1$s"><i class="fas fa-check"></i> %2$s</a>', sh_cd_license_upgrade_link(), __('Upgrade now', SH_CD_SLUG ) );

	switch ( $display ) {
		case 'free':
			$shortcodes = sh_cd_shortcode_presets_free_list();
			$show_premium_col = false;
			break;
		case 'premium':
			$shortcodes = sh_cd_shortcode_presets_premium_list();
			$show_premium_col = false;
			break;
		default:
			$shortcodes = sh_cd_presets_both_lists();
			$show_premium_col = true;
	}

	$html = sprintf('<table class="widefat sh-cd-table" width="100%%">
                <tr class="row-title">
                    <th class="row-title" width="30%%">%s</th>', __('Shortcode', SH_CD_SLUG ) );

                     if ( true === $show_premium_col) {
	                     $html .= sprintf( '<th class="row-title">%s</th>', __('Premium', SH_CD_SLUG ) );
                     }

					$html .= sprintf( '<th width="*">%s</th>
											</tr>', __('Description', SH_CD_SLUG ) );

	$class = '';

		foreach ( $shortcodes as $key => $data ) {

			$class = ($class == 'alternate') ? '' : 'alternate';

			$shortcode = '[' . SH_CD_SHORTCODE. ' slug="' . $key . '"]';

			$premium_shortcode = ( true === isset( $data['premium'] ) && true === $data['premium'] );

			$html .= sprintf( '<tr class="%s"><td>%s</td>', $class, esc_html( $shortcode ) );


            if ( true === $show_premium_col) {

                $html .= sprintf( '<td align="middle">%s%s</td>',
                    ( true === $premium_shortcode && true === $premium_user ) ? '<i class="fas fa-check"></i>' : '',
                    ( true == $premium_shortcode && false === $premium_user ) ? $upgrade_link : ''
                );
            }

			$html .= sprintf( '<td>%s</td></tr>', wp_kses_post( $data['description'] ) );

        }

    $html .= '</table>';

	return $html;
}

/**
 * Display an upgrade button
 *
 * @param string $css_class
 * @param null $link
 */
function sh_cd_upgrade_button( $css_class = '', $link = NULL ) {

    $link = ( false === empty( $link ) ) ? $link : SH_CD_UPGRADE_LINK . '?hash=' . sh_cd_generate_site_hash() ;

	echo sprintf('<a href="%s" class="button-primary sh-cd-upgrade-button%s"><i class="far fa-credit-card"></i> %s £%s %s</a>',
		esc_url( $link ),
		esc_attr( ' ' . $css_class ),
        __( 'Upgrade to Premium for ', SH_CD_SLUG ),
        esc_html( sh_cd_license_price() ),
		__( 'a year ', SH_CD_SLUG )
	);
}

/**
 * Is multsite functionality active for this install?
 *
 * @return bool
 */
function sh_cd_is_multisite_enabled() {

	if ( false === is_multisite() ) {
		return false;
	}

	if ( false === sh_cd_license_is_premium() ) {
		return false;
	}

	return true;
}

/**
 * Fetch all multisite slugs
 *
 * @return array|null
 */
function sh_cd_multisite_slugs() {

	if ( false === is_multisite() ) {
		return [];
	}

	$cache = sh_cd_cache_get( 'sh-cd-multisite-slugs' );

	if ( false !== $cache ) {
		return $cache;
	}

	$slugs = sh_cd_db_shortcodes_multisite_slugs();

	$slugs = ( false === empty( $slugs ) ) ? wp_list_pluck( $slugs, 'slug' ) : NULL;

	// Cache this for a short time
	sh_cd_cache_set( 'sh-cd-multisite-slugs', $slugs, 30 );

	return ( true === is_array( $slugs ) ) ? $slugs : [];
}

/**
 * Have we reached the limit of free shortcodes?
 * @return bool
 */
function sh_cd_reached_free_limit() {

	if ( true === sh_cd_license_is_premium() ) {
		return false;
	}

	$existing_shortcodes = sh_cd_db_shortcodes_count();

	if ( true === empty( $existing_shortcodes ) ) {
		return false;
	}

	return ( (int) $existing_shortcodes >= 15 );
}

/**
 * Get the minimum user role allowed for viewing data pages in admin
 * @return mixed|void
 */
function sh_cd_permission_role() {

	// If not premium, then admin only
	if ( false === sh_cd_license_is_premium() ) {
		return 'manage_options';
	}

	$permission_role = get_option( 'sh-cd-edit-permissions', 'manage_options' );

	return ( false === empty( $permission_role ) ) ? $permission_role : 'manage_options';
}

/**
 * Does the user have the correct permissions to view this page?
 */
function sh_cd_permission_check() {

	$allowed_viewer = sh_cd_permission_role();

	if ( false === current_user_can( $allowed_viewer ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.', SH_CD_SLUG ) );
	}
}

/**
 * Display upgrade notice
 *
 * @param bool $pro_plus
 */
function sh_cd_display_pro_upgrade_notice( ) {
	?>

	<div class="postbox sh-cd-advertise-premium">
		<h3 class="hndle"><span><?php echo __( 'Upgrade Snippet Shortcodes and get more features!', SH_CD_SLUG ); ?> </span></h3>
		<div style="padding: 0px 15px 0px 15px">
			<p><a href="<?php echo esc_url( admin_url('admin.php?page=sh-cd-shortcode-variables-license') ); ?>" class="button-primary"><?php echo __( 'Upgrade now', SH_CD_SLUG ); ?></a></p>
		</div>
	</div>

	<?php
}
