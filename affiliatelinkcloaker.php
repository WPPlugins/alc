<?php
/*
Plugin Name:  Affiliate Link Cloaker
Plugin URI:  http://www.brewsterware.com/affiliate-link-cloaker-wordpress-plugin.html
Version: 1.00.05
Description: Generates geo targetted cloaked affiliate links. Links can either be placed manually in the post/page content, or if a keyword/phrase is specified links will be inserted automatically.
Author: Joe Brewer
Author URI:  http://www.brewsterware.com/
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define('ALC_VERSION', '1.00.05');

// constants for the flag field in the link table
define('ALC_ACTIVE',         0x0001);
define('ALC_NOFOLLOW',       0x0002);
define('ALC_OPENNEWWINDOW',  0x0004);
define('ALC_COUNTREDIRECTS', 0x0008);
define('ALC_CASESENSITIVE',  0x0010);

define('ALC_SHOWTHISMONTHSTATS',  0x0020);

define('ALC_DEFAULTURLTRIGGER', 'recommends');

global $alc_db_version;
$alc_db_version = 3;

require_once('links.php');
require_once('redirect.php');
require_once('interface.php');
require_once('debug.php');

register_activation_hook(__FILE__, 'alc_activate');
add_action('wpmu_new_blog', 'alc_new_blog', 10, 6);

function alc_upgrade_check() {
	$update_option = (is_multisite()) ? update_site_option : update_option;
	$get_option = (is_multisite()) ? get_site_option : get_option;

	if ($get_option('ALC_VERSION') == '') {
		$update_option('ALC_VERSION', ALC_VERSION);
	}
	
	if ($get_option('ALC_VERSION') < ALC_VERSION) {
		$activeNetworkWide = is_plugin_active_for_network('affiliatelinkcloaker/affiliatelinkcloaker.php');
		
		alc_activate($activeNetworkWide);
	
		$update_option('ALC_VERSION', ALC_VERSION);
	}
}
alc_upgrade_check();

function alc_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
    global $wpdb;
	
    if (is_plugin_active_for_network('affiliatelinkcloaker/affiliatelinkcloaker.php')) {
        switch_to_blog($blog_id);
        _alc_activate();
        
		restore_current_blog();
    }
}

function alc_activate($networkwide) {
    global $wpdb;
	
    if (function_exists('is_multisite') &&
		is_multisite()) {
        // check if it is a network activation - if so, run the activation function for each blog id
        if ($networkwide) {
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                _alc_activate();
            }
			
            restore_current_blog();
            return;
        }
		
		_alc_activate();
    } else {
		// not a multisite....
		_alc_activate();
	}
}

function _alc_activate() {
    global $wpdb;
	
	$table_name = $wpdb->prefix . 'alc_link';
	create_links_table($table_name);

	$table_name = $wpdb->prefix . 'alc_address';
	create_addresses_table($table_name);
	
	$table_name = $wpdb->prefix . 'alc_redirectlog';
	create_redirects_table($table_name);
	
	// do we need to add/set the url trigger ?
	if (!get_option('alc_url_trigger')) {
		add_option('alc_url_trigger', ALC_DEFAULTURLTRIGGER);
	}
}

function create_links_table($table_name) {
    global $wpdb;
	
    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}
	
    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
	}
	
	$sql = "CREATE TABLE {$table_name} (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`redirectType` enum('300','301','302','303','307') NOT NULL default '302',
			`maxReplacements` smallint(5) unsigned NOT NULL default '0',
			`flags` tinyint(4) NOT NULL,
			`titleText` varchar(128) NOT NULL,
			`searchText` varchar(128) NOT NULL,
			`searchTextDelimiter` char(1) NULL,
			`urlSuffix` varchar(128) NOT NULL,
			`replacementOrder` int(11) NOT NULL default '0',
			`cssClass` varchar(32) NOT NULL,
			`pageExceptions` varchar(128) NOT NULL,
			`postExceptions` varchar(128) NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `urlSuffix` (`urlSuffix`)
			) ENGINE=MyISAM {$charset_collate} {$charset_collate} ;";
	
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_addresses_table($table_name) {
    global $wpdb;
	
    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}
	
    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
	}
	
	$sql = "CREATE TABLE {$table_name} (
			`id` int(10) unsigned NOT NULL auto_increment,
			`linkId` int(10) unsigned NOT NULL,
			`default` enum('Yes','No') NOT NULL default 'No',
			`country` varchar(2) NOT NULL,
			`address` varchar(256) NOT NULL,
			PRIMARY KEY (`id`),
			KEY `linkId` (`linkId`)
			) ENGINE=MyISAM {$charset_collate} {$charset_collate} ;";
	
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_redirects_table($table_name) {
    global $wpdb;
	
    if (!empty($wpdb->charset)) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}
	
    if (!empty($wpdb->collate)) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
	}
	
	$sql = "CREATE TABLE {$table_name} (
			`addressId` int(10) unsigned NOT NULL,
			`redirectDateTime` datetime NOT NULL,
			INDEX `addressId` (`addressId`)
			) ENGINE=MyISAM {$charset_collate} {$charset_collate} ;";
	
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

?>