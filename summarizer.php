<?php

	// https://en.wikipedia.org/w/api.php?action=query&titles=Duke%20Energy&prop=info|revisions&rvprop=content&format=json
	
	/*
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
		$infobox = $infobox_matches[0];
		preg_match_all('/\|\s*([\w_]*)\s*=\s*(.*?)\n/', $infobox, $infobox_data_matches, PREG_SET_ORDER);
		foreach ($infobox_data_matches as $infobox_field)
		{
			$wikipage['properties'][$infobox_field[1]] = $infobox_field[2];
		}
	}

	print_r($wikipage);
*/
	$company = array(
		'name' => 'Dan\'s Microwaves',
		'former_name' => 'Mitch Mics',
		'business_type' => 'company',
		'type' => 'private'
	);

	$template_filepath = 'templates/'.$company['business_type'].'.txt';
	if (!file_exists($template_filepath))
	{
		// exit and error out
		exit;
	}

	$template = file_get_contents($template_filepath);

	echo evaluate($template, $company);


	/*

	$business_types = array(
		'company',
		'law firm',
		'organization',
		'school',
		'university',
		'venue'		
	);

	$business_name = array();

	$business_name['subject'] = array(
		$business['name'],
		'the '.$business['business_type'],
		'it'
	);

	$business_name['object'] = array(
		$business['name'],
		'the '.$business['business_type']
	);*/

	function evaluate($string, $parameters)
	{
		// find all \token{string} matches
		preg_match_all('/(?<token>
        \{[^{}]*((?P>token)[^{}]*)?\} # {something}
)/x', $string, $matches, PREG_OFFSET_CAPTURE);

		$last_offset = 0;
		$new_string = '';
		// for each \token{string} match, evaluate and concatenate all text before the match and also the match itself
		foreach ($matches[0] as $index=>$match)
		{
			// grab a couple initial state variables
			$substring = $match[0];
			$offset = $match[1];
			$length = strlen($substring);

			// look for all placeholders, set as requirements
			preg_match_all('/\*(.*?)\*/', $substring, $requirements_matches);
			$has_requirements = true;
			foreach ($requirements_matches[1] as $requirement)
			{
				if (!isset($parameters[$requirement])) $has_requirements = false;
			}

			// process the actual match
			$first_bracket_offset = strpos($substring, "{");  // start of string in \token{string}
			$last_bracket_offset = strrpos($substring, "}");  // end of string in \token{string}
			if ($has_requirements)
			{
				$substring = evaluate(substr($substring,$first_bracket_offset+1,$last_bracket_offset-$first_bracket_offset-1), $parameters);
			}
			else $substring = '';

			// all text before the \token{string} match
			$new_string .= substr($string, $last_offset, $offset-$last_offset);

			// the evaluated \token{string} match
			$new_string .= $substring;

			// set the last offset – where we should pick up after the last \token{string} match
			$last_offset = $offset+$length;
		}

		// add everything in the $string after the last match
		$new_string .= substr($string, $last_offset, strlen($string)-$last_offset);
		return str_replace(array_map(function($x) { return '*'.$x.'*'; }, array_keys($parameters)), array_values($parameters), $new_string);
	}
?>