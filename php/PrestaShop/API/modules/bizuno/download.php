<?php
// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('bizunoAPI.php');
$ctl = new bizunoAPI();
$result = $ctl->processSend('downloadOrder', intval($_GET['order_id']));
die(Tools::jsonEncode($result));

?>