<?php
$cacheFile = "output/unblocked_edl.txt";

$cachedDomains = [];

if (file_exists($cacheFile)) {
    $cachedLines = file($cacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($cachedLines as $line) {
        $cachedDomains[trim($line)] = true;
    }
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

	// Merge cached + new
	$domains = array_merge($cachedDomains, $domains);
	
	// Sort domains
	$cleanList = array_keys($domains);
	sort($cleanList);
	
	// Build output string
	if (!is_dir("output")) {
    	mkdir("output", 0755, true);
	}

	$output = implode("\n", $cleanList) . "\n";

	file_put_contents("output/unblocked_edl.txt", $output);

	echo "Generated " . count($cleanList) . " domains\n";
?>