<?php

define('ALC_CT_POST', 1);
define('ALC_CT_PAGE', 2);

add_filter('the_content', 'alcInjectPostLinks', 250);

function alcReplaceKeywords($search, $replace, $text, $maxReplacements = 0, $caseSensitive = true) {
	$keys = 0;
	$values = 1;
	
	$hasHeadings = false;
	$hasLinks = false;
	$hasImages = false;
	
	$links = array();
	$headings = array();
	$images = array();
	
	$invalidChars = array_merge(range('a', 'z'), range('A', 'Z'));
	
	$regEx = "#<h.*?</h\d>#mis";
	if (preg_match_all($regEx, $text, $matches)) {
		for ($i = 0 ; $i <= sizeof($matches[0]) - 1 ; $i++) {
			$headings[$values][$i] = "%%%HEADING_{$i}%%%";
			$headings[$keys][$i] = $matches[0][$i];
		}
		
		$text = str_replace($headings[$keys], $headings[$values], $text);
		$hasHeadings = true;
	}
	
	$regEx = "#<img.*?>#mis";
	if (preg_match_all($regEx, $text, $matches)) {
		for ($i = 0 ; $i <= sizeof($matches[0]) - 1 ; $i++) {
			$images[$values][$i] = "%%%IMAGE_{$i}%%%";
			$images[$keys][$i] = $matches[0][$i];
		}
		
		$text = str_replace($images[$keys], $images[$values], $text);
		$hasImages = true;
	}
	
	$regEx = "#<a.*?</a>#mis";
	if (preg_match_all($regEx, $text, $matches)) {
		for ($i = 0 ; $i <= sizeof($matches[0]) - 1 ; $i++) {
			$links[$values][$i] = "%%%LINK_{$i}%%%";
			$links[$keys][$i] = $matches[0][$i];
		}
		
		$text = str_replace($links[$keys], $links[$values], $text);
		$hasLinks = true;
	}
	
	// are there any links or headings in the text
	if (!$hasLinks &&
		!$hasHeadings &&
		!$hasImages &&
		!$maxReplacements) {
		// no links in the text and no maximum replacements - it's safe to do a straight replace
		return $caseSensitive ? str_replace($search, $replace, $text) :
								str_ireplace($search, $replace, $text);
	}

	$docLength = strlen($text);
	$searchLength = strlen($search);
	$tmpText = '';
	$position = 0;
	$count = 0;
	
	// loop indefinately
	for (;;) {
		// check for the term we are searching for
		if ($caseSensitive) {
			$compareResult = strcmp(substr($text, $position, $searchLength), $search);
		} else {
			$compareResult = stricmp(substr($text, $position, $searchLength), $search);
		}
		
		if ($compareResult == 0) {
			/* we have found the text, but we need to make sure
			 * it is a whole word and not part of another
			 */
			 $preChar = substr($text, $position - 1, 1);
			 $postChar = substr($text, $position + $searchLength, 1);
				 
			 if (in_array($preChar, $invalidChars) == false &&
				 in_array($postChar, $invalidChars) == false) {
				// jump forward past the search term
				$position += $searchLength - 1;
				
				// perform the replacement
				$tmpText .= $replace;
				
				// count the number of replacements
				$count++;
			} else {
				/* we didnt find anything, so continue
				 * as normal...
				 */
				$tmpText .= substr($text, $position, 1);
			}
			
			/* check if we have reached the maximum number of replacements
			 * or if there are any more keywords in the rest of the string
			 */
			if (($maxReplacements &&
				$count >= $maxReplacements) ||
				stripos($text, $search, $position) === false) {
				// add on the end of the string
				$tmpText .= substr($text, $position + 1);
			
				// jump out of the loop
				break;
			}
		} else {
			$tmpText .= substr($text, $position, 1);
		}

		// increase the position
		$position++;
		
		// have we reached the end of the document ?
		if ($position >= $docLength) {
			break;
		}
	}
	
	$tmpText = str_replace($links[$values], $links[$keys], $tmpText);
	$tmpText = str_replace($headings[$values], $headings[$keys], $tmpText);
	$tmpText = str_replace($images[$values], $images[$keys], $tmpText);
	
	// return the new document
	return $tmpText;
}

function alcReplaceKeywordsOld($search, $replace, $text, $maxReplacements = 0, $caseSensitive = true) {
	$START_TAG = '<a';
	$END_TAG = '</a>';
	
	$strCmpFunc = $caseSensitive ? 'strcmp' : 'stricmp';

	$tmpText = '';
	$position = 0;
	$count = 0;
	$inLink = false;
	
	$invalidChars = array_merge(range('a', 'z'), range('A', 'Z'));
	
	$startTagLength = strlen($START_TAG);
	$endTagLength = strlen($END_TAG);
	$docLength = strlen($text);
	$searchLength = strlen($search);
	
	// are there any links in the text
	if (!strpos($text, $START_TAG) &&
		!$maxReplacements) {
		// no links in the text and no maximum replacements - it's safe to do a straight replace
		return $caseSensitive ? str_replace($search, $replace, $text) :
								str_ireplace($search, $replace, $text);
	}
	
	// loop indefinately
	for (;;) {
		// are we inside a link ?
		if (!$inLink) {
			// check for the term we are searching for
			// if (substr($text, $position, $searchLength) == $search) {
			if ($strCmpFunc(substr($text, $position, $searchLength), $search) == 0) {
				/* we have found the text, but we need to make sure
				 * it is a whole word and not part of another
				 */
				 $preChar = substr($text, $position - 1, 1);
				 $postChar = substr($text, $position + $searchLength, 1);
					 
				 if (in_array($preChar, $invalidChars) == false &&
					 in_array($postChar, $invalidChars) == false) {
					// jump forward past the search term
					$position += $searchLength - 1;
					
					// perform the replacement
					$tmpText .= $replace;
					
					// count the number of replacements
					$count++;
				} else {
					/* we didnt find anything, so continue
					 * as normal...
					 */
					$tmpText .= substr($text, $position, 1);
				}
				
				/* check if we have reached the maximum number of replacements
				 * or if there are any more keywords in the rest of the string
				 */
				if (($maxReplacements &&
					$count >= $maxReplacements) ||
					stripos($text, $search, $position) === false) {
					// add on the end of the string
					$tmpText .= substr($text, $position + 1);
				
					// jump out of the loop
					break;
				}
			} else {
				/* ok, so we havent found our search term
				 * so lets check for the begining of a link
				 */
				if (substr($text, $position, $startTagLength) == $START_TAG) {
					$inLink = true;
				}
				
				$tmpText .= substr($text, $position, 1);
			}
		} else {
			/* we're in a link here
			 * check for the end of the link (</a>)
			 */
			if (substr($text, $position, $endTagLength) == $END_TAG) {
				// we found the end :-)
				$inLink = false;
			}
			
			$tmpText .= substr($text, $position, 1);
		}

		// increase the position
		$position++;
		
		// have we reached the end of the document ?
		if ($position >= $docLength) {
			break;
		}
	}
	
	// return the new document
	return $tmpText;
}

function alcInjectPostLinks($PostBody) {
	global $post;
	
	return alcInjectLinks($PostBody, ALC_CT_POST, $post->ID);
}

function alcInjectPageLinks($PostBody) {
	global $page;
	
	return alcInjectLinks($PostBody, ALC_CT_PAGE, $page->ID);
}

function alcInjectLinks($PostBody, $contentType, $id = 0) {
	global $wpdb, $table_prefix;
	$activeFlag = ALC_ACTIVE;
	$link = '';
	$url = '';
	$replacements = 0;
	$rows = array();
	$postText = $PostBody;
	
	
	/* retreive a list of search terms and link details from the links
	 * table that have at least one related address record with an
	 * address filled in
	 */
	$query = "	SELECT
						link.maxReplacements,
						link.flags,
						link.titleText,
						link.searchText,
						link.searchTextDelimiter,
						link.urlSuffix,
						link.cssClass,
						(CASE
							WHEN $contentType = 1
							THEN link.postExceptions
							WHEN $contentType = 2
							THEN link.pageExceptions
							ELSE ''
						END)
					AS	exceptions
				FROM
						{$table_prefix}alc_link
					AS	link
				INNER JOIN
						{$table_prefix}alc_address
					AS	address
					ON	(address.linkId = link.id)
					AND	(LENGTH(address.address > 5))
				WHERE
						((link.flags & $activeFlag) = $activeFlag)
					AND	(LENGTH(link.searchText) > 0)
				GROUP BY
						link.id
				ORDER BY
						link.replacementOrder ASC";

	$rows = $wpdb->get_results($query);
	
	// have we got any rows to process ?
	if (!sizeof($rows)) {
		return $PostBody;
	}
	
	// generate the cloaked url
	$url_trigger = get_option("alc_url_trigger");
	
	if ($url_trigger == '') {
		$url_trigger = ALC_DEFAULTURLTRIGGER;
	}
	
	// process the rows
	foreach ($rows as $row) {
		// skip ?
		if (strlen($row->exceptions) > 0 &&
			$id > 0) {
			$exceptions = explode(',', $row->exceptions);
			
			if (in_array($id, $exceptions)) {
				continue;
			}
		}
		
		$url = get_option('siteurl') . "/$url_trigger/" . $row->urlSuffix;
		
		// build the link
		$link = '<a ';
		
		if (($row->flags & ALC_NOFOLLOW) == ALC_NOFOLLOW) {
			$link .= 'rel="nofollow" ';
		}
		
		if (($row->flags & ALC_OPENNEWWINDOW) == ALC_OPENNEWWINDOW) {
			$link .= 'target="_blank" ';
		}
		
		if ($row->titleText) {
			$link .= 'title="' . $row->titleText . '" ';
		}
		
		if ($row->cssClass) {
			$link .= 'class="' . $row->cssClass . '" ';
		}
		
		// case sensitive searching ?
		$csSearching = (($row->flags & ALC_CASESENSITIVE) == ALC_CASESENSITIVE);
		$strPosFunction = (($csSearching) ? 'strpos' : 'stripos');
		
		$link .= 'href="' . $url . '">';
		
		// is there a delimiter we need to account for ?
		if ($row->searchTextDelimiter) {
			// put the search terms into an array
			$searchText = array();
			$searchText = explode($row->searchTextDelimiter,
									$row->searchText);
			
			// valid array with elements ?
			if (!is_array($searchText) ||
				!sizeof($searchText)) {
				continue;
			}
			
			// iterate through the array
			foreach ($searchText as $key) {
				// clean up...
				$key = trim($key);
				
				// check if the search text appears in the post
				if (strlen($key) &&
					($strPosFunction($postText, $key) !== false)) {
					// perform the replacement
					$postText = alcReplaceKeywords($key,
												 $link . $key . '</a>',
												 $postText,
												 $row->maxReplacements,
												 $csSearching);
				}
			}
			
		} else {
			// no delimiter. check that the search text appears in the post
			if ($strPosFunction($postText, $row->searchText) !== false) {
				// perform the replacement
				$postText = alcReplaceKeywords($row->searchText,
											 $link . $row->searchText . '</a>',
											 $postText,
											 $row->maxReplacements,
											 $csSearching);
			}
		}
	}

	return $postText;
}

?>