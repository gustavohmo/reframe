<?php

require "../bcause-core2.php";

$con = new Connection();
$id = $_GET['id'];

$event = new Event($con);
$event_data = $event->getEvent($id);

$json_output = $event->getEventChildrenJSON($con,1,0);

print $json_output;

?>