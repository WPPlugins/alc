<?php

function debuglink($linkId) {
	global $wpdb, $table_prefix;
	
	echo '<div class="wrap"><h2>Affiliate Link debug <a style="font-size:55%;" href="' . get_option('siteurl') . '/wp-admin/admin.php?page=affiliatelinkcloaker&edit=' . (int)$linkId . '">Back</a></h2>';
	
	echo '<div class="postbox-container"><div class="metabox-holder">';
	
	echo "<div><strong>Instructions:</strong><br />If you are having issues with a link, please view the threads over at the <a href=\"http://forums.brewsterware.com\" target=\"_blank\">brewsterware forums</a>. If you do cannot find an answer to your issue, start a new thread a description of the issue, copy the text below in it's entirety and paste it into the thread.<br /><br /></div>";
	
	echo '<textarea rows="30" cols="60">[code]';
	echo "Global parameters\n----------------------------\n";
	echo "URL trigger: " . get_option('alc_url_trigger') . "\n\n";
	
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
	
	echo "Link parameters\n----------------------------\n";
	echo "Active:                  " . ((($flags & ALC_ACTIVE) == ALC_ACTIVE) ? "Yes\n" : "No\n");
	echo "Page exceptions:         $pageExceptions\n";
	echo "Post exceptions:         $postExceptions\n";
	echo "Replacement order:       $replacementOrder\n";
	echo "Maximum replacements:    $maxReplacements\n";
	echo "Search text:             $searchText\n";
	echo "Search text delimiter:   $searchTextDelimiter\n";
	echo "Case sensitive:          " . ((($flags & ALC_CASESENSITIVE) == ALC_CASESENSITIVE) ? "Yes\n" : "No\n");
	echo "URL suffix:              $urlSuffix\n";
	echo "CSS class:               $cssClass\n";
	echo "Count redirects:         " . ((($flags & ALC_COUNTREDIRECTS) == ALC_COUNTREDIRECTS) ? "Yes\n" : "No\n");
	echo "Show this months stats:  " . ((($flags & ALC_SHOWTHISMONTHSTATS) == ALC_SHOWTHISMONTHSTATS) ? "Yes\n" : "No\n");
	echo "No follow links:         " . ((($flags & ALC_NOFOLLOW) == ALC_NOFOLLOW) ? "Yes\n" : "No\n");
	echo "Open link in new window: " . ((($flags & ALC_OPENNEWWINDOW) == ALC_OPENNEWWINDOW) ? "Yes\n" : "No\n");
	echo "Redirect type:           $redirectType\n\n";
	
	
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

	echo "\nLink destinations\n---------------------------------\nDefault Country Redirects Address\n";
	
	foreach ($rows as $row) {
		echo ($row->default == 'Yes') ? 'X       ' : '        ';
		
		echo str_pad($row->country, 2) . '      ';
		echo $row->redirects;
		printf("%-10d", $row->redirects);
		echo $row->address . "\n";
	}
	
	echo "[/code]</textarea></div></div></div>";	
}

?>