<?php

//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

alc_uninstall();

function alc_uninstall() {
	global $wpdb, $table_prefix;

	// For Single site
	if (!is_multisite()) {
		$wpdb->query("DROP TABLE {$table_prefix}alc_link");
		$wpdb->query("DROP TABLE {$table_prefix}alc_address");
		$wpdb->query("DROP TABLE {$table_prefix}alc_redirectlog");
			
		delete_option('alc_url_trigger');
		delete_option('alc_db_version');
	} 
	// For Multisite
	else {
		$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
		$original_blog_id = get_current_blog_id();
		
		foreach ($blog_ids as $blog_id) {
			switch_to_blog($blog_id);
			
			$wpdb->query("DROP TABLE {$table_prefix}alc_link");
			$wpdb->query("DROP TABLE {$table_prefix}alc_address");
			$wpdb->query("DROP TABLE {$table_prefix}alc_redirectlog");
			
			delete_option('alc_url_trigger');
			delete_option('alc_db_version');
		}
		
		switch_to_blog($original_blog_id);
	}
}

?>