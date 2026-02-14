<?php
require_once "order.php";
class OrderSubmitted extends Order
{
    public $updates = [];
    public $notes = [];
    public function __construct($app, $id)
    {
        $this->app = $app;
        $this->id = $id;
        $this->producer = $app->post["producer"][$this->id];
        $this->date_end = $app->post["date"][$this->id];
        $this->is_closed = $app->post["is_closed"][$this->id];
        if ($app->realname != $app->username) {
            $this->notes[] = "eingegeben von " . $app->realname;
        }
    }

    public function add_update($article)
    {
        $this->updates[$article->id] = $article->update; // update maybe empty array []
    }

    public function updates()
    {
        return array_filter($this->updates); // drop articles without updates
    }


    public function add_note_items($article)
    {
        $this->notes[] = $article->note_items();
    }
    public function notes()
    {
        return implode("\n", array_filter($this->notes));
    }

    public function submit_updates()
    {
        $updates = [
            "updates" => $this->updates(),
            "comment" => $this->notes()
        ];

        if ($updates["updates"] || $updates["comment"]) {

            if ($this->app->debug) {
                print "<pre style='background-color: #EEE;'>";
                print "updates for order $this->id: ";
                print_r($updates);
                $result = $this->app->submit_order_updates($this->id, $updates);
                if (true) {
                    print "result: ";
                    print_r($result ?: "empty == success!\n");
                } else {
                    print "submission to foodsoft disabled for debugging.\n";
                }
                print "---------------------------------------------------------------\n";
                print "</pre>";
            } else {
                $result = $this->app->submit_order_updates($this->id, $updates);
            }
            // todo: error handling! $result = ["error" => ...?]
        }
    }
}
?>