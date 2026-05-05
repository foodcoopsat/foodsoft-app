<?php
$icon = "pickup2";

require_once "foodsoft-app.php";
$app = new FoodsoftApp($config);
$foodcoop = $app->foodcoop_name;

$apps = [];
$apps["pickup"] = "Meine Bestellungen abholen";
if ($config["distribute_app"] ?? true) {
  $apps["distribute"] = "Einkistln";
}

$options = "";
foreach ($apps as $app => $app_description) {
  $options .= "<option value='$app'>$app_description</option>";
}

?>

<!DOCTYPE html>

<script>
  function displayLoading() {
    document.getElementById("loading").style.display = "";
    document.getElementById("submit-button").style.display = "none";
  }
</script>

<html>

<head>
  <title>Foodsoft Apps <?= $foodcoop ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- https://www.w3schools.com/html/html_responsive.asp -->
  <link href="../styles/normalize.css" rel="stylesheet" type="text/css">
  <link href="../styles/fonts.css" rel="stylesheet" type="text/css">
  <link href="../styles/global.css" rel="stylesheet" type="text/css">

  <!--Touch Icons & Favicon-->
  <link rel="apple-touch-icon" sizes="180x180" href="/app-icon/<?= $icon ?>/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/app-icon/<?= $icon ?>/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/app-icon/<?= $icon ?>/favicon-16x16.png">
  <link rel="manifest" href="/app-icon/<?= $icon ?>/site.webmanifest">
  <link rel="mask-icon" href="/app-icon/<?= $icon ?>/safari-pinned-tab.svg" color="#0e960a">
  <link rel="shortcut icon" href="/app-icon/<?= $icon ?>/favicon.ico">
  <meta name="msapplication-TileColor" content="#00a300">
  <meta name="msapplication-config" content="/app-icon/<?= $icon ?>/browserconfig.xml">
  <meta name="theme-color" content="#ffffff">
  <!--END - Touch Icons & Favicon-->
</head>

<body>
  <div class="login-container">
    <div id="loginbox">
      <h1>
        <?= $foodcoop ?><br>Foodsoft Apps
      </h1>
      <p class="info">
        Klicke auf "Anmelden", dann gelangst du zur Anmeldeseite der Foodsoft, und anschließend wieder
        hierher zurück in die App. Falls du dich vorher schon in der Foodsoft angemeldet hast,
        geht es gleich weiter.
      </p>
      <form id="loginForm" name="loginForm" method="get" action="">
        <div class="field-box">
          <label for="action">Aktion</label>
          <select name="app" id="app" style="width:100%;">
            <?= $options ?>
          </select>
        </div>
        <br>
        <button type="submit" id="submit-button" onClick="displayLoading();">Anmelden</button>
        <div style="display:none;" id="loading">
          <p><i>Bitte warten, Daten werden aus der Foodsoft geladen...</i></p>
        </div>
      </form>
    </div>
  </div>
</body>

</html>