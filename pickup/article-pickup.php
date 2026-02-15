<?php
require_once "article.php";
class ArticlePickup extends Article
{
    public function __construct($order, $data)
    {
        parent::__construct($order, $data);
        $this->ordered = intval($data["ordered"]);
        $this->tolerance = intval($data["tolerance"]);
        $this->received = floatval($data["received"]);
        $this->set_state();
        $this->finalize_construct();
    }

    public function html_form()
    {
        $classes = [
            "article",
        ];
        if ($this->not_received)
            $classes[] = "disabled";
        elseif ($this->has_variable_weight)
            $classes[] = "mandatory";
        if ($this->order->week >= 2)
            $classes[] = "week2+";
        if ($this->order->state == "closed")
            $classes[] = "closed";

        print '<p ' .
            ' class="' . implode(" ", $classes) . '" ' .
            ' id="p-article-' . $this->id . '"' .
            ' data-order-id="' . $this->order->id . '" ' .
            ' data-pickup-date-index="' . $this->order->pickup_date_index . '" ' .
            '>'; {
            $this->html_checkbox();
            $this->html_name();
            print "<br>\n";

            $this->html_unit_and_price();
            print "<br>\n";

            $this->html_ordered();
            $this->html_received();
            print "<br>\n";

            $this->html_note();

            $this->html_hidden_input("order_article_ids[" . $this->order->id . "][]", "ID");
            if ($this->is_distributed)
                $this->html_hidden_input("distributed[]", "ID");
        }
        print "</p>";
    }


    private function html_checkbox()
    {
        $classes = ["checkbox"];
        if ($this->order->ready_for_pickup)
            $classes[] = "ready-for-pickup";
        print html_tag("input", [
            "type" => "checkbox",
            "class" => $classes,
            "name" => "checked[]",
            "id" => "checkbox-" . $this->id,
            "value" => $this->id,
            "onChange" => "update_article_visibility(" . $this->id . ")",
            ($this->is_checked ? "checked" : ""),
        ]);
        if ($this->is_checked) {
            print " <input type='hidden' name='checked_initial[]' value='" . $this->id . "'>\n";
        }
    }

    public function html_name()
    {
        print "<b>" . $this->name . "</b>";
        parent::html_name();
    }


    private function html_unit_and_price()
    {
        print "Einheit: " . $this->unit;
        if ($this->price)
            print " zu " . $this->price_str;
        if ($this->deposit) {
            print $this->price ? " + " : ", ";
            print "Pfand " . $this->app->local_currency_str(abs($this->deposit)) . " ";
        }
        if (
            $this->unit_weight > 0 &&
            $this->price > 0 &&
            $this->unit_weight != 1000
        ) {
            print " (" . $this->app->local_currency_str($this->price_per_kg) . "/kg)";
        }
        $this->html_hidden_input("unit", $this->unit);
        $this->html_hidden_input("unit_weight", $this->unit_weight);
        $this->html_hidden_input("price", $this->price);
    }

    public function html_ordered()
    {
        print $this->order->ordered_term . ": " . $this->ordered;
        $this->html_hidden_input("ordered", $this->ordered);
        $this->html_hidden_input("tolerance", $this->tolerance);
        if ($this->unit_weight > 0) {
            $this->html_hidden_input("weight_ordered", $this->weight_ordered);
        }
    }

    private function html_received()
    {
        $this->html_hidden_input("received_initial", $this->received);

        if ($this->order->is_open)
            return; // no adaptions for open orders

        print ", ";
        $this->html_hidden_input("has_variable_weight", $this->has_variable_weight);
        if ($this->order->is_closed) {
            print "abgerechnet: " . $this->received;
            if ($this->has_variable_weight) {
                print ", " . $this->weight_received . " Gramm";
            }
            return;
        }

        if ($this->has_variable_weight) {
            if ($this->adapted_received)
                print "erhalten: " . $this->received;
            print "<br>";
            print "Gewicht bestellt: " . $this->weight_ordered . " Gramm<br>";
            $this->html_weight_input();
        } else {
            $this->html_number_input();
            if ($this->has_adaptable_weight) {
                $this->html_optional_weight_input();
            }
        }

        if ($this->unit_weight > 0) {
            $this->html_hidden_input("weight_received_initial", $this->weight_received);
        }

        // todo: warning if pickup is in future
    }



    private function html_optional_weight_input()
    {
        $button_id = "button-weight-optional-" . $this->id;
        $weight_received_id = "weight-received-" . $this->id;
        $weight_input_id = "weight-adaption-" . $this->id;
        $on_click =
            "document.getElementById('$weight_input_id').style.display = ''; " .
            "document.getElementById('$button_id').style.display = 'none'; ";
        print "<br><span id='$weight_received_id'>";
        if ($this->adapted_received) {
            print "Gewicht erhalten: " . $this->weight_received . " Gramm ";
            $on_click .= "document.getElementById('$weight_received_id').style.display = 'none';";
        } else {
            print "Gewicht bestellt: " . $this->weight_ordered . " Gramm ";
        }
        print html_button("abweichendes Gewicht eingeben", $button_id, $on_click);
        // print '<button type="button" ' .
        //     ' onclick="' . $on_click . '" ' .
        //     "id='$button_id'>" .
        //     'Abweichendes Gewicht eingeben</button>';
        print "<br></span>";
        $this->html_weight_input("display:none");
    }

    private function html_weight_input($style = "")
    {
        print "<span style='$style' id='weight-adaption-" . $this->id . "'>";
        print "Gewicht erhalten: ";
        $input = new form_input(); # Gewichtsabweichung
        $input->set_name($this->var_name("weight_received"));
        $input->set_init_value($this->weight_received);
        $input->set_data_attribute("weight-ordered", $this->weight_ordered);
        $input->set_max_value($this->weight_ordered * 5);
        $input->set_class("weight unit");
        $input->set_update_function("update_weight($this->id)");
        $input->set_null_button($this->text_not_received, $this->reset_weight);
        $input->set_article_name(sprintf("%s", $this->name));
        //$input->set_buttons_on_both_sides();
        $input->print();


        if ($this->ordered > 1 && $this->has_variable_weight) {
            print '<br><span id="weight-separated-' . $this->id . '">' .
                '<button type="button"
                    onclick="show_individual_weight_inputs(' . $this->id . ',' . $this->ordered . ')">' .
                'Gewicht für jedes Stück extra eingeben</button><br></span>' . "\n";
            for ($i = 0; $i < $this->ordered; $i++) {
                $idnr = $this->id . "-$i";
                print "<span id='single-weight-$idnr' style='display: none'>" .
                    "Gewicht Stück " . ($i + 1) . ": " .
                    "<input class='weight' type='text' name='single_weight[" . $this->id . "][$i]' " .
                    " id='weight-$idnr' size='2' onChange='calculate_sum(" . $this->id . "," . $this->ordered . ")'>
                    Gramm" .
                    "<br></span>\n";
            }
        }
        print "</span>";
    }

    private function html_number_input()
    {
        print "erhalten: ";
        $input = new form_input(); # Anzahl Artikel
        $input->set_name($this->var_name("received"));
        $input->set_init_value($this->received);
        $input->set_data_attribute("ordered", $this->ordered);
        $input->set_class("number");
        $input->set_update_function("update_received(" . $this->id . ")");
        $input->set_null_button($this->text_not_received, $this->reset_received);
        $input->set_article_name(sprintf("%d x %s", $this->reset_received, $this->name));
        //$input->set_buttons_on_both_sides();
        $input->print();
    }

    private function html_note()
    {
        print '<button type="button" ' .
            ' onclick="show_note(' . $this->id . ',true)" ' .
            ' id="note-button-show-' . $this->id . '">' .
            'Notiz eingeben' .
            '</button>';
        print '<span id="note-' . $this->id . '" style="display:none">';
        $text = "Hinweis zur Abrechnung:";
        if ($this->order->state == "closed")
            $text .= " (Änderungen in erhaltener Menge können nicht mehr " .
                "berücksichtigt werden, weil die Bestellung bereits abgerechnet ist!)";
        print '<span class="info">' . $text . '</span><br>';
        print '<textarea type="text" ' .
            ' id="note-textarea-' . $this->id . '" ' .
            ' name="' . $this->var_name(" note") . '" ' . 'rows=3 cols=28>' . $this->note .
            '</textarea><br>';
        print '<button type="button" ' .
            ' onclick="show_note(' . $this->id . ', false)" ' .
            ' id="note-button-hide-' .
            $this->id .
            '">' .
            'Notiz verbergen' .
            '</button>';
        print '</span>';
        $this->html_hidden_input("note_initial", $this->note);
    }
}
?>