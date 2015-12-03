<?php
	exec('traceroute google.com 2>&1', $out, $code);
	if ($code) {
	    die("An error occurred while trying to traceroute: " . join("\n", $out));
	}
	print_r($out);
