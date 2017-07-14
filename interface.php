<?php

require_once('graphs.php');

add_action('admin_menu', 'add_alc_menu');
add_action('init', 'alc_init');

function add_alc_menu()
{
	add_submenu_page('options-general.php', 'Link Cloaker Options', 'Link Cloaker', 8, 'affiliatelinkcloaker', 'links_management_panel');
}

function alc_init()
{
	if (is_admin()) {
		wp_enqueue_script('', 'https://www.google.com/jsapi');
	}
}

function links_management_panel() {
	if (isset($_GET['edit']) &&
		isset($_GET['new']) &&
		!isset($_GET['debug'])) {
		editlink('new');
	}
	
	if (isset($_GET['edit']) &&
		!isset($_GET['new']) &&
		!isset($_GET['debug'])) {
		$linkId = (int)$_GET['edit'];
		
		editlink('edit', $linkId);
	}
	
	if (!isset($_GET['new']) &&
		!isset($_GET['edit']) &&
		!isset($_GET['debug'])) {
		show_links();
	}
	
	if (isset($_GET['edit']) &&
		!isset($_GET['new']) &&
		isset($_GET['debug'])) {
		$linkId = (int)$_GET['edit'];
		
		debuglink($linkId);
	}
}


function show_links() {
	global $wpdb, $table_prefix;
	$message = '';
	$errors = array();
	
	// url trigger updated ?
	if (isset($_POST['url_trigger']) &&
		strlen($_POST['url_trigger']) &&
		($_POST['url_trigger'] != get_option('alc_url_trigger')) &&
		check_admin_referer('alc_trigger', '_wpnonce')) {
		update_option('alc_url_trigger', $_POST['url_trigger']);
		
		$message = 'URL trigger updated.';
	}
	
	// delete affiliate links ?
	if (isset($_POST['links']) &&
		check_admin_referer('alc_delete_links', '_wpnonce')) {
		$query = "	DELETE
					FROM
							{$table_prefix}alc_link
					WHERE
							{$table_prefix}alc_link.id in (";
		
		// sanitise and add the ids to the query
		foreach ($_POST['links'] as $link) {
			$query .= (int)$link . ',';
		}
		
		// chop the last comma off
		$query = rtrim($query, ',');
		
		// finish off the query
		$query .= ')';

		// run the query :-)
		$wpdb->query($query);
		
		// remove orphaned address records
		$query = "	DELETE
							{$table_prefix}alc_address
					FROM
							{$table_prefix}alc_address
					LEFT OUTER JOIN
							{$table_prefix}alc_link
						ON	({$table_prefix}alc_link.id = {$table_prefix}alc_address.linkId)
					WHERE
							({$table_prefix}alc_link.id IS NULL)";
		$wpdb->query($query);
		
		// finally, remove orphaned log records
		$query = "	DELETE
							{$table_prefix}alc_redirectlog
					FROM
							{$table_prefix}alc_redirectlog
					LEFT OUTER JOIN
							{$table_prefix}alc_address
						ON	({$table_prefix}alc_address.id = {$table_prefix}alc_redirectlog.addressId)
					WHERE
							({$table_prefix}alc_address.id IS NULL)";
		$wpdb->query($query);
		
		$message = 'Affiliate link(s) removed.';
	}
	
?>
<div class="wrap">
<h2>Affiliate links <a href="<?php echo get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&edit&new'; ?>" class="button add-new-h2">Add New</a></h2>
<?php

	if ($message) {
		echo '<div class="message updated" style="width: 97%;" id="message"><p><strong>' . $message . '</strong></p></div>';
	}
	
	if (sizeof($errors)) {
		echo '<div class="message error" style="width: 97%;" id="message"><p><strong>' . implode('<br />', $errors) . '</strong></p></div>';
	}

?>

<div style="width:100%;" class="postbox-container">
<div class="metabox-holder">
<div class="postbox" style="width:100%;">
	<h3 class="hndle">Affiliate URL Trigger</h3>
	<form method="post">
	<?php wp_nonce_field('alc_trigger', '_wpnonce'); ?>
	<table style="margin:4px;" class="form-table">
	<tr>
		<td><input type="text" name="url_trigger" size="20" value="<?php  echo get_option('alc_url_trigger'); ?>"></td>
		<td><strong>URL Trigger</strong><br /><em>(Default: <strong>recommends</strong>) </em>
		</td>
		<td width="70%" rowspan="2">
		<table>
			<tr>
			<td><p>Affiliate link cloaker is free - wahay!. Please support it with one of the options below - I would really appreciate it :-)</p>
			<ol><li style="padding-bottom:0px;margin-bottom:1px;">Vote for the <a href="http://wordpress.org/extend/plugins/alc/" target="_blank">affiliate link cloaker</a> on the wordpress plugin site</li>
			<li style="padding-bottom:0px;margin-bottom:1px;">Leave a comment on my <a href="http://www.brewsterware.com/" target="_blank">blog</a> or <a href="http://forums.brewsterware.com/" target="_blank">support forums</a> (and/or leave a suggestion for a new feature)</li>
			<li style="padding-bottom:0px;margin-bottom:1px;">Use my <a href="http://wordpress.org/plugins/regiondetect/" target="_blank">Region detect</a> plugin to geo target your affiliate links</li>
			<li style="padding-bottom:0px;margin-bottom:1px;">Make a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=Z2M2DB4892LBJ" target="_blank">paypal donation</a> (no amount is too small)</li>
			<li>Buy something from my <a href="http://www.brewsterware.com/amazon-wish-list-2" target="_blank">amazon wishlist</a> for me</li>
			</ol>
			</td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td></td>
		<td><input class="button-primary" type="submit" name="go" value="Save"></td>
	</tr>
	</table>
	</form>
</div>
</div>
</div>

<form method="post" action="<?php echo get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker'; ?>">
<?php wp_nonce_field('alc_delete_links', '_wpnonce'); ?>
<table class="widefat">
	<thead>
	<tr>
	<th scope="col" id="title" class="manage-column" style="width:20px"></th>
	<th scope="col" id="title" class="manage-column" style="width:20px"></th>
	<th scope="col" id="title" class="manage-column" style="width:50px">Order</th>
	<th scope="col" id="title" class="manage-column" style="width:50px">Active</th>
	<th scope="col" id="title" class="manage-column" style="width:50px">Max Links</th>
	<th scope="col" id="author" class="manage-column" style="width:130px;">Search Text</th>
	<th scope="col" id="tags" class="manage-column" style="width:130px;">URL Suffix</th>
	<th scope="col" id="comments" class="manage-column" style="width:55px;">nofollow</th>
	<th scope="col" id="comments" class="manage-column" style="width:50px;">new<br />window</th>
	<th scope="col" id="comments" class="manage-column" style="width:60px;">addresses</th>
	<th scope="col" id="comments" class="manage-column" style="width:60px;">redirects</th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col" class="manage-column"></th>
	<th scope="col" class="manage-column"></th>
	<th scope="col" class="manage-column">Order</th>
	<th scope="col" class="manage-column">Active</th>
	<th scope="col" class="manage-column">Max Links</th>
	<th scope="col" class="manage-column">Search Text</th>
	<th scope="col" class="manage-column">URL Suffix</th>
	<th scope="col" class="manage-column">nofollow</th>
	<th scope="col" class="manage-column">new<br />window</th>
	<th scope="col" class="manage-column">addresses</th>
	<th scope="col" class="manage-column">redirects</th>
	</tr>
	</tfoot>
<tbody>
<?php

	$url_trigger = get_option("alc_url_trigger");
	$site_url = get_option('siteurl');

	$query = "	SELECT
						alc_link.id,
						alc_link.replacementOrder,
						alc_link.flags,
						alc_link.maxReplacements,
						alc_link.searchText,
						alc_link.urlSuffix,
						IFNULL(alc_addresscount.addresses, 0)
					AS	addresses,
						(CASE
							WHEN alc_defaultaddress.id IS NULL
							THEN 0
							ELSE 1
						 END)
					AS	hasDefault,
						COUNT(alc_redirectlog.addressId)
					AS	redirects
				FROM
						{$table_prefix}alc_link
					AS	alc_link
								LEFT OUTER JOIN
						{$table_prefix}alc_address
					AS	alc_address
					ON	(alc_address.linkId = alc_link.id)
					AND	(LENGTH(alc_address.address) > 5)
				LEFT OUTER JOIN
						(SELECT
								linkId,
								COUNT(id)
							AS	addresses
						 FROM
								{$table_prefix}alc_address
							AS	alc_address
						 GROUP BY
								alc_address.linkId)
					AS	alc_addresscount
					ON	(alc_addresscount.linkId = alc_link.id)
				LEFT OUTER JOIN
						{$table_prefix}alc_address
					AS	alc_defaultaddress
					ON	(alc_defaultaddress.linkId = alc_link.id)
					AND	(alc_defaultaddress.default = 'Yes')
				LEFT OUTER JOIN
						{$table_prefix}alc_redirectlog
					AS	alc_redirectlog
					ON	(alc_redirectlog.addressId = alc_address.id)
				GROUP BY
						alc_link.id
				ORDER BY
						alc_link.replacementOrder ASC";
	$rows = $wpdb->get_results($query);
	
	foreach ($rows as $row) {
		$background = '';
		if (!$row->addresses ||
			!$row->hasDefault) {
			$background = "background: #FAEBD7;";
		}
?>
	<tr class='' valign="top">
		<th style="<?php echo $background; ?>" scope="row"><input name="links[]" value="<?php echo $row->id; ?>" type="checkbox"></th>
		<td style="text-align:left;<?php echo $background; ?>"><a title="Edit Affiliate Link" href="<?php echo 'admin.php?page=affiliatelinkcloaker&edit=' . $row->id; ?>">Edit</a></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo $row->replacementOrder; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo (($row->flags & ALC_ACTIVE) == ALC_ACTIVE) ? 'Yes' : 'No'; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo $row->maxReplacements; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo $row->searchText; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><span title="<?php echo "$site_url/$url_trigger/" . $row->urlSuffix; ?>"><?php echo $row->urlSuffix; ?></span></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo (($row->flags & ALC_NOFOLLOW) == ALC_NOFOLLOW) ? 'Yes' : 'No'; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo (($row->flags & ALC_OPENNEWWINDOW) == ALC_OPENNEWWINDOW) ? 'Yes' : 'No'; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo $row->addresses; ?></td>
		<td style="text-align:left;<?php echo $background; ?>"><?php echo $row->redirects; ?></td>
	</tr>
<?php
	}
?>
</tbody>
</table>

<table class="widefat post fixed" cellspacing="0">
	<thead>
	<tr>
	<td><div class="submit"><input type="submit" name="deleteselected" value="Delete Selected" /></div>
	</td>
	</tr>
</table>
</form>

</div>
<?php

}


function editlink($command = 'new', $linkId = 0) {
	global $wpdb, $table_prefix;
	$errors = array();
	$message = '';
	
	// initialise variables with default values
	$id = 0;
	$flags |= ALC_CASESENSITIVE;
	$searchText = '';
	$searchTextDelimiter = '';
	$urlSuffix = '';
	$replacementOrder = 0;
	$maxReplacements = 0;
	$redirectType = '302';
	
	// delete an address?
	if (isset($_POST['delete_address'])) {
		$addressId = (int)$_POST['delete_address'];
		
		// delete the address
		$wpdb->query("DELETE FROM {$table_prefix}alc_address WHERE id = $addressId");
		
		// and now delete any entries in the redirect log table..
		$wpdb->query("DELETE FROM {$table_prefix}alc_redirectlog WHERE addressId = $addressId");
		
		$message = 'Address deleted.';
	}
	
	// update an address?
	if (isset($_POST['update_address']) &&
		check_admin_referer('alc_edit_address', '_wpnonce')) {
		// is this a default link ?
		if (isset($_POST['default'])) {
			// make sure that we dont end up with more than one default
			$wpdb->update($table_prefix . 'alc_address',
							array('default' => 'No'),
							array('linkId' => (int)$linkId));
		}
		
		// prepare the data
		$where = array('id' => (int)$_POST['update_address']);
		$data = array('default' => isset($_POST['default']) ? 'Yes' : 'No',
						'country' => $_POST['country'],
						'address' => $_POST['address']);
		
		// update the record
		$wpdb->update($table_prefix . 'alc_address', $data, $where, array('%s'), array('%d'));
		
		$message = 'Address updated.';
	}
	
	// create a new record ?
	if (isset($_POST['command']) &&
		$_POST['command'] == 'new') {
		// validate data....
		if (!strlen($_POST['urlsuffix'])) {
			$errors[] = 'Please provide a url suffix.';
		}
		
		// check that the url suffix is unique
		if (!isSuffixUnique($_POST['urlsuffix'])) {
			$errors[] = 'Please provide a unique suffix.';
		}
		
		// any errors ?
		if (!sizeof($errors)) {
			$flags = 0;
			
			//sanitise data
			if (isset($_POST['replacementactive'])) {
				$flags |= ALC_ACTIVE;
			}
			
			if (isset($_POST['countredirects'])) {
				$flags |= ALC_COUNTREDIRECTS;
			}
			
			if (isset($_POST['nofollow'])) {
				$flags |= ALC_NOFOLLOW;
			}
			
			if (isset($_POST['opennewwindow'])) {
				$flags |= ALC_OPENNEWWINDOW;
			}
			
			if (isset($_POST['casesensitive'])) {
				$flags |= ALC_CASESENSITIVE;
			}
			
			if (isset($_POST['showthismonthstats'])) {
				$flags |= ALC_SHOWTHISMONTHSTATS;
			}
			
			$titleText = $wpdb->escape($_POST['titletext']);
			$searchText = $wpdb->escape($_POST['searchtext']);
			$searchTextDelimiter = $wpdb->escape($_POST['searchtextdelimiter']);
			$urlSuffix = $wpdb->escape($_POST['urlsuffix']);
			$replacementOrder = (int)$_POST['replacementorder'];
			$maxReplacements = (int)$_POST['maxreplacements'];
			$redirectType = $_POST['redirecttype'];
			
			$data = array(
						'flags' => $flags,
						'titleText' => $titleText,
						'searchText' => $searchText,
						'searchTextDelimiter' => $searchTextDelimiter,
						'urlSuffix' => $urlSuffix,
						'replacementOrder' => $replacementOrder,
						'maxReplacements' => $maxReplacements,
						'redirectType' => $redirectType
			);
			
			// perform the insert
			$wpdb->insert($table_prefix . 'alc_link', $data);
			
			$command = 'edit';
			$linkId = $wpdb->insert_id;
			
			$message = 'New affiliate link added.';
		}
	}
	
	// update record ?
	if (isset($_POST['command']) &&
		$_POST['command'] == 'edit' &&
		check_admin_referer('alc_link_options', '_wpnonce') &&
		$linkId) {
		// validate data....
		if (!strlen($_POST['urlsuffix'])) {
			$errors[] = 'Please provide a url suffix.';
		}
		
		// check that the url suffix is unique
		if (!isSuffixUnique($_POST['urlsuffix'], $linkId)) {
			$errors[] = 'Please provide a unique suffix.';
		}
		
		// any errors ?
		if (!sizeof($errors)) {
			$flags = 0;
			
			//sanitise data
			if (isset($_POST['replacementactive'])) {
				$flags |= ALC_ACTIVE;
			}
			
			if (isset($_POST['countredirects'])) {
				$flags |= ALC_COUNTREDIRECTS;
			}
			
			if (isset($_POST['nofollow'])) {
				$flags |= ALC_NOFOLLOW;
			}
			
			if (isset($_POST['opennewwindow'])) {
				$flags |= ALC_OPENNEWWINDOW;
			}
			
			if (isset($_POST['casesensitive'])) {
				$flags |= ALC_CASESENSITIVE;
			}
			
			if (isset($_POST['showthismonthstats'])) {
				$flags |= ALC_SHOWTHISMONTHSTATS;
			}
			
			$titleText = $wpdb->escape($_POST['titletext']);
			$searchText = $wpdb->escape($_POST['searchtext']);
			$searchTextDelimiter = $wpdb->escape($_POST['searchtextdelimiter']);
			$urlSuffix = $wpdb->escape($_POST['urlsuffix']);
			$cssClass = $wpdb->escape($_POST['cssclass']);
			$pageExceptions = $wpdb->escape($_POST['pageexceptions']);
			$postExceptions = $wpdb->escape($_POST['postexceptions']);
			$replacementOrder = (int)$_POST['replacementorder'];
			$maxReplacements = (int)$_POST['maxreplacements'];
			$redirectType = $_POST['redirecttype'];
			
			$data = array(
						'flags' => $flags,
						'titleText' => $titleText,
						'searchText' => $searchText,
						'searchTextDelimiter' => $searchTextDelimiter,
						'urlSuffix' => $urlSuffix,
						'cssClass' => $cssClass,
						'pageExceptions' => $pageExceptions,
						'postExceptions' => $postExceptions,
						'replacementOrder' => $replacementOrder,
						'maxReplacements' => $maxReplacements,
						'redirectType' => $redirectType
			);
			
			$where = array('id' => $linkId);
			
			// perform the insert
			$wpdb->update($table_prefix . 'alc_link', $data, $where);
			
			// if the redirect count check box is not checked, remove log entries
			if (!isset($_POST['countredirects'])) {
				$query = "	DELETE
									{$table_prefix}alc_redirectlog
							FROM
									{$table_prefix}alc_redirectlog
							INNER JOIN
									{$table_prefix}alc_address
								ON	({$table_prefix}alc_address.id = {$table_prefix}alc_redirectlog.addressId)
							WHERE
									({$table_prefix}alc_address.linkId = $linkId)";
				$wpdb->query($query);
			}

			$message = 'Affiliate link updated.';
		}
	}
	
	if ($command == 'edit' &&
		$linkId) {
		$posturl = get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&edit=' . $linkId;
		
		$query = "	SELECT
							id,
							flags,
							searchText,
							searchTextDelimiter,
							urlSuffix,
							cssClass,
							pageExceptions,
							postExceptions,
							replacementOrder,
							maxReplacements,
							redirectType
					FROM
							{$table_prefix}alc_link
					WHERE
							id = $linkId";
		$row = $wpdb->get_row($query);
		
		$id = $row->id;
		$flags = $row->flags;
		$searchText = $row->searchText;
		$searchTextDelimiter = $row->searchTextDelimiter;
		$urlSuffix = $row->urlSuffix;
		$cssClass = $row->cssClass;
		$pageExceptions = $row->pageExceptions;
		$postExceptions = $row->postExceptions;
		$replacementOrder = $row->replacementOrder;
		$maxReplacements = $row->maxReplacements;
		$redirectType = $row->redirectType;
	}
	
	if ($command == 'new') {
		$posturl = get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&edit&new';
	}

	// has the user created a new link record?
	if ($linkId) {
		$posturl = get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&edit=' . $linkId;
	}
	
	// validate address stuff
	if ($linkId) {
		$query = "	SELECT
							COUNT(alc_address.id)
						AS	addresses,
							(CASE
								WHEN alc_defaultaddress.id IS NULL
								THEN 0
								ELSE 1
							 END)
						AS	defaultAddress
					FROM
							{$table_prefix}alc_link
						AS	alc_link
					LEFT OUTER JOIN
							{$table_prefix}alc_address
						AS	alc_address
						ON	(alc_address.linkId = alc_link.id)
					LEFT OUTER JOIN
							{$table_prefix}alc_address
						AS	alc_defaultaddress
						ON	(alc_defaultaddress.linkId = alc_link.id)
						AND	(alc_defaultaddress.default = 'Yes')
					WHERE
							alc_link.id = $linkId
					GROUP BY
							alc_link.id";
		$result = $wpdb->get_row($query);
		
		// have we got some addresses?
		if (($linkId == 0 &&
			$command == 'new') ||
			!$result->addresses) {
			$errors[] = "Please add at least one address.";
		}
		
		// do we have at least one default address?
		if ($command == 'new' ||
			!$result->defaultAddress) {
			$errors[] = "Please make one of the addresses a default";
		}
	} else {
		/* linkId must be zero if we're here. check if this is
		 * for a new link...
		 */
		if ($command == 'new') {
			$errors[] = "Once you have saved this link, you will need to add at least one default address. Addresses can be added below the options once the link options have been saved.";
		}
	}
	
?>
<div class="wrap">
  <form action="<?php echo $posturl; ?>" method="post">
  <input type="hidden" name="command" value="<?php echo $command; ?>" />
	<h2>Affiliate Link Options <a style="font-size:55%;" href="<?php echo get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker'; ?>">Back to affiliate links</a></h2>
<?php

	wp_nonce_field('alc_link_options', '_wpnonce');

	if ($message) {
		echo '<div class="message updated" style="width: 97%;" id="message"><p><strong>' . $message . '</strong></p></div>';
	}
	
	if (sizeof($errors)) {
		echo '<div class="message error" style="width: 97%;" id="message"><p><strong>' . implode('<br />', $errors) . '</strong></p></div>';
	}

?>
	<div class="postbox-container">
	<div class="metabox-holder">

	<div style="margin-right:100%;width:100%" class="postbox">
	<h3 class="hndle">Options</h3>
	<table width="100%" style="margin:8px;" class="form-table">
	
<tr valign="top">
	<th scope="row" style="text-align:left;"><a href="<?php echo get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&debug=1&edit=' . (int)$linkId; ?>">Debug</a></th>
	<td>Need some help with this link? Click on the debug link to the left. This will download a text file with the configuration of the link and addresses. Copy and paste the contents of the file with your specific issue at <a href="http://forums.brewsterware.com" target="_blank">Brewsterware forum</a></td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="checkbox" id="replacementactive" name="replacementactive" <?php echo (($flags & ALC_ACTIVE) == ALC_ACTIVE) ? 'checked="checked"' : ''; ?> /></th>
	<td><label style="font-weight: bold;" for="replacementactive">Active</label><br />This enables the replacement of text in content with the cloaked affiliate links.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="pageexceptions" size="20" value="<?php echo htmlentities($pageExceptions); ?>" /></td>
	<td><strong>Page exceptions</strong><br />Enter a list of page ids separated by commas of pages that you do not want to add affiliate links to.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="postexceptions" size="20" value="<?php echo htmlentities($postExceptions); ?>" /></td>
	<td><strong>Post exceptions</strong><br />Enter a list of post ids separated by commas of posts that you do not want to add affiliate links to.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="replacementorder" size="2" value="<?php echo (int)$replacementOrder; ?>" /></th>
	<td><strong>Replacement Order</strong><br />This is the order that the replacements are done.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="maxreplacements" size="2" value="<?php echo (int)$maxReplacements; ?>" /></th>
	<td><strong>Max Replacements</strong><br />Maximum number of replacements per post. Setting this to zero will cause all keyword occurances to be replaced.</td>
</tr>
<tr valign="top"> 
	<th scope="row" style="text-align:left;"><input type="text" name="searchtext" size="20" value="<?php echo htmlentities($searchText); ?>" /></th>
	<td><strong>Search Text</strong><br />This is the text that is searched for in the article content. Seperate multiple keywords/phrases with the delimeter specified below.</td>
</tr>
<tr valign="top"> 
	<th scope="row" style="text-align:left;"><input type="text" name="searchtextdelimiter" size="1" maxlength="1" value="<?php echo $searchTextDelimiter; ?>" /></th>
	<td><strong>Search Text Delimiter</strong><br />This is the delimiter that seperates multiple keywords. This should be left empty if you are only specifying one keyword/phrase above.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="checkbox" id="casesensitive" name="casesensitive" <?php echo (($flags & ALC_CASESENSITIVE) == ALC_CASESENSITIVE) ? 'checked="checked"' : ''; ?> /></td>
	<td><label style="font-weight: bold;" for="casesensitive">Case sensitive searching</label><br />This defines whether the searching of the keyword/phrase should be case sensitive. This defaults to true.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="urlsuffix" size="20" value="<?php echo htmlentities($urlSuffix); ?>" /></td>
	<td><strong>Cloaked URL suffix</strong><br />This text will be placed at the end of the cloaked url like this: <?php echo get_option('siteurl'); ?>/recommends/<strong>cloaked_url_suffix</strong>. This suffix must be unique.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="text" name="cssclass" size="20" value="<?php echo htmlentities($cssClass); ?>" /></td>
	<td><strong>CSS Class</strong><br />The link will use this css class.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="checkbox" id="countredirects" name="countredirects" <?php echo (($flags & ALC_COUNTREDIRECTS) == ALC_COUNTREDIRECTS) ? 'checked="checked"' : ''; ?> /></td>
	<td><label style="font-weight: bold;" for="countredirects">Count Redirects</label><br />This enables the counting of redirects that are performed.<br /><input type="checkbox" id="showthismonthstats" name="showthismonthstats" <?php echo (($flags & ALC_SHOWTHISMONTHSTATS) == ALC_SHOWTHISMONTHSTATS) ? 'checked="checked"' : ''; ?> /> <label for="showthismonthstats">Show graph for this months stats - switching this on may slow the loading of this page (but it is quite a cool feature).</label>
	</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="checkbox" id="nofollow" name="nofollow" <?php echo (($flags & ALC_NOFOLLOW) == ALC_NOFOLLOW) ? 'checked="checked"' : ''; ?> /></td>
	<td><label style="font-weight: bold;" for="nofollow">Nofollow Link</label><br />This puts a no follow tag on the link.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><input type="checkbox" id="opennewwindow" name="opennewwindow" <?php echo (($flags & ALC_OPENNEWWINDOW) == ALC_OPENNEWWINDOW) ? 'checked="checked"' : ''; ?> /></td>
	<td><label style="font-weight: bold;" for="opennewwindow">Open link in new window</label><br />This causes the link to open in a new browser window.</td>
</tr>
<tr valign="top">
	<th scope="row" style="text-align:left;"><?php redirectTypeCombo('redirecttype', $redirectType); ?></td>
	<td><label style="font-weight: bold;" for="redirecttype">Redirect type</label><br />These are http status codes - documentation for them can be found <a target="_blank" href="http://en.wikipedia.org/wiki/URL_redirection">here</a>. This will default to a 302 redirect. If this is set to a 301 redirect, most modern browsers will cache the redirect and any changes to the addresses below will not show for a specific user if they have already followed the link.</td>
</tr>
<tr valign="top">
	<th scope="row"></td>
	<td><input class="button-primary" name="go" value="Save" type="submit"><br /><br /></td>
</tr>

	</table>
	</div>

</div>
</div>
</form>

<?php
	if ($linkId) {
		addAffiliateLinks($linkId, $flags);
	}
?>

<div class="postbox-container">
	<div class="metabox-holder">
	<div style="margin-right:100%;width:100%" class="postbox">
	<h3 class="hndle">Cloudflare support</h3>
	<table width="100%" style="margin:8px;" class="form-table">
	<tr valign="top">
	<td>Affiliate link cloaker now uses <a href="http://www.cloudflare.com" target="_blank">cloudflare</a> to geo target it's links. Install the <a href="http://wordpress.org/plugins/regiondetect/" target="_blank">region detect</a> plugin to set a default country if cloud flare cannot determin the country. The region detect plugin will also generate a dropdown list of countries for each address. Cloud flare will automatically be detected and used - no need to switch it on in the plugin. It will override any country detected by region detect.</td>
</tr>
</table>
</div>
</div>
</div>
</div>

<?php	
}


function addAffiliateLinks($linkId, $flags) {
	global $wpdb, $table_prefix;
	
	$posturl = get_option('siteurl') . "/wp-admin/admin.php?page=affiliatelinkcloaker&edit=$linkId#links";

?>
<h2>Addresses <a href="<?php echo get_option('siteurl') . "/wp-admin/admin.php?page=affiliatelinkcloaker&edit=$linkId&newaffiliatelink#links"; ?>" class="button add-new-h2">Add New</a></h2>
<?php

	// add a new affiliate link ?
	if (isset($_GET['newaffiliatelink'])) {
		// insert a blank record...
		$data = array('linkId' => $linkId);
		
		$wpdb->insert($table_prefix . 'alc_address', $data, '%d');
	}
?>
<table id="links" class="widefat">
	<thead>
	<tr>
	<th scope="col" id="title" class="manage-column" style="width:10px">Address</th>
	<th scope="col" id="title" class="manage-column" style="width:10px">Default</th>
	<th scope="col" id="title" class="manage-column" style="width:50px">Country</th>
	<th scope="col" id="title" class="manage-column" style="width:10px">Redirects</th>
	<th scope="col" id="title" class="manage-column" style="width:20px"></th>
	<th scope="col" id="title" class="manage-column" style="width:20px"></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col" class="manage-column">Affiliate Link</th>
	<th scope="col" class="manage-column">Default</th>
	<th scope="col" class="manage-column">Country</th>
	<th scope="col" class="manage-column">Redirects</th>
	<th scope="col" class="manage-column"></th>
	<th scope="col" class="manage-column"></th>
	</tr>
	</tfoot>
<tbody>
<?php
	$query = "	SELECT
						alc_address.id,
						alc_address.address,
						alc_address.country,
						alc_address.default,
						COUNT(alc_redirectlog.addressId)
					AS	redirects
				FROM
						{$table_prefix}alc_address
					AS	alc_address
				LEFT OUTER JOIN
						{$table_prefix}alc_redirectlog
					AS	alc_redirectlog
					ON	(alc_redirectlog.addressId = alc_address.id)
				WHERE
						(alc_address.linkId = $linkId)
				GROUP BY
						alc_address.id";
	$rows = $wpdb->get_results($query);
	
	$forceDefault = (sizeof($rows) == 1);
	
	foreach ($rows as $row) {
?>
	<tr class='' valign="top">
		<form action="<?php echo $posturl; ?>" method="post">
		<?php wp_nonce_field('alc_edit_address', '_wpnonce'); ?>
		<input type="hidden" name="update_address" value="<?php echo $row->id; ?>" />
		<td style="text-align:left;"><input type="text" size="70" name="address" value="<?php echo $row->address; ?>" /></td>
		<td style="text-align:left;">
		<input type="checkbox" name="default" <?php echo ($row->default == 'Yes' || $forceDefault) ? 'checked="checked"' : ''; ?> />
		</td>
		<td><?php countrycombo('country', $row->country); ?></td>
		<td><?php echo $row->redirects; ?></td>
		<td><input class="button-primary" type="submit" value="Update" /></td>
		</form>
		<form action="<?php echo $posturl; ?>" method="post">
		<td>
			<input type="hidden" name="delete_address" value="<?php echo $row->id; ?>" />
			<input class="button-primary" type="submit" value="Delete" onclick="return confirm('Press OK to delete address.')" />
		</td>
		</form>
	</tr>
<?php
	}
	
	if (($flags & ALC_SHOWTHISMONTHSTATS) == ALC_SHOWTHISMONTHSTATS) {
		insertGraphs($linkId);
	}
	
?>
</tbody>
</table>
<div id="chart_div"></div>
<?php
}


function isSuffixUnique($suffix, $id = 0) {
	global $wpdb, $table_prefix;
	
	// no suffix, dont bother doing anything
	if (!$suffix) {
		return false;
	}
	
	// prepare the data
	$escapedSuffix = $wpdb->escape($suffix);
	$id = (int)$id;
	
	$query = "	SELECT
						COUNT(id)
					AS	suffixCount
				FROM
						{$table_prefix}alc_link
				WHERE
						(urlSuffix = '$escapedSuffix')
					AND	(id <> $id)";
	
	$row = $wpdb->get_row($query);
	
	return ($row->suffixCount == 0) ? true : false;
}


function squashText($text, $maxLength, $seperator, $seperatorPos) {
	$textLen = strlen($text);
	
	// do we need to process the text ?
	if ($textLen < $maxLength) {
		return $text;
	}
	
	$seperatorLen = strlen($seperator);
	
	return substr($text, 0, $textLen - $seperatorLen - $seperatorPos) .
			$seperator . substr($text, $textLen - $seperatorPos);


}

function redirectTypeCombo($name, $default = '') {
	$types = array(0 => '300', '301', '302', '303', '307');
	
	echo "<select name=\"$name\">";
		
	foreach ($types as $type) {
		echo '<option style="padding-right:10px;"';
		
		// a default?
		echo ($type == $default) ? ' selected' : '';
		
		echo ">$type</option>\n";
	}
	
	echo '</select>';

}

function countrycombo($name, $default = '') {
	global $wpdb;
	
	/* if the region detect plugin is not installed
	 * just display a text box
	 */
	 
	if (!is_plugin_active('regiondetect/regiondetect.php')) {
		echo '<input type="text" size="2" name="country" value="' . $default . '" />';
		
		return;
	}
	
	$query = "	SELECT
						countryCode,
						countryName
				FROM
						{$wpdb->base_prefix}rd_countrynames
				ORDER BY
						countryName ASC";
	$rows = $wpdb->get_results($query);
	
	echo "<select name=\"$name\">";
		
	foreach ($rows as $row) {
		echo '<option value="' . $row->countryCode . '"';
		
		// a default?
		echo ($row->countryCode == $default) ? ' selected' : '';
		
		// the text
		echo '>' .  $row->countryName . "</option>\n";
	}
	
	echo '</select>';
}

?>