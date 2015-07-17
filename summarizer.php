<?php

	// https://en.wikipedia.org/w/api.php?action=query&titles=Duke%20Energy&prop=info|revisions&rvprop=content&format=json
	
	$url = 'https://en.wikipedia.org/w/api.php?action=query&titles=Duke%20Energy&prop=info|revisions&rvprop=content&format=json';
	
	$ch = curl_init("");
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Dandescriptions/1.1 (http://www.choibles.com/; dan@choibles.com)'); // set user agent
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	$response = curl_exec($ch);
	if (curl_errno($ch))
	{
		$message = 'cURL Error: '.curl_error($ch)."\n";
		if ($write_log) file_put_contents($log_filename, $message, FILE_APPEND);
		echo $message;
		continue;
	}
	curl_close($ch);

	$response = json_decode($response);
	$pages = (array) $response->query->pages;
	$page = (array) array_shift($pages);

	$wikipage = array();
	$wikipage['page_id'] = $page['pageid'];
	$wikipage['title'] = $page['title'];
	$wikipage['touched'] = $page['touched'];

	$page_data = $page['revisions'][0]->{"*"};
	preg_match('/\{\{Infobox (.*?)\n(\|\s*([\w_]*)\s*=\s*(.*?)\n)*\}\}/',$page_data, $infobox_matches);
	
	if (isset($infobox_matches[0])) // infobox found
	{
		$wikipage['type'] = $infobox_matches[1];
		$infobox = $infobox_matches[0];
		preg_match_all('/\|\s*([\w_]*)\s*=\s*(.*?)\n/', $infobox, $infobox_data_matches, PREG_SET_ORDER);
		foreach ($infobox_data_matches as $infobox_field)
		{
			$altered_value = str_replace(array('</ref>'),array(''),preg_replace('/<ref.*?>/','',$infobox_field[2]));
			$wikipage['properties'][$infobox_field[1]] = $altered_value;
		}
	}

	$template_filepath = 'templates/'.$wikipage['type'].'.txt';
	if (!file_exists($template_filepath))
	{
		// exit and error out
		exit;
	}
	$template = file_get_contents($template_filepath);

	echo evaluate_template($template, $wikipage['properties']);

	function evaluate_template($template, $parameters)
	{
		// find all {string} matches
		preg_match_all('/\{((([^\{\}]+)|(?R))*)\}/', $template, $matches, PREG_OFFSET_CAPTURE);
		$last_offset = 0;
		$new_string = '';
		// for each {string} match, evaluate and concatenate all text before the match and also the match itself
		foreach ($matches[0] as $match)
		{
			// grab a couple initial state variables
			$substring = $match[0];
			$content = substr($substring, 1, strlen($substring)-2); // substring without enclosing braces
			$offset = $match[1];
			$length = strlen($substring);

			// remove sub-matches so we only check this level for requirements
			preg_match_all('/\{((([^\{\}]+)|(?R))*)\}/', $content, $obscure_matches);
			$test_substring = str_replace($obscure_matches[0], array(), $substring);
			
			// look for all placeholders, set as requirements
			preg_match_all('/\*(.*?)\*/', $test_substring, $requirements_matches);
			$has_requirements = true;
			foreach ($requirements_matches[1] as $requirement)
			{
				if (!isset($parameters[$requirement])) $has_requirements = false;
			}

			// process the actual match
			if ($has_requirements) $substring = evaluate_template($content, $parameters);
			else $substring = '';

			// all text before the {string} match
			$new_string .= substr($template, $last_offset, $offset-$last_offset);

			// the evaluated {string} match
			$new_string .= $substring;

			// set the last offset – where we should pick up after the last {string} match
			$last_offset = $offset+$length;
		}

		// add everything in the $template after the last match
		$new_string .= substr($template, $last_offset, strlen($template)-$last_offset);
		return str_replace(array_map(function($x) { return '*'.$x.'*'; }, array_keys($parameters)), array_values($parameters), $new_string);
	}

/*

	

	print_r(template_braces($template));

	function template_braces($string, $recursion = 0) {
		$results = array();
	    if (preg_match_all("/\{((([^\{\}]+)|(?R))*)\}/", $string, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
	        foreach ($matches as $match) {
	            $results[] = array('match' => $match[0][0], 'offset' => $match[0][1], 'deepness' => $recursion);
	            if ($InnerResults = template_braces($match[1][0], $recursion+1)) {
	                $results = array_merge($results, $InnerResults);
	            }
	        }
	        return $results;
	    }
	    return false;
	}


	function wikipedia_braces($string, $recursion = 0) {
	    $results = array();
	    if (preg_match_all("/\{\{(?<tagname>[\w\s]+)(?<content>(([^\{\}]+)|(?R))*)\}\}/", $string, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
	        foreach ($matches as $match) {
	            $results[] = array('match' => $match[0], 'tagname' => $match['tagname'], 'content' => $match['content'], 'deepness' => $recursion);
	            if ($InnerResults = wikipedia_braces($match['content'], $recursion+1)) {
	                $results = array_merge($results, $InnerResults);
	            }
	        }
	        return $results;
	    }
	    return false;
	}*/
?>