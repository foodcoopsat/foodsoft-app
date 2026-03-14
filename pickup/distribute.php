<?php
require_once("foodsoft-api-app.php");
require_once("order-distribute.php");
require_once("article-distribute.php");
require_once("html-helpers.php");

class DistributeApp extends FoodsoftApiApp
{
    public $order_ids;
    public $edit_received;
    public $ajax_timeout = 100;
    public $index;

    public function needs_api()
    {
        return !in_array($this->action, ["ajax-write", "ajax-read"]);
    }

    public function __construct($config)
    {
        parent::__construct($config);

        if (str_contains($this->action, "ajax")) {
            $this->handle_ajax($this->action);
            exit;
        }

        $this->title = "Einkistln";
        $this->order_ids = $this->post["order_ids"] ?? [];
        $this->edit_received = $this->post["edit_received"] ?? true;

        if (!$this->order_ids) {
            $this->html_header([], []);
            $this->html_title();
            $this->html_distribute_preselect();
        } else {
            $this->html_header([
                "../distribute.js",
                "../input.js",
            ], [
                "onload" => "start_update('$this->username', $this->ajax_timeout)",
            ]);
            $this->html_title();
            $this->load_protocolls();
            $this->html_distribute_form();
            $this->set_index();
            $this->html_bottom_bar();
        }
        $this->html_footer();
    }

    public function handle_ajax($action)
    {
        if ($action == "ajax-write") {
            $ajax_data = $this->get["ajax-data"]; //json encoded array
            $this->save_protocoll($ajax_data);
        } elseif ($action == "ajax-read") {
            $from_event = $this->get['from_event'] ?? 0;
            if ($from_event == -2) { // load all weeks, including current week
                print implode("\n", $this->load_protocolls(true, true));
            } elseif ($from_event == -1) { // load all weeks, excluding current week
                print implode("\n", $this->load_protocolls(true, false));
            } else {
                $n_tries = 0;
                do {
                    $new_events = ($from_event == -1) ?
                        $this->load_protocolls(true) :
                        $this->load_protocoll_json(0, $from_event);
                    sleep(1);
                } while (count($new_events) == 0 && $n_tries++ < $this->ajax_timeout);
                print implode("\n", $new_events);
            }
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
            print html_tag(
                "h3",
                ["class" => $class],
                $date_str
            );
            foreach ($order_indices as $i) {
                $order = new OrderDistribute($this, $this->orders[$i]);
                # $order->pickup_date_index = $date_index;
                print html_tag(
                    "p",
                    ["class" => $class],
                    html_checkbox(
                        "order_ids[]",
                        $order->id,
                        "checkbox-$order->id",
                        "",
                        true,
                        $order->distribute &&
                        $order->days_in_future >= 0 && $order->days_in_future < 7
                    ) .
                    $order->producer . " vom " . $order->date_end
                );
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


    public function set_index()
    {
        $this->index = [];
        foreach ($this->orders as $order_data) {
            $order = $this->create_order($order_data);
            $this->index["_order-" . $order->id] = " "; // spacer
            $this->index["order-" . $order->id] = "=== $order->producer =====";
            $order->sort_articles();
            foreach ($order->articles as $article_data) {
                $this->index["article-" . $article_data["id"]] = str_repeat("&nbsp;", 2) . $article_data["name"];
            }
        }
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
                $article = $order->create_article($article_data);
                $article->html_name();
                $article->html_ordered();
                $input_id = $article->html_received();
                $article->html_note("Notiz für alle eingeben", "Hinweis an alle, die diesen Artikel bestellt haben:");
                $article->html_buttons($input_id);
                $article->html_group_orders();
                $article->html_difference();
                $article->html_update_received($input_id);
                $order->html_article_index(5);
            }
        }
    }
    public function html_bottom_bar()
    {
        print html_tag(
            "div",
            [
                "class" => "searchbar",
                "style" => [
                    "position: fixed;",
                    "bottom: 0;",
                    "width: 100%;",
                    "padding: 16px 8px;",
                    "background-color: #EEE;",
                    "z-index: 999;"
                ]
            ],
            html_tag(
                "div",
                ["style" => "float:left"],
                html_select("index", ["0" => "-- Artikel/Bestellung auswählen --"] + $this->index)
            ) .
            html_tag(
                "div",
                ["style" => "float:right"],
                '<span id="seconds"></span>' .
                '<span id="sync-status"></span>' .
                str_repeat("&nbsp;", 5)
            )
        );
        print html_tag("div", ["style" => "padding-bottom: 100px;"], "<!--  margin-bottom -->");
    }

}
