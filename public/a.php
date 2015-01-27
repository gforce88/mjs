<?php
$country = $_SESSION["country"];
echo $country;
date_default_timezone_set ( 'PRC' );
echo date(strtotime("01/24/2015"));
//$row->membershipStartDate = date('Y-m-d H:i:s',strtotime($studentData["startdate"]));
?>