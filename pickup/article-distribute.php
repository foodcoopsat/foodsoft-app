<?php
require_once("article.php");

class ArticleDistribute extends Article
{
    public $grouporders = [];
    public $n_grouporders;

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
        $this->n_grouporders = count($this->grouporders);
        $this->finalize_construct();
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


    public function html_received()
    {
        $not_delivered = "nicht geliefert|doch geliefert";
        print "<p> ";
        if ($this->app->edit_received) {
            if ($this->has_adaptable_weight) {
                print "Gewicht erhalten: ";

                $input = new form_input();
                $input->set_name($this->var_name("weight_received"));
                $input->set_init_value($this->weight_received);
                $input->set_class("weight");
                // $input->set_update_function(sprintf(
                //     "updateTotalReceived($id_article, %d, /*update_weight=*/ false); ",
                //     $unit_weight
                // ) . "ajaxOnChange(this);");
                // if ($n_ordergroups == 1) {
                //     $input->add_update_function("redistributeWeight($id_article);");
                // }
                $input->set_clear_button();
                $input->set_null_button($not_delivered, $this->received);
                $input->set_reset_button($this->weight_received);
                $input->set_article_name($this->name);
                $input->set_buttons_on_both_sides();
                $input->print();

                print " Gramm";
                // print "<input type='hidden' value='" . $total_weight_received . "'  name='formdata" . $index . "[total_weight_initial]'>";
                // if (is_variable_weight($article)) {
                //     print ": vom Lieferschein übernehmen!";
                // }
                // print "<input type='hidden' value='$unit_weight' id='input-$id_article-unit-weight'>\n";
            } else {
                print "erhalten: ";

                $input = new form_input();
                $input->set_name($this->var_name("received"));
                $input->set_init_value($this->received);
                $input->set_class("number");
                // $input->set_update_function(sprintf(
                //     "updateTotalReceived($id_article, %d, /*update_weight=*/ true); ",
                //     $unit_weight
                // ) . "ajaxOnChange(this);");

                $input->set_null_button($not_delivered, $this->received);
                $input->set_reset_button($this->received);
                $input->set_buttons_on_both_sides();
                $input->set_article_name(sprintf("%d x %s", $this->received, $this->name));
                $input->print();
            }
        } else {

        }

        print "</p>";
    }

    public function html_buttons()
    {
        print html_tag(
            "p",
            [],
            html_button("&ddarr; Für alle 'erhalten' auf 'bestellt' setzen", "", "")
        );
        if ($this->n_grouporders > 1) {
            if ($this->has_adaptable_weight) {
                print html_tag(
                    "p",
                    [],
                    html_button("&ddarr; Gesamtgewicht auf Gruppen aufteilen", "", "")
                );
                print html_tag(
                    "p",
                    [],
                    html_button("&uuarr; Gesamtgewicht von Gruppen übernehmen", "", "")
                );
            } else {
                print html_tag(
                    "p",
                    [],
                    html_button("&uuarr; Gesamtanzahl von $this->n_grouporders Gruppen übernehmen", "", "")
                );
            }
        }

    }
    public function html_group_orders()
    {
        $table = [];
        foreach ($this->grouporders as $id => $grouporder) {
            $received = $grouporder["received"];
            $weight_received = $grouporder["weight_received"] ?? 0;
            if ($this->app->edit_received) {
                if ($this->has_adaptable_weight) {
                    $input = new form_input();
                    $input->set_name("weight_received_grouporder[$id]");
                    $input->set_init_value($weight_received);
                    //$input->set_id("received-$this->id");
                    $input->set_class("weight");
                    // $input->set_update_function(
                    //     "updateReceived($id_article, update_weight=true, $id_ordergroup); " .
                    //     "ajaxOnChange(this);"
                    // );
                    $input->set_buttons_on_both_sides();
                    $weight_received = $input->html();
                } else {
                    $input = new form_input();
                    $input->set_name("received_grouporder[$id]");
                    $input->set_init_value($received);
                    //$input->set_id("received-$this->id");
                    $input->set_class("number");
                    // $input->set_update_function(
                    //     "updateReceived($id_article, update_weight=true, $id_ordergroup); " .
                    //     "ajaxOnChange(this);"
                    // );
                    $input->set_buttons_on_both_sides();
                    $received = $input->html();
                }
            }
            $table[] = [
                "checkbox" => html_checkbox(
                    "distributed[]",
                    $id,
                    "checkbox-$id",
                    "ajaxOnChange(this);",
                    true,
                    $this->is_distributed
                ),
                "ordered" => $grouporder["ordered"],
                "received" => $received,
                "weight_ordered" => $grouporder["weight_ordered"] ?? "",
                "weight_received" => $weight_received,
                "group_name" => $grouporder["name"],
            ];

        }
        $this->app->html_table($table, array_filter([
            "checkbox" => " ",
            "ordered" => "bestellt",
            "weight_ordered" => $this->has_adaptable_weight ? " " : "",
            "received" => $this->has_adaptable_weight ? "" : "erhalten",
            "weight_received" => $this->has_adaptable_weight ? "Gramm erhalten" : "",
            "group_name" => "Bestellgruppe",
        ]), ["received" => $this->has_adaptable_weight ? "%.2f" : "%.0f"]);
    }
}
?>