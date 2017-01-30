<?php

require './resources/class.iCalReader.php';

function parseiCalWithURL ($calUrl) {

	if (!$calUrl) { return []; }

	$ical   = new ICal();
	$icalLoaded = $ical->initURL($calUrl);
	$events = $ical->events();

	$provasArray = [];

	// print_r($calUrl);exit;

	foreach ($events as $event) {
		if (!@$event['SUMMARY'] || !$event['DTSTART']) { continue; }

		$provasArray[] = new Prova(@$event['SUMMARY'],
							 getFullDateFromUnix($ical->iCalDateToUnixTimestamp($event['DTSTART'])),
							 "Moodle",
							 getDay($event['DTSTART']),
							 getWeekDay($event['DTSTART']),
							 getMonthNumber($event['DTSTART']));
	}

	return ($provasArray);
}
?>