<?php
require_once "article.php";
class ArticleSubmitted extends Article
{
    public $update = [];
    public $single_weights;
    public $note_items; // array of strings for note entries

    public function __construct($order, $id)
    {
        $this->order = $order;
        $this->app = $order->app;
        $this->id = $id;
        $this->name = $this->get("article_name");
        $this->has_variable_weight = $this->get("has_variable_weight");
        $this->ordered = floatval($this->get("ordered"));
        $this->received = $this->get("received");
        if ($this->received === null) {
            $this->received = $this->ordered;
        } else {
            $this->received = floatval($this->received);
        }
        $this->unit = $this->get("unit");
        $this->unit_weight = floatval($this->get("unit_weight"));
        $this->price = floatval($this->get("price"));

        $this->weight_ordered = floatval($this->get("weight_ordered"));
        $this->weight_received = floatval($this->get("weight_received"));

        $this->single_weights = $this->get("single_weight") ?: [];
        $this->note_items = [];
    }

    public function get($property)
    {
        return $this->app->post[$property][$this->id] ?? null;
    }

    public function is_checked()
    {
        return in_array($this->id, $this->app->post["checked"] ?? []);
    }
    public function was_checked()
    {
        return in_array($this->id, $this->app->post["checked_initial"] ?? []);
    }
    public function checked_has_changed()
    {
        return $this->is_checked() != $this->was_checked();
    }

    public function is_distributed()
    {
        return in_array($this->id, $this->app->post["distributed"] ?? []);
    }


    public function has_changed($property, $digits = null)
    {
        return $this->is_different($property, $property . "_initial", $digits);
    }

    public function is_different($property1, $property2, $digits = null)
    {
        $id = $this->id;
        $post = $this->app->post;
        if (!isset($post[$property1][$id]) || !isset($post[$property2][$id])) {
            return false; // if one of the values is not set, we consider it as not different
        }
        $value1 = $post[$property1][$id];
        $value2 = $post[$property2][$id];
        if ($digits === null) {
            return $value1 != $value2;
        } else {
            $difference = abs(round($value1, $digits) - round($value2, $digits));
            $tolerance = pow(10, -$digits);
            return $difference > $tolerance;
        }
    }
    public function is_equal($property1, $property2, $digits = null)
    {
        return !$this->is_different($property1, $property2, $digits);
    }


    public function update_status()
    {
        // use tolerance to save pickup information
        $this->update["tolerance"] =
            ($this->get("tolerance") ?? 0) +
            ($this->is_distributed() ? $this->app->base_distribution : 0) +
            ($this->is_checked() ? $this->app->base_pickedup : 0);

        // alternative: use database field
        // $update["state"] = ...
    }

    public function update_received()
    {
        $this->update["result"] = $this->get("received");
    }

    public function update_received_weight()
    {
        $this->update["result"] = $this->received =
            $this->get("weight_received") /
            $this->get("unit_weight");
    }


    public function add_note_received()
    {
        $received_numbers = [$this->ordered];
        if ($this->is_different("received_initial", "ordered")) {
            $received_numbers[] = $this->get("received_initial");
        }
        $received_numbers[] = $this->get("received");
        $received_numbers = implode(" => ", $received_numbers);
        $this->note_items[] = $received_numbers;
        return $received_numbers;
    }

    public function add_note_received_weight()
    {
        $weight_numbers = [$this->get("weight_ordered")];
        if ($this->is_different("weight_received_initial", "weight_ordered", 0)) {
            $weight_numbers[] = $this->get("weight_received_initial");
        }
        $received = $this->get("weight_received");
        if ($this->single_weights) {
            $received .= " (" . implode("+", $this->single_weights) . ")";
        }
        $weight_numbers[] = $received;
        $weight_numbers = implode(" g => ", $weight_numbers) . " g";
        $this->note_items[] = $weight_numbers;
        return $weight_numbers;
    }

    private function has_notes()
    {
        // if (count($this->note_items) > 0) {
        //     print "<pre>";
        //     print "note items for article $this->id: ";
        //     print_r($this->note_items);
        //     print "</pre>";
        // }

        return count($this->note_items) > 0;
    }
    public function add_note_for_article()
    {
        if (
            $this->has_changed("note") ||
            $this->has_notes() && $this->get("note")
        ) {
            // alt:
            // $this->note_items[] = "@ " . $this->get("note") . " #" . $this->id;

            // neu: ... @123456 Kommentar zum Artikel
            $this->note_items[] = "@" . $this->id . " " . $this->get("note");
        }
    }

    public function note_items()
    {
        return $this->has_notes() ?
            $this->name . ": " . implode(" ", $this->note_items) :
            "";
    }

    public function html_order_and_article_name()
    {
        return
            $this->order->date_end . " " . $this->order->producer . "<br>" .
            "<b>" . $this->name . "</b><br>";
    }

    public function html_changes()
    {
        $html = "";
        if ($this->has_notes()) { // has_notes -> changes were submitted
            $html = "<p class='article'>";

            $html .= $this->html_order_and_article_name();

            $html .= "bestellt: " . implode(" ", $this->note_items) . " erhalten.<br>";

            $price_ordered = $this->get("price") * $this->get("ordered");
            $price_received = $this->get("price") * $this->received;
            $price_difference = $price_received - $price_ordered;
            $html .= "Preis: " .
                $this->app->local_currency_str($price_ordered) . " => " .
                $this->app->local_currency_str($price_received) . " " .
                "(" . $this->app->local_currency_str($price_difference, $plus_sign = true) . ")";
            $html .= ".<br>";

            if ($this->get("note"))
                $html .= "Notiz zum Abrechnen: " . $this->get("note");

            $html .= "</p>";

        } elseif (
            $this->has_variable_weight &&
            !$this->order->is_closed && // if order is closed, weight_ordered and weight_received are not submitted!
            $this->is_equal("weight_ordered", "weight_received", 0)
        ) {
            // no different weight submitted!
            $html = "<p class='article warning'>";

            $html .= "<span class='info'>Achtung, du hast kein Gewicht eingegeben. " .
                "Wenn das Gewicht so stimmt, kannst du diese Warnung ignorieren:</span><br>";

            $html .= $this->html_order_and_article_name();

            $html .= "bestellt: ";
            if ($this->ordered > 1) {
                $html .= $this->ordered . " x " . $this->get("unit_weight") . " g = ";
            }
            $html .= $this->get("weight_ordered");
            $html .= ".<br>";

            $price_ordered = $this->get("price") * $this->get("ordered");
            $html .= "Preis: " .
                $this->app->local_currency_str($price_ordered);
            $html .= ".<br>";

            if ($this->get("note"))
                $html .= "Notiz zum Abrechnen: " . $this->get("note");

            $html .= "</p>";

        } elseif ($this->has_changed("note")) {
            // no changes in number or weight submitted, but note!
            $html = "<p>";

            $html .= $this->html_order_and_article_name();

            $html .= "bestellt: " . $this->ordered;
            $html .= " => " . $this->received . " erhalten.";
            $html .= "<br>";

            $html .= "Notiz zum Abrechnen: " . $this->get("note");

            $html .= "</p>";
        }
        return $html;
    }

    public function html_unchecked()
    {
        if ($this->is_checked())
            return "";
        $html = $this->order->date_end . " " . $this->order->producer . ": <nobr>";
        $html .= $this->received . " x <b>" . $this->name . "</b></nobr>";
        return $html;
    }

}
?>