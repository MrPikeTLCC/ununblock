<?php
	$cacheFile = "cache.txt";
	$cacheTime = 3600; // seconds (1 hour)
	
	// If cache exists and is fresh → serve it and exit
	if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
	    header("Content-Type: text/plain");
	    readfile($cacheFile);
	    exit;
	}


	$sources = array(
		array(
			"url" => "https://gist.githubusercontent.com/eddy-22/2cdfadfa539cdaccfd0a9e31bf98e6d6/raw/unblocked_sites.md",
			"method" => "md-li"
		)
	);
	
	$domains = array();
	
	foreach($sources as $src){
		$context = stream_context_create([
		    "http" => [
		        "header" => "User-Agent: Mozilla/5.0\r\n"
		    ]
		]);
		
		$content = file_get_contents($src["url"], false, $context);
		
		if ($content === false) {
		    if (file_exists($cacheFile)) {
		        header("Content-Type: text/plain");
		        readfile($cacheFile);
		        exit;
		    }
		    die("Failed to fetch gist and no cache available");
		}
		
		switch($src["method"]){
			case "md-li":	// Markdown List
				
				if ($content === false) {
				    die("Failed to fetch gist");
				}
				
				// Split into lines
				$lines = explode("\n", $content);
				
				foreach ($lines as $line) {
				    // Extract URLs from each line
				    if (preg_match('/https?:\/\/[^\s\]\)]+/', $line, $matches)) {
				        $url = trim($matches[0]);
				
				        // Parse URL
				        $parsed = parse_url($url);
				
				        if (!empty($parsed['host'])) {
				            $domain = strtolower($parsed['host']);
				
				            // Optional: strip "www."
				            $domain = preg_replace('/^www\./', '', $domain);
				
				            $domains[$domain] = true; // deduplicate
				        }
				    }
				}
				
			break;
		}
	}
	
	// Sort domains
	$cleanList = array_keys($domains);
	sort($cleanList);
	
	// Build output string
	$output = implode("\n", $cleanList) . "\n";

	file_put_contents("output/unblocked_edl.txt", $output);

	echo "Generated " . count($cleanList) . " domains\n";
?>