<?php

$config = [
    // "foodcoop_name" => "...", // default: ucwords(current url dir)
    // "foodsoft_url" => "https://app.foodcoops.at/...", // default: "https://app.foodcoops.at/" + current url dir

    // Einrichten:  https://app.foodcoops.at/.../oauth/applications
    // Callback URL: https://pickup.foodcoops.at/.../
    // Client (application) credentials von dort: 
    "client_id" => "",
    "client_secret" => "",

    // flag to display order comments as popover in pickup order view
    "show_order_comments" => false,
    "exclude_usernames" => [
        "Bestellungen",
        "admin"
    ], // default: []
];


// ----------------------------------------------------------
// development: test settings for local foodsoft
// ----------------------------------------------------------

$config["runs_on_local_server"] = $_SERVER["HTTP_HOST"] == "localhost";
$config["use_local_foodsoft"] = $config["runs_on_local_server"];
$config["debug"] = FALSE; #$runs_on_local_server;  #TRUE; #FALSE;

if ($config["use_local_foodsoft"]) {
    $config["foodsoft_url"] = "http://localhost:3000/f";
    // Einrichten:  localhost:3000/f/oauth/applications
    // Callback URL: http://localhost/...
    $config["time_now"] = "2025-12-17"; // "today"; date should be the date of the last local foodsoft database update
}
// --- end of development section ---------------------------------------



include "../main.php";

?>
