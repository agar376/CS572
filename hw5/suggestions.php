<?php

$dic_text = file_get_contents("serialized_dictionary.txt");;

$q = strtolower($_GET['q']);

if (get_magic_quotes_gpc() == 1)
{
	$q = stripslashes($q);
}

$q = trim($q);

$tokens = explode(" ", $q);

$search_term = array_pop($tokens);

$suggestions = join(" ", $tokens);

$url = "http://localhost:8983/solr/hw4/suggest?wt=json&indent=true&q=" . $search_term;

$json_string = file_get_contents($url);

$json_data = json_decode($json_string);

$result_arr = [];

foreach ($json_data->suggest->suggest->$search_term->suggestions as $sug) {
	if(preg_match('/' . $sug->term . '/i', $dic_text)) {
		array_push($result_arr, trim($suggestions . " " . $sug->term));
	}
}
if(!in_array(trim($q), $result_arr)) {
	array_push($result_arr, $q);	
}

echo join(",", $result_arr);

?>