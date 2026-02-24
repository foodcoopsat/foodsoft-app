<?php

class Article
{
    public $app;
    public $data;
    public $order;
    public $id;
    public $name;
    public $price;
    public $price_str;
    public $deposit;
    public $ordered;
    public $tolerance;
    public $received;
    public $not_received;
    public $reset_received;
    public $adapted_received;
    public $unit;
    public $unit_weight;
    public $price_per_kg;
    public $weight_ordered;
    public $weight_received;
    public $reset_weight;
    public $has_weight;
    public $has_variable_weight;
    public $has_adaptable_weight;
    public $has_locked_weight;
    public $locked_weight;
    public $is_pickedup;
    public $is_distributed;
    public $note;
    public $text_not_received = "nicht erhalten|doch erhalten";

    public function __construct($order, $data)
    {
        $this->order = $order;
        $this->app = $order->app;
        $this->data = $data;

        $this->id = $data["id"];
        $this->name = $data["name"];

        $this->price = floatval($data["price"]);
        $this->price_str = $this->app->local_currency_str($this->price);
        $this->deposit = floatval($data["deposit"] ?? "0");

        $this->set_unit_weight($data["unit"]);
        $this->has_variable_weight = str_contains(
            $this->name,
            $this->app->config["variable_weight"] ?? "*"
        );
        $this->set_locked_weight(
            $this->app->config["locked_weight"] ??
            ["#", "Glas"]
        );
        $this->has_adaptable_weight =
            $this->has_weight &&
            $this->order->has_adaptable_weights &&
            !$this->has_locked_weight;

        $this->note = $order->article_comments[$this->id] ??
            $this->app->article_notes[$this->id] ?? "";
    }


    public function set_state()
    {
        // there are multiple supported ways to save the articles' state,
        // all of them are supported in reading data, but may be overwritten by the later ones

        // $app->article_state_save_method == "foodsoft-db-tolerance"
        // use tolerance to save pickup information
        $tolerance_db = $this->tolerance;
        $this->tolerance = $tolerance_db % 1000;
        $this->is_distributed = intdiv($tolerance_db, $this->app->base_distribution) % 10;
        $this->is_pickedup = intdiv($tolerance_db, $this->app->base_pickedup) % 10;


        // $app->article_state_save_method ==  "foodsoft-db-article-state"
        // // alternative: use database field
        // todo: implemet it ...

        // $app->article_state_save_method == "in-app"
        $this->is_distributed = $this->app->articles_distributed[$this->id]["distributed"] ?? $this->is_distributed;
        $this->is_pickedup = $this->app->articles_pickedup[$this->id]["pickedup"] ?? $this->is_pickedup;
    }

    public function finalize_construct()
    {
        $this->not_received = ($this->received == 0);
        $this->reset_received = $this->received ?: $this->ordered;
        $this->adapted_received = ($this->received != $this->ordered);

        $this->weight_ordered = $this->unit_weight * $this->ordered;
        $this->weight_received = round($this->unit_weight * $this->received);
        $this->reset_weight = round($this->unit_weight * $this->reset_received);
    }

    private function set_locked_weight($items)
    {
        $this->has_locked_weight = FALSE;
        foreach ($items as $item) {
            if (str_contains($this->unit, $item)) {
                $this->has_locked_weight = TRUE;
                return;
            }
        }
    }
    private function set_unit_weight($unit)
    {
        // set unit weight (weight of one article entity) in gram from foodsoft unit-string
        // e.g. "Stück <1,3 kg"=> 1300 (g)
        $this->unit = $unit;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*kg\b/i', $unit, $m)) {
            // 2,5 kg => 2500
            $this->unit_weight = 1000 * floatval(str_replace(',', '.', $m[1]));
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*g\b/i', $unit, $m)) {
            // 250 g => 250
            $this->unit_weight = floatval(str_replace(',', '.', $m[1]));
        } else {
            $this->unit_weight = 0;
        }
        // printf("[%.0f g]", $this->unit_weight);
        if ($this->has_weight = ($this->unit_weight > 0))
            $this->price_per_kg = $this->price / $this->unit_weight * 1000;
    }

    public function var_name($var)
    {
        return $var . "[" . $this->id . "]";
    }

    public function html_hidden_input($varname, $value)
    {
        if ($value == "ID")
            $value = $this->id;
        else
            $varname = $this->var_name($varname);

        print "<input type='hidden' name='$varname' value='$value'>";
    }


    public function html_name()
    {
        $this->html_hidden_input("article_name", $this->name);
    }

}
?>