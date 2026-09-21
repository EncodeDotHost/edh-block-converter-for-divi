<?php
/**
 * Uninstall.
 *
 * The plugin deletes its conversion flags and reports. It keeps the
 * backups of the original content, because they are the only copy of the
 * Divi content. Define EDH_DG_DELETE_BACKUPS as true in wp-config.php to
 * delete the backups also.
 *
 * @package EDH\DiviGutenberg
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_post_meta_by_key( '_edh_dg_converted' );
delete_post_meta_by_key( '_edh_dg_report' );

if ( defined( 'EDH_DG_DELETE_BACKUPS' ) && EDH_DG_DELETE_BACKUPS ) {
	delete_post_meta_by_key( '_edh_dg_original_content' );
	delete_post_meta_by_key( '_edh_dg_original_meta' );
}
