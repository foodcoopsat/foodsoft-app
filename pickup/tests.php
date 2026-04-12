<?php

require_once "article.php";


$article_data = [
    "name" => "test article",
];

$test_units = [
    "500g" => 500,
    "500 g" => 500,
    "ca.500g" => 500,
    "ca. 500g" => 500,
    "ca. 500 g" => 500,
    "ca. 500g pro Stueck" => 500,
    "ca. 500 g pro Stueck" => 500,
    "kg" => 1000,
    "Kg" => 1000,
    "kg " => 1000,
    "(kg)" => 0,
    "1kg" => 1000,
    "1 kg" => 1000,
    "ca.1kg" => 1000,
    "ca. 1kg" => 1000,
    "ca. 1 kg" => 1000,
    "ca. 1kg pro Stueck" => 1000,
    "ca. 1 kg pro Stueck" => 1000,
    "1.2kg" => 1200,
    "1.2 kg" => 1200,
    "ca.1.2kg" => 1200,
    "ca. 1.2kg" => 1200,
    "ca. 1.2 kg" => 1200,
    "1,2kg" => 1200,
    "1,2 kg" => 1200,
    "ca.1,2kg" => 1200,
    "ca. 1,2kg" => 1200,
    "ca. 1,2 kg" => 1200,
];

print "<pre>";

print "=== weight unit tests ============================\n";
$failed = [];
foreach ($test_units as $test_unit => $correct_weight) {
    $article_data["unit"] = $test_unit;
    $article = new Article(null, $article_data);
    printf(
        "%30s => %7g g, correct: %7g g, %s!\n",
        "'$test_unit'",
        $article->unit_weight,
        $correct_weight,
        $article->unit_weight == $correct_weight ? "ok" : "WRONG"
    );
    if ($article->unit_weight != $correct_weight) {
        $failed[] = $test_unit;
    }
}
print count($failed) . " failed: ";
print implode(", ", $failed) . "\n";

