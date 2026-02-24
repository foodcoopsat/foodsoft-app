<?php
require_once "order.php";
class OrderPickup extends Order
{
    public $grouporder_id;
    public $ready_for_pickup;
    public function __construct($app, $data)
    {
        parent::__construct($app, $data);

        // print "<pre>";
        // print_r($data);
        // print "</pre>";

        $this->grouporder_id = $this->id;
        $this->id = $data["order_id"];
        $this->ready_for_pickup =
            $this->has_pickup_date && $this->days_in_past >= 0 ||
            $this->is_open || $this->is_stock_order;

    }

    public function create_article($article_data)
    {
        return new ArticlePickup($this, $article_data);
    }

    function html()
    {
        $order_class = "order-" . $this->id;
        print "<h3 class='order $order_class'>" . $this->producer . "</h3>";

        if ($this->info_text) {
            print "<p class='info $order_class'>$this->info_text</p>";
        }

        if ($this->is_open) {
            print "<p class='info'>Bestellung ist noch offen - Abweichungen bitte in der ";
            $this->app->html_foodsoft_url( // "/group_orders/37402/edit?order_id=6228"
                "/group_orders/" . $this->grouporder_id .
                "/edit?order_id=" . $this->id,
                "Foodsoft-Bestellung"
            );
            print " eingeben!</p>";

        }
        if ($this->app->show_only_received_orders) {
            if (!$this->is_received) {
                print html_tag(
                    "p",
                    ["class" => ["info", $order_class]],
                    "Bestellung ist noch nicht freigegeben. " .
                    "Bestellt wurde: " .
                    implode(", ", $this->article_names())
                );
                return;
            }
        }

        $this->html_hidden_input("producer", $this->producer);
        $this->html_hidden_input("date", $this->date_pickup);
        $this->html_hidden_input("is_closed", $this->is_closed);

        foreach ($this->articles as $article_data) {
            $this->create_article($article_data)->html_form();
        }
    }

    public function article_names()
    {
        $names = [];
        foreach ($this->articles as $article_data) {
            $names[] = $this->create_article($article_data)->name;
        }
        return $names;
    }



}