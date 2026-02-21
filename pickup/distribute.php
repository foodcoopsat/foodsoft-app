<?php
require_once("foodsoft-api-app.php");
require_once("order-distribute.php");
require_once("article-distribute.php");
require_once("html-helpers.php");

class DistributeApp extends FoodsoftApiApp
{
    public $order_ids;
    public $ajax_timeout = 100;

    public function needs_api()
    {
        return !in_array($this->action, ["ajax-write", "ajax-read"]);
    }
    public function __construct($config)
    {
        parent::__construct($config);

        if (str_contains($this->action, "ajax")) {
            $this->handle_ajax();
            exit;
        }

        $this->title = "Einkistln";
        $this->order_ids = $this->post["order_ids"] ?? [];

        $this->html_header([
            "distribute.js",
            "input.js",
        ], [
            #"onload" => "init()",
        ]);
        $this->html_title();
        if (!$this->order_ids) {
            $this->html_distribute_preselect();
        } else {
            $this->load_protocolls();
            $this->html_distribute_form();
        }
        $this->html_footer();
    }

    public function handle_ajax()
    {
        if ($this->action == "ajax-write") {
            $this->save_protocoll($this->get["ajax-data"]);
        } elseif ($this->action == "ajax-read") {
            $from_event = $this->get['from_event'];
            $n_tries = 0;
            do {
                $new_events = $this->load_protocoll_json(0, $from_event);
                sleep(1);
            } while (count($new_events) == 0 && $n_tries++ < $this->ajax_timeout);
            print implode("\n", $new_events);
        }
    }


    public function get_foodsoft_orders($order_ids = null, $stock_orders = true)
    {
        $url = $this->api_url . "/orders" .
            ($order_ids ?
                "?ids=" . implode(
                    ",",
                    array_map('strval', $order_ids)
                )
                : ""
            );
        // print "api-url: $url\n";
        $data = $this->api->getResource($url);
        if ($stock_orders) {
            $orders = $data["orders"];
        } else {
            $orders = array_filter($data["orders"], function ($order) {
                // print_r($order);
                // print $order["name"] != "Lager" ? "kein Lager" : "ist Lager";
                // print "\n";
                return $order["name"] != "Lager";
            });
        }

        $this->set_orders($orders);
        // print "<pre>";
        // print_r($this->orders);
        // print "------------------------------------\n\n</pre>";
    }

    public function create_order($data)
    {
        return new OrderDistribute($this, $data);
    }


    public function html_select_orders()
    {
        $this->get_foodsoft_orders(null, false);

        print "<h2>Bestellungen auswählen</h2>";
        print "<p class='info'>Bitte wähle aus, welche Bestellung(en) du einkistln möchtest:</p>\n";
        print "<p><b>Abholdatum - Lieferantin - Datum Bestellende</b></p>\n";

        foreach ($this->orders_by_date as $date_str => $order_indices) {
            # $date_index = $this->orders_by_date_index[$date_str];
            $class = $this->orders_days_in_past[$date_str] > 0 ? "past-order" : "";
            print "<h3 class='$class'>" . $date_str . "</h2>\n";
            foreach ($order_indices as $i) {
                $order = new OrderDistribute($this, $this->orders[$i]);
                # $order->pickup_date_index = $date_index;
                print "<p class='$class'>";
                $px = 30;
                $style =
                    "width: " . $px . "px; " .
                    "height: " . $px . "px; " .
                    "vertical-align: -40%; ";
                print "<input type='checkbox' " .
                    "name='order_ids[]' " .
                    "value='$order->id' " .
                    "style='$style' " .
                    ($order->distribute &&
                        $order->days_in_future >= 0 &&
                        $order->days_in_future < 7 ?
                        "checked" : "") .
                    "> ";
                print $order->producer . " vom " . $order->date_end; //substr($order->date_end, 0, 6) . "";
                print "</p>";
            }
        }
        print html_button("ältere Bestellungen anzeigen", "show-more", "show_more_orders()");
    }






    public function html_distribute_preselect()
    {
        $this->html_form_begin("");
        $this->html_select_user("EinkistlerIn", false);
        $this->html_select_orders();
        $this->html_form_end("Einkistln starten");
    }

    public function html_distribute_form()
    {
        $this->get_foodsoft_orders($this->order_ids);

        // print "<pre>";
        // print_r($this->orders);
        // exit;

        foreach ($this->orders as $order_data) {
            $order = new OrderDistribute($this, $order_data);
            $order->html_heading();

            foreach ($order->articles as $article_data) {
                $article = $order->create_article($article_data); //new Article($order, $article_data);

                $article->html_name();
                print "<p>"; {

                    $article->html_ordered();

                    $ordergroups = [];
                    foreach ($article->grouporders as $go) {
                        $ordergroups[] = $go["name"] . ": " . $go["ordered"] . " => " . $go["received"];
                    }
                    print html_list($ordergroups);
                }
                print "</p>";
            }
        }
    }

}
