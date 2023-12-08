<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

/**
 * Read More: https://developer.mozilla.org/en-US/docs/Web/API/WebVTT_API
 * Function to parse webtt (voice-tagged transcripts) to json
 * @author - authorspirit
 * 
 * @param string $vttContent multi-line string
 * @param bool $stringify true, converts to json string
 */
function parseVTT2Json(string $vttContent = null, bool $stringify = false): mixed {
	if (is_null($vttContent)) return array();

	$lines = explode("\n", $vttContent);
	$jsonArray = [];

	$currentEntry = null;

	foreach ($lines as $line) {
		$line = trim($line);

		// Skip empty lines
		if ($line === '') {
			continue;
		}

		// Check if it's a timecode line
		if (preg_match('/^\d+:\d+:\d+\.\d+ --> \d+:\d+:\d+\.\d+/', $line, $match)) {
			// If there's an existing entry, push it to the JSON array

			// 00:00:05.000 --> 00:00:10.000 line:63% position:72% align:start
			// 00:00:05.000 --> 00:00:10.000 - extract start and end timings - ignore others (TODO, if other position required in future)
			$lineArray = explode("-->", $match[0]);

			$timings = array();
			foreach ($lineArray as $idx => $tm) {
				if (empty(trim($tm))) continue;
				$lineArray[$idx] = trim($tm);
			}

			if (count($lineArray) == 2) {
				$timings["start"] = $lineArray[0];
				$timings["end"] = $lineArray[1];
			}

			// update the previous entry
			if ($currentEntry !== null) {
				$jsonArray[] = $currentEntry;
			}

			// Start a new entry
			$currentEntry = ['time' => $timings, 'text' => ''];
		} else {

			if ($currentEntry == null) continue;

			// ignore numering for tags
			if (preg_match("/^\d$/", $line)) continue;

			/**
			 * Extract voice tag content and remove voice tags
			 * <v Megan Bowen>Hello, there. Good morning</v> 
			 */
			$voiceTagContent = "";
			if (preg_match('/\>(.*?)\<\/v\>/', $line)) {
				$voiceTagContent = preg_replace('/\>(.*?)\<\/v\>/', '', $line);
				$voiceTagContent = !empty($voiceTagContent) ? preg_replace('/^\<v/', "", $voiceTagContent) : '';
			}

			// Remove voice tags and append text to the current entry
			$line = preg_replace('/\<\/?v\>/', '', $line);
			$currentEntry['text'] .= strip_tags($line);
			$currentEntry['voice'] = trim($voiceTagContent);
		}
	}

	// Add the last entry to the JSON array
	if ($currentEntry !== null) {
		$jsonArray[] = $currentEntry;
	}

	if($stringify){
		return json_encode($jsonArray, JSON_PRETTY_PRINT);
	}

	return $jsonArray;
}

$vttContent = file_get_contents("conversation.vtt");

$parsedJson = parseVTT2Json($vttContent, true);

file_put_contents("z-parse,json", $parsedJson);
