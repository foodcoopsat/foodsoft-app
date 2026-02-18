<?php
require_once("article.php");

class ArticleDistribute extends Article
{
    public $grouporders;

    public function __construct($order, $data)
    {
        parent::__construct($order, $data);
        $this->ordered = 0;
        $this->received = 0;
        foreach ($data["grouporders"] as $grouporder) {
            $go = [
                "name" => $grouporder["ordergroup_name"],
                "ordered" => intval($grouporder["ordered"]),
                "tolerance" => intval($grouporder["tolerance"]),
                "received" => floatval($grouporder["received"])
            ];
            // replace the next 5 lines if status is obtained directly
            $this->tolerance = $go["tolerance"];
            $this->set_state();
            $go["checked"] = $this->is_pickedup;
            $go["distributed"] = $this->is_distributed;
            $go["tolerance"] = $this->tolerance;

            if ($this->has_weight) {
                foreach (["ordered", "received"] as $q) {
                    $go["weight_$q"] = $go[$q] * $this->unit_weight;
                }
            }

            $this->grouporders[$grouporder["id"]] = $go;

            $this->ordered += $go["ordered"];
            $this->received += $go["received"];
        }
    }

    public function html_name()
    {
        print "<h3>$this->name</h3>";
        parent::html_name();
    }

    public function html_ordered()
    {
        print "<p>Bestellt: " . $this->ordered . unit_str($this->unit) . " "; // " Einheiten ";
        if ($this->has_weight) {
            print " = " . weight_str($this->weight_ordered);
        }
        print ", <b>";
        if ($this->has_adaptable_weight) {
            print $this->app->local_currency_str($this->price_per_kg) . "/kg ";
        } else {
            print $this->app->local_currency_str($this->price) . "/Einheit ";
        }
        print "</b>";
        // $deposit = strpos($article["name"], "Pfand") !== FALSE ? " und Pfand" : "";
        // print " <small>inkl. Mwst.$deposit</small>";
        print "</p>";
    }
}
?>