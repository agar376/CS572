<?php

header('Content-Type: text/html; charset=utf-8');

ini_set('memory_limit', '4096M');

$limit = 10;

$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : false;

$algo = isset($_REQUEST['pagerank']) ? $_REQUEST['pagerank'] : "lucene";

$results = false;

$map = [];

$map_file = fopen('mapNBCNewsDataFile.csv', 'r');

while (($line = fgetcsv($map_file)) !== FALSE) {
	$map[trim($line[0])] = trim($line[1]);
}

fclose($map_file);

function get_snippet($text, $query) { 
    $tokens = explode (' ',$query);
    $pos = strpos(strtolower($text), strtolower($query));

    foreach ($tokens as $query) {
        $pos = strpos(strtolower($text), strtolower($query)); 
        if ($pos > -1) break;
    }

    $excerpt = substr($text, 0, $pos + strlen($query) + 200); 
    $phrase_joined = join("|", $tokens);
    preg_match("/" . $phrase_joined . "/i", $excerpt, $match, PREG_OFFSET_CAPTURE);
    $start_string = $match[0][1];
    $end_string = strpos($excerpt, '"', $start_string);
    $excerpt = substr_replace($excerpt, "", $end_string); 
    $start_pos = strrpos($excerpt, '"');
    $excerpt = substr($excerpt, $start_pos + 1); 
    $excerpt = "... " . $excerpt . " ...";

    return highlight($excerpt, $phrase_joined); 
}

function highlight($excerpt, $phrase_joined) {
    $excerpt = preg_replace("/" . $phrase_joined . "/i", "<b>$0</b>" , $excerpt);
    return $excerpt;
} 

if ($query)
{
	require_once('Apache/Solr/Service.php');
	require_once('SpellCorrector.php');

	$solr = new Apache_Solr_Service('localhost', 8983, '/solr/hw4/');

	if (get_magic_quotes_gpc() == 1)
	{
		$query = stripslashes($query);
	}

	$query = trim($query);

	$query_tokens = explode(" ", $query);
	$corrected_query = "";
	foreach($query_tokens as $el){		    
		$corrected_query = $corrected_query . SpellCorrector::correct($el) . " ";
	}
	$corrected_query = trim($corrected_query);

	$squery = strcasecmp($query, $corrected_query) != 0 ? $corrected_query : $query;
	if($w) {
		$squery = $query;
	}


	try
	{
		$additionalParameters = [];
		if ($_REQUEST['pagerank'] == "pagerank") {
			$additionalParameters['sort'] ="pageRankFile desc";
		}

		$results = $solr->search($squery, 0, $limit, $additionalParameters);

	}
	catch (Exception $e)
	{
		die("<html><head><title>Exception</title></head><body><pre>{$e->__toString()}</pre></body></html>");
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>PHP Solr Indexing and Searching</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){
			var stext = $("#q").val();
			$("#q").autocomplete({
				source: function(request, response) {
		            $.ajax({
		            	url: "suggestions.php",
			            data: { 
						  	"q": request.term,
			             }, 

			             success: function(data) {
			                response($.map(data.split(","), function(item) { 
		                    	if(!item.match(/[.:\d\-_]/gi)) {
				                    return { 
				                        label: item,
				                        value: item,
				                   	}
			                    }; 
			            	}));
			            }
		          	}); 
		         },
				minLength: 1
			})
		});
	</script>

</head>

<body>
	<form accept-charset="utf-8" method="get">
		<label for="q">Search:</label>
		<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
		<br/>
		<input type="radio" name="pagerank" value="lucene" checked />Use Lucene Solr Algorithm
		<input type="radio" name="pagerank" value="pagerank" <?php if ($_REQUEST['pagerank'] == "pagerank") echo "checked";?> />Use Page Rank Algorithm
		<br/>
		<input type="submit"/>
	</form>
	<?php

	if ($results)
	{
		$total = (int) $results->response->numFound;
		$start = min(1, $total);
		$end = min($limit, $total);
		?>
		<br/>
		<?php 
		if (strcasecmp($query, $corrected_query) != 0 && $w) {
			?>
			Did you mean: &nbsp;
			<b><i>
				<a href="http://localhost/hw5.php?q=<?php echo htmlentities($corrected_query);?>&pagerank=<?php echo $algo;?>"><?php echo $corrected_query; ?></a>
			</i></b>
			<br/>
			<br/>
			<?php 
		}

		if (strcasecmp($query, $corrected_query) != 0 && !$w) {
			?>
			Did you mean: &nbsp;
			<b><i>
				<a href="http://localhost/hw5.php?q=<?php echo htmlentities($corrected_query);?>&pagerank=<?php echo $algo;?>"><?php echo $corrected_query; ?></a>
			</i></b>
			<br/>
			<br/>
			Search instead for: &nbsp;
			<b><i>
				<a href="http://localhost/hw5.php?q=<?php echo htmlentities($query);?>&pagerank=<?php echo $algo;?>&w=1"><?php echo $query; ?></a>
			</i></b>
			<br/>
			<br/><i>Showing results for <b><?php echo $squery;?> </b></i>
			<br/>
			<?php 
		}
		?>

		<br/>
		<div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
		<ol>
			<?php
			foreach ($results->response->docs as $doc)
			{
				$id = htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8');
				$url_id = str_replace("/Users/ShobhitAgarwal/Downloads/NBCNewsData/NBCNewsDownloadData/", "", $id);

				?>
				<li>
					<a target="_blank" href=<?php echo htmlspecialchars($map[$url_id], ENT_NOQUOTES, 'utf-8'); ?>><?php echo htmlspecialchars($doc->dc_title, ENT_NOQUOTES, 'utf-8'); ?></a>
					<br/>
					<b><i>url:</i></b> <a target="_blank" href=<?php echo htmlspecialchars($map[$url_id], ENT_NOQUOTES, 'utf-8'); ?>><?php echo htmlspecialchars($map[$url_id], ENT_NOQUOTES, 'utf-8'); ?></a>
					<br/>

					<b><i>Doc ID:</i></b> <?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?>
					<br/>
					<b><i>Short Description:</i></b> <?php echo htmlspecialchars(($doc->og_description ? $doc->og_description : "No Description Available"), ENT_NOQUOTES, 'utf-8'); ?>
					<br/>
					<?php
						$snippet = "";
				        $tokens = explode (' ',$squery);
				        $phrase_joined = join("|", $tokens);
   						if(trim($snippet) == "" && preg_match('/' . $phrase_joined . '/i', $doc->og_description)) {
							$snippet = $doc->og_description;
							$snippet = highlight($snippet, $phrase_joined);
						}
   						else if(trim($snippet) == "" && preg_match('/' . $phrase_joined . '/i', $doc->title)) {
							$snippet = $doc->title;
							$snippet = highlight($snippet, $phrase_joined);
						}
						else if(trim($snippet) == "") {
							$content = file_get_contents("NBCNewsData/NBCNewsDownloadData/" . $url_id);
							$content = preg_replace("#<(.*)/(.*)>#iUs", "\n", $content);
							$snippet = get_snippet($content, $squery);
						}
   						if(!preg_match('/' . $phrase_joined . '/i', $snippet)) {
							$snippet = "";
						}
					?>
					<b><i>Snippet:</i></b> <?php echo ($snippet && trim($snippet) != "..." ? $snippet : "No Snippet Available"); ?>
					<br/>
					<br/>
				</li>
				<?php
			}
			?>
		</ol>
		<?php
	}
	?>
</body>
</html>