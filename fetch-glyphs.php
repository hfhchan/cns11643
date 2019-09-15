<?

function getURL2($url) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);

	// Init
	curl_setopt($ch, CURLOPT_USERAGENT, 'okhttp/3.5.0');
	curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
	curl_setopt($ch, CURLOPT_TIMEOUT, 8);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Set Log
	$log = fopen('php://temp', 'r+');
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_STDERR, $log);

	// HTTP Info
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

	// SSL Info
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	$data = curl_exec($ch);

	if (!$data) {
		$data = curl_exec($ch);		
		if (!$data) {
			echo 'Error - ' . curl_getinfo($ch, CURLINFO_HTTP_CODE);
			var_dump($log2);
			exit;
		}
	}

	// Get Log Info
	rewind($log);
	$log2 = stream_get_contents($log);
	fclose($log);

	// Get Headers and Content
	list($headers, $content) = explode("\r\n\r\n", $data, 2);
	if (strpos($headers, ' 100 Continue') !==false ){
		list($headers, $content) = explode("\r\n\r\n", $content, 2);
	}

	// Get HTTP Code
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//	return (object) [
//		'time'    => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
//		'info'    => curl_getinfo($ch),
//		'headers' => $headers,
//		'code'    => $httpcode,
//		'content' => $content,
//		'log'     => $log2
//	];
	return $content;
}

?>
<!doctype html>
<style>
body{max-width:640px;margin:20px auto;font:16px/1.2 Arial}
h1{margin:10px 0;font-size:20px}
form{margin:10px 0}
form>div{margin:10px 0}
form>div>label{margin:0;padding:0}
form>div>label>input{margin:0;padding:0}
</style>

<title>Generate .bat file for CNS11643 glyphs</title>
<h1>Generate .bat file for fetching CNS11643 glyphs</h1>
<form method=get>
	<div>Fetch Plane:
		<select name=plane>
<?php for ($i = 1; $i < 30; $i++) echo '<option value="' .$i. '">T' . strtoupper(dechex($i)) . ' (Plane ' . $i . ')</option>' . "\r\n"; ?>
		</select>
	</div>
	<div><label><input type=checkbox name=face value=sung> Use Songti</label></div>
	<div><input type=submit value=Generate></div>
</form>

<hr>

<? if (isset($_GET['plane'])) { ?>
<div>
<?
	set_time_limit(300);

	$codepoints = [];

	$plane = intval($_GET['plane']);
	$html = getURL2('https://www.cns11643.gov.tw/search.jsp?ID=5&cPage='.$plane.'&SN=0000&SN2=FFFF&PAGE=1');
	$start = strpos($html, '下一頁');
	$end = strpos($html, '最後頁', $start);
	$sub = substr($html, $start, $end - $start);
	$sub = str_replace('下一頁 -">〉</a> <a href=\'', '', $sub);
	$sub = str_replace('\' class=last title="- ', '', $sub);
	$lastPage = substr($sub, strpos($sub, '&PAGE=') + 6);
	echo 'Scanning page 1 - ' . $lastPage;
?>
</div>
<div style="font-size:10px;margin:10px 0">
<?
	for ($i = 1; $i <= $lastPage; $i++) {
		$html2 = getURL2('https://www.cns11643.gov.tw/search.jsp?ID=5&cPage='.$plane.'&SN=0000&SN2=FFFF&PAGE=' . $i);
		$start = strpos($html2, '<div class=wordList><span>');
		$end = strpos($html2, '</span></div>', $start);
		$sub = substr($html2, $start, $end - $start);
		$sub = explode("\n", trim($sub));
		foreach ($sub as $line) {
			$ref = trim(strip_tags($line));
			if ($ref != '') {
				$codepoints[] = substr($ref, strpos($ref, '-') + 1);
			}
		}
		echo '<div>Scanned page ' . $i . '</div>';
	}
?>
</div>
<textarea style="width:100%;height:600px;font-size:10px;white-space:pre" spellcheck="false">
<?
	foreach ($codepoints as $codepoint) {
		$filename = 'T' . strtoupper(dechex($plane)) . '-' . strtoupper($codepoint);
		if (isset($_GET['face']) && $_GET['face'] == 'sung') {
			echo 'IF NOT EXIST "'.$filename.'.png" curl -o "'.$filename.'.png" "https://www.cns11643.gov.tw/cgi-bin/ttf2png?fontsize=128&face=sung&page='.$plane.'&number='.strtolower($codepoint).'&bgcolor=ffffff"';
		} else {
			echo 'IF NOT EXIST "'.$filename.'.png" curl -o "'.$filename.'.png" "https://www.cns11643.gov.tw/cgi-bin/ttf2png?fontsize=128&page='.$plane.'&number='.strtolower($codepoint).'&bgcolor=ffffff"';
		}
		echo "\n";
	}
?>
</textarea>
<?
	echo '<div>Total ' . count($codepoints) . ' glyphs</div>';
}

?>