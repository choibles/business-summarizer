<?php

	// https://en.wikipedia.org/w/api.php?action=query&titles=Duke%20Energy&prop=info|revisions&rvprop=content&format=json

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

	function evaluate($string, $parameters)
	{
		// find all \token{string} matches
		preg_match_all('/(?<token>
        \\\\ # the slash in the beginning
        [a-zA-Z_]+ #a word
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

			// process the actual match
			$first_bracket_offset = strpos($substring, "{");  // start of string in \token{string}
			$last_bracket_offset = strrpos($substring, "}");  // end of string in \token{string}
			$requirement = substr($substring, 1, $first_bracket_offset-1);
			if (isset($parameters[$requirement]))
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

	echo evaluate($template, $company);

	exit;


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
?>