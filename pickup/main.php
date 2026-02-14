<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);

$app_name = $_GET["app"] ?? "";

if ($app_name == "pickup") {
    require_once "pickup.php";
    $app = new PickupApp($config);
} elseif ($app_name == "distribute") {
    require_once "distribute.php";
    $app = new DistributeApp($config);
} else {
    include "select-app.php";
}

?>