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
		preg_match_all('/(?<token>
        \\\\ # the slash in the beginning
        [a-zA-Z_]+ #a word
        \{[^{}]*((?P>token)[^{}]*)?\} # {something}
)/x', $string, $matches, PREG_OFFSET_CAPTURE);

		echo 'STRING = '.$string."\n\n";

		$last_offset = 0;
		$new_string = (sizeof($matches[0]) > 0 ? '' : $string);
		foreach ($matches[0] as $match)
		{
			$substring = $match[0];
			
			$offset = $match[1];
			$length = strlen($substring);

			// process substring
			$first_bracket_offset = strpos($substring, "{");
			$last_bracket_offset = strrpos($substring, "}");
			echo 'SUBSTRING = '.substr($substring,$first_bracket_offset+1,$last_bracket_offset-$first_bracket_offset-1)."\n\n";
			$substring = evaluate(substr($substring,$first_bracket_offset+1,$last_bracket_offset-$first_bracket_offset-1), $parameters);

			$new_string .= substr($string, $last_offset, $offset-$last_offset);
			echo 'NEWSTRING = '.$new_string."\n\n";

			$new_string .= $substring;
			echo 'NEWSTRING = '.$new_string."\n\n";

			$last_offset = $offset+$length;
			echo 'LAST OFFSET = '.$last_offset."\n\n";
		}
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