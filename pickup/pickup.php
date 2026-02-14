<?php
require_once("foodsoft-api-app.php");
require_once("order-pickup.php");
require_once("article-pickup.php");
require_once("order-submit.php");
require_once("article-submit.php");



class PickupApp extends FoodsoftApiApp
{
    public $credit;
    public $n_pickedup_initially = 0;
    public $articles_not_pickedup = [];
    public $protocoll = [];
    public function __construct($config, $need_api = true)
    {
        if ($need_api) {
            parent::__construct($config);
        } else {
            $this->config = $config;
        }

        $this->title = "Abholen";

        if ($this->action == "") {
            $this->html_header([
                "pickup.js",
                "input.js",
            ], [
                "onload" => "init()",
                "onbeforeunload" => "return before_unload()",
            ]);
            $this->html_title();
            if ($this->has_current_user_ordergroup()) {
                $this->html_pickup_form();
            } else {
                $this->html_form_begin("");
                $this->html_select_user("Abholer*in", true);
                $this->html_form_end("Weiter");
            }
        } elseif ($this->action == "submit") {
            $this->html_header();
            $this->html_title();
            $this->html_submit();
        } else {
            print html_tag(
                "p",
                ["class" => "error"],
                "Unbekannte Aktion: " . $this->action
            );
        }

        $this->html_footer();
    }
    public function get_foodsoft_group_orders($ordergroup_id = null)
    {
        $url = $this->api_url;
        if ($ordergroup_id)
            $url .= "/?ordergroup_id=$ordergroup_id";
        $data = $this->api->getResource($url);
        if ($data == NULL) {
            print 'getResource($access_token, "' . $url . '") => NULL' . "\n";
            if ($this->config["use_local_foodsoft"] ?? false) {
                print "lokale Foodsoftinstanz nicht gestartet?";
            } else {
                print "Fehler bei der Verbindung zum Foodsoft-Server?";
            }
            exit();
        }
        $this->set_orders($data["group_orders"]);
    }


    public function create_order($data)
    {
        return new OrderPickup($this, $data);
    }


    public function html_pickup_form()
    {
        $this->html_form_begin("submit", "check_form");

        print html_list([
            "Abhaken, was du abgeholt bzw. überprüft hast. Wenn nicht anders vermerkt, " .
            "findest du die bestellten Artikel in deinem Kistl.",

            "Gegenüber Bestellung abweichende Stückzahlen und Gewichte eingeben",

            "Abgehaktes speichern und Abweichungen in die Foodsoft übertragen zur " .
            "Berücksichtigung bei der Abrechnung: ganz unten"
        ], true);

        print "<p>";
        print "Benutzer*in: $this->username<br>";
        print "Bestellgruppe: $this->ordergroup<br>";
        print "<b>Dein Name:</b> ";
        print html_tag(
            "input",
            [
                "type" => "text",
                "name" => "realname",
                "value" => $this->username
            ]
        );
        print html_hidden_input("username", $this->username);
        print html_hidden_input("ordergroup", $this->ordergroup);
        print "</p>";

        // print "      <p class='info'>Wenn du nicht $this->username bist, gib bitte zumindest deinen Vornamen ein. 
        //      Über die " . $this->foodcoop->html_ahref("home/ordergroup", "Foodsoft (Neue Person einladen)") .
        //     "kannst du einen persönlichen Zugang zu deiner Bestellgruppe anlegen, sodass hier
        //      automatisch dein richtiger Name erscheint.</p>\n";

        print "<p>";
        print $this->date_str("now", True) . "<br>";

        if (!$this->was_ordergroup_selected) {
            print "Verfügbares Guthaben: " .
                $this->local_currency_str($this->get_foodsoft_credit());
        }
        print "</p>";
        // print "was_ordergroup_selected: " . ($this->was_ordergroup_selected ? "true" : "false") . "<br>";
        $this->get_foodsoft_group_orders($this->was_ordergroup_selected ? $this->ordergroup_id : null);
        foreach ($this->orders_by_date as $date_str => $order_indices) {
            $date_index = $this->orders_by_date_index[$date_str];
            print html_tag(
                "h2",
                ["class" => "date", "id" => "date-$date_index"],
                $date_str
            );
            // print "<h2 class='date' id='date-$date_index'>" . $date_str . "</h2>\n";
            foreach ($order_indices as $i) {
                $order = $this->create_order($this->orders[$i]);
                $order->pickup_date_index = $date_index;
                $order->html();
            }
        }

        print "<p>";
        print html_button("zeige mehr Artikel", 'show-more', 'show_articles(5);');
        print html_button("zeige weniger Artikel", 'show-less', 'show_articles(1);');
        print "</p>";

        $this->html_form_end(
            "Speichern",
            "Die als abgeholt bzw. erledigt markierten Artikel sowie " .
            "die Änderungen von Stückzahl und Gewicht speichern:"
        );
    }






    // ==== process submitted form =========================================================


    private function submitted_order_article_ids()
    {
        return $this->post["order_article_ids"];
    }


    public function html_submit()
    {
        // process submitted form data: submit updates to foodsoft, display results 
        print '<p class="info">Folgende Änderungen wurden zur Abrechnung gespeichert.
                Änderungen, die vorher schon gespeichert wurden, werden nicht mehr angzeigt.
                Wenn du noch nicht fertig bist, oder du noch etwas ausbessern möchtest, geh nochmal zurück.
                Wenn du fertig bist, kannst du diese App bzw. diese Seite schließen. </p>';
        print "<hr>";

        $this->username = $this->post["username"];
        $this->realname = $this->post["realname"];
        $this->ordergroup = $this->post["ordergroup"];

        $html = [];
        $html_unchecked = [];
        foreach ($this->submitted_order_article_ids() as $order_id => $article_ids) {
            $order = new OrderSubmitted($this, $order_id);
            foreach ($article_ids as $article_id) {
                $article = new ArticleSubmitted($order, $article_id);
                if (
                    $article->has_changed("received", 2) &&
                    !$article->is_different("weight_received", "weight_ordered", 0)
                ) {
                    $article->update_received();
                    $article->add_note_received();
                }

                if ($article->has_changed("weight_received", 0)) {
                    $article->update_received_weight();
                    $article->add_note_received_weight();
                }

                $html[] = $article->html_changes(); // before add_note_for_article to exclude note_for_article

                $article->add_note_for_article();
                $this->add_to_protocoll($article);

                if ($article->checked_has_changed()) {
                    $article->update_status();
                }
                $html_unchecked[] = $article->html_unchecked();

                $order->add_update($article);
                $order->add_note_items($article);
            }
            $order->submit_updates(); // submit updates to foodsoft
        }

        $html = array_filter($html);
        if (count($html) == 0) {
            $html = [
                "<p class='info'>" .
                "Keine Änderungen von Stück oder Gewicht seit dem letzten Speichern.</p>"
            ];
        }

        $n_articles = $this->count("article_name");
        $unchecked_before = $this->count("checked_initial");
        $unchecked_after = $this->count("checked");
        $delta = sprintf("%+d", $unchecked_after - $unchecked_before);
        $html[] = "<p class='info'>" .
            "Deine abgehakten Artikel ($unchecked_after/$n_articles, Änderung: $delta) " .
            "wurden gespeichert.</p>";

        if ($html_unchecked = array_filter($html_unchecked)) {
            $html[] = "<h3>Nicht abgehakte Artikel:</h3>" .
                "<p class='info'>Nicht abgehakte Artikel werden beim Abrechnen genauso von deinem " .
                "Guthaben abgezogen wie abgehakte. Wenn du dir sicher bist, dass Artikel nicht geliefert wurden, " .
                "gehe nochmal zurück und setze die Zahl für Stück bzw. Gewicht bei 'empfangen:' auf Null " .
                "und hake die Artikel ab, im Sinne von 'ist für mich erledigt'.</p>" .

                html_list($html_unchecked, true);
        }

        print implode("\n", $html);

        print '<div style="float:left"><button onclick="history.back(1)">Zurück</button></div>';
        print '<div style="float:right"><a href="login.php"><button>Fertig</button></a></div>';
        print '<div style="clear:both"></div>';

    }

    public function submit_order_updates($order_id, $updates)
    {
        return $this->api->updateResource(
            $this->api_url . "/$order_id",
            $updates,
            $this->debug
        );
    }


    // ==== protocoll =========================================================

    public function add_to_protocoll($article)
    {
        if ($article->updates || $article->has_changed("note")) {
            $this->protocoll[] = [
                "username" => $this->username,
                "realname" => $this->realname,
                "ordergroup" => $this->ordergroup,
                "producer" => $article->order->producer,
                "order_date" => $article->order->date_end,
                "id" => $article->id,
                "name" => $article->name,
                "unit" => $article->unit,
                "price" => $article->price,
                "ordered" => $article->ordered,
                "received_initial" => $article->get("received_initial"),
                "received" => $article->received,
                "weight_ordered" => $article->weight_ordered,
                "weight_received_initial" => $article->get("weight_received_initial"),
                "weight_received" => $article->weight_received,
                "single_weights" => $article->single_weights,
                "note" => $article->get("note"),
            ];
        }
    }


    public function protocoll_filename($week = 0)
    {
        return "protocolls/pickup/" .
            date("Y-W", strtotime("-$week weeks")) .
            ".txt";
    }

}

?>