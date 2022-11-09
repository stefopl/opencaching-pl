<?php
switch($_GET['page'] ?? false) {
default:
case 'logbook':
include("logbook.php");
break;
//case 'cachevalidator':
//include("cachevalidator.php");
//break;
}
