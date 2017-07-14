<?php

add_action('wp_loaded', 'alcRedirect');

require_once('affiliatelinkcloaker.php');

function alcRedirect() {
	global $wpdb, $table_prefix;
	
	// global from the region detect plugin
	global $rdISO3166_1;
	
	// if cloudflare is being used, override the country code
	if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
		switch ($_SERVER["HTTP_CF_IPCOUNTRY"]) {
			case 'XX':
				$rdISO3166_1 = $_SERVER["HTTP_CF_IPCOUNTRY"];
				
				// get default from region detect plugin
				if (is_plugin_active('regiondetect/regiondetect.php')) {
					if (is_multisite()) {
						$rdISO3166_1 = get_site_option('rd_defaultcountrycode');
					} else {
						$rdISO3166_1 = get_option('rd_defaultcountrycode');
					}
				}
				
				break;
				
			default:
				$rdISO3166_1 = $_SERVER["HTTP_CF_IPCOUNTRY"];
		}
	}

	// get the request uri
	if (isset($_SERVER['REQUEST_URI'])) {
		$request = $_SERVER['REQUEST_URI'];
	} else {
		if (isset($_SERVER['QUERY_STRING']) &&
			$_SERVER['QUERY_STRING'] != '') {
			$request = $_SERVER['QUERY_STRING'];
		}
	}

	$url_trigger = get_option("alc_url_trigger");

	if ($url_trigger == '') {
		$url_trigger = ALC_DEFAULTURLTRIGGER;
	}
	
	// has the url trigger been found in the request uri ?
	if (strpos("$request", "/$url_trigger/") !== false) {
		// grab the key which is after the url trigger
		$urlSuffix = explode($url_trigger . '/', $request);
		$urlSuffix = $urlSuffix[1];
		
		// remove forward slashes
		$urlSuffix = str_replace('/', '', $urlSuffix);
		
		$urlSuffix = $wpdb->escape($urlSuffix);
		$query = "	SELECT
							alc_link.flags,
							alc_link.redirectType,
							alc_default.id
						AS	defaultid,
							alc_default.address
						AS	defaultaddress,
							alc_country.id
						AS	countryid,
							alc_country.address
						AS	countryaddress
					FROM
							{$table_prefix}alc_link
						AS	alc_link
					LEFT OUTER JOIN
							{$table_prefix}alc_address
						AS	alc_default
						ON	(alc_default.linkId = alc_link.id)
						AND	(alc_default.default = 'Yes')
					LEFT OUTER JOIN
							{$table_prefix}alc_address
						AS	alc_country
						ON	(alc_country.linkId = alc_link.id)
						AND	(alc_country.country = '$rdISO3166_1')
					WHERE
							(alc_link.urlSuffix = '$urlSuffix')
					LIMIT
							1";
		$result = $wpdb->get_row($query, OBJECT);
		$addressId = 0;
		
		// did we find a country record?
		if ($result->countryid != null &&
			strlen($result->countryaddress)) {
			// indeed :-)
			$redirectUrl = $result->countryaddress;
			
			$addressId = $result->countryid;
		} else {
			// double check that we have a default address
			if ($result->defaultid != null &&
				strlen($result->defaultaddress)) {
				$redirectUrl = $result->defaultaddress;
			
				$addressId = $result->defaultid;
			} else {
				/* TODO: have a coronary...... maybe later
				 * for now, just redirect to the default site address
				 */
				$redirectUrl = get_option('siteurl');
			}
		}
		
		// update the redirection count ?
		if ((($result->flags & ALC_COUNTREDIRECTS) == ALC_COUNTREDIRECTS) &&
			$addressId) {
			$query = "	INSERT INTO
								{$table_prefix}alc_redirectlog
						(addressId, redirectDateTime)
						VALUES
						($addressId, NOW())";
			$wpdb->query($query);
		}
		
		// redirect the user
		wp_redirect($redirectUrl, $result->redirectType);
		exit;
	}
}

?>