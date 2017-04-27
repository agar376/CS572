<?php

header('Content-Type: text/html; charset=utf-8');

$limit = 10;

$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;

$results = false;

$map = [];

$map_file = fopen('mapNBCNewsDataFile.csv', 'r');

while (($line = fgetcsv($map_file)) !== FALSE) {
	$map[trim($line[0])] = trim($line[1]);
}

fclose($map_file);

if ($query)
{
	require_once('Apache/Solr/Service.php');

	$solr = new Apache_Solr_Service('localhost', 8983, '/solr/hw4/');

	if (get_magic_quotes_gpc() == 1)
	{
		$query = stripslashes($query);
	}

	try
	{
		$additionalParameters = [];
	    if ($_REQUEST['pagerank'] == "pagerank") {
	        $additionalParameters['sort'] ="pageRankFile desc";
	    }

		$results = $solr->search($query, 0, $limit, $additionalParameters);

	}
	catch (Exception $e)
	{
		die("<html><head><title>Exception</title><body><pre>{$e->__toString()}</pre></body></html>");
	}
}
?>
<html>
<head>
	<title>PHP Solr Indexing and Searching</title>
</head>
<body>
	<form accept-charset="utf-8" method="get">
		<label for="q">Search:</label>
		<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
		<br/>
		<input type="radio" name="pagerank" value="lucene" checked />Use Lucene Solr Algorhthm
		<input type="radio" name="pagerank" value="pagerank" <?php if ($_REQUEST['pagerank'] == "pagerank") echo "checked";?> />Use Page Rank Algorhthm
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
					<b><i>url:</i></b> <a target="_blank" href=<?php echo htmlspecialchars($map[$url_id], ENT_NOQUOTES, 'utf-8'); ?>><?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?></a>
					<br/>

					<b><i>Doc ID:</i></b> <?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?>
					<br/>
					<b><i>Short Description:</i></b> <?php echo htmlspecialchars(($doc->og_description ? $doc->og_description : "No Description Available"), ENT_NOQUOTES, 'utf-8'); ?>
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