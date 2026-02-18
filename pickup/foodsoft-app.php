<?php

class FoodsoftApp
{
    public $app_name;
    public $action;
    public $config;
    public $title;
    public $request_uri;
    public $foodcoop_dirname;
    public $decimal_separator;
    public $post;
    public $get;
    public $foodcoop_name = "Unbekannte Foodcoop";
    public $username;
    public $realname;
    public $ordergroup;
    public $ordergroup_id;
    public $was_ordergroup_selected;
    public $credit;
    public $orders;
    public $orders_by_date;
    public $orders_by_date_index;
    public $orders_days_in_past;
    public $users;
    public $user_str_separator;
    public $time_now;
    public $n_weeks; // number of weeks from $time_now back to consider orders
    public $article_state_save_method = "in-app"; // "foodsoft-db-tolerance", "foodsoft-db-article-state", "in-app"
    public $articles_pickedup = [];
    public $articles_distributed = [];
    public $base_distribution = 10000;
    public $base_pickedup = 1000;
    public $protocoll_dir = "protocolls";
    public $protocoll = [];
    public $protocoll_last_modified = null;
    public $table_default_values = [];
    public $table_keys = [];
    public $table = [];
    public $debug;


    public function __construct($config)
    {
        global $_POST;
        global $_GET;

        $this->get = $_GET;
        $this->post = $_POST;

        $this->app_name = $this->get["app"] ?? "";
        $this->action = $this->get["action"] ?? "";

        $this->request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->foodcoop_dirname = basename($this->request_uri);
        $this->foodcoop_name = $config["foodcoop_name"] ?? ucfirst($this->foodcoop_dirname);

        $this->config = $config;
        $this->decimal_separator = $config["decimal_separator"] ?? ",";
        $this->user_str_separator = $config["user_str_separator"] ?? "|";
        $this->time_now = $config["time_now"] ?? "today";
        $this->n_weeks = $this->config["n_weeks"] ?? 5;


        $post_get = array_merge($this->post, $this->get);
        if ($this->was_ordergroup_selected = key_exists("username", $post_get)) {
            $items = explode($this->user_str_separator, $this->post["username"] ?? "");
            if (count($items) > 1) { // from user selection dropdown
                $this->username = $items[0];
                $this->ordergroup_id = $items[1];
                $this->ordergroup = $items[2];
            } else { // from hidden input after form submission (go back)
                $this->username = $post_get["username"];
                $this->ordergroup_id = $post_get["ordergroup_id"];
                $this->ordergroup = $post_get["ordergroup"];
            }
        } else {
            $this->username = null;
        }
        // print "<pre class='disabled'>";
        // print "items: ";
        // print_r($items);
        // print "_GET: ";
        // print_r($_GET);
        // print "username: $this->username";
        // print "</pre>";

        #ini_set("precision", 14);
        ini_set("serialize_precision", 14);
    }

    public function html_header($js_scriptfiles = [], $js_body_functions = [])
    {
        print "<!DOCTYPE html>\n";

        print html_tag("html", ["lang" => "de"]);

        print html_tag(
            "head",
            [],
            html_tag("meta", ["charset" => "UTF-8"]) .
            html_tag("meta", ["name" => "viewport", "content" => "width=device-width, initial-scale=1.0"]) .
            html_tag("title", [], $this->title . " " . $this->foodcoop_name) .
            html_tags(
                "link",
                "href",
                [
                    "../styles/normalize.css",
                    "../styles/fonts.css",
                    "../styles/global.css",
                ],
                [
                    "rel" => "stylesheet",
                    "type" => "text/css"
                ],
            ) .
            html_tags(
                "script",
                "src",
                $js_scriptfiles,
                ["type" => "text/javascript"],
                " "
            )
        );

        print html_tag("body", $js_body_functions);
    }

    public function html_footer()
    {
        print "</body>\n";
        print "</html>\n";
    }


    public function html_title()
    {
        print html_tag(
            "h1",
            [],
            $this->foodcoop_name . " " . $this->title
        );
    }


    public function html_select_user($addressee, $skip_users_without_ordergroup)
    {
        if ($this->was_ordergroup_selected || $this->has_current_user_ordergroup()) {
            print "<p>Hallo $this->username!<br>";
            print "<input type='hidden' id='username' name='username' value='" .
                implode($this->user_str_separator, [$this->username, $this->ordergroup_id, $this->ordergroup]) .
                "'>";
            return true;
        }

        // print "<p class='info'>Wir benötigen deinen Namen bitte für eventuelle Rückfragen.</p>\n";

        $this->get_foodsoft_users($skip_users_without_ordergroup);
        // print "<pre>";
        // print_r($pickup->users);
        // exit;

        print "<h2>Deinen Namen auswählen: Wer bist du?</h2>";
        print "<p>Hallo $addressee!<br>";
        print '<select name="username" id="username" >\n';
        // todo: preselect current user if possible
        print '<option value="none">-- bitte deinen Namen auswählen --</option>';
        $n_users = 0;
        foreach ($this->users as $user) {
            $uid = $user["id"];
            $name = trim($user["name"]);
            // print "user: '$name' ";
            // print_r($this->config["exclude_usernames"] ?? []);
            // print "<br>";
            if (in_array($name, $this->config["exclude_usernames"] ?? [])) {
                continue;
            }
            $ordergroup = trim($user["ordergroup_name"]);
            $oid = $user["ordergroup_id"];
            print "<option value='$name|$oid|$ordergroup'> $name ($ordergroup)</option>\n";
            $n_users++;
        }
        print "</select><br>";
        print "<small>Die Auswahlliste enthält $n_users Einträge.</small></p>";
        return false;
    }

    public function html_table($table, $headers)
    {
        // show html table of $table for all keys of $headers
        // $headers = [key=>table_head_for_key, ...]
        // $table rows can have optional entries for style classes and headings
        // data format is set according to keys ("price", ...) and data types (numbers, strings)

        $toc = [];
        foreach ($table as $row) {
            if ($row["heading"] ?? false) {
                $i = count($toc);
                $toc[] = html_tag("a", ["href" => "#h$i"], $row["heading"]);
            }
        }
        print html_list($toc);

        $table_start = " <table> <tr><th>" . implode("</th><th>", array_values($headers)) . "</th></tr>\n";
        $table_end = "</table>";

        $html = "";
        $is_table_started = false;
        $i_heading = 0;
        foreach ($table as $row) {
            if ($row["heading"] ?? false) {
                if ($is_table_started) {
                    $html .= $table_end;
                }
                $html .= html_tag("h2", ["id" => "h" . $i_heading++], $row["heading"]);
                $is_table_started = false;
            }
            if (!$is_table_started) {
                $html .= $table_start;
                $is_table_started = true;
            }

            $html .= html_tag("tr", ["class" => $row["class"] ?? ""]);
            foreach (array_keys($headers) as $key) {
                $data = $row[$key] ?? "";
                $align = "left";
                if (is_array($data)) {
                    $data = implode(", ", array_filter($data));
                } elseif (is_numeric($data)) {
                    $align = "right";
                    if (str_contains($key, "weight")) {
                        $data = sprintf("%.0f", $data);
                    } elseif (str_contains($key, "price")) {
                        $data = $this->local_currency_str(
                            $data,
                            str_contains($key, "diff")
                        );
                    } elseif (is_int($data) || $key == "id") {
                        $data = sprintf("%d", $data);
                    } else {
                        $data = $this->loc_floatstr(sprintf("%.2f", $data));
                    }
                }
                $html .= "    <td align='$align'>" . $data . "</td>";
            }
            $html .= "</tr>\n";
        }
        if ($is_table_started) {
            $html .= $table_end;
        } else {
            $html .= html_tag("p", ["class" => "info"], "Keine Einträge zum Anzeigen.");
        }
        print $html;
    }
    public function get_foodsoft_users($skip_users_without_ordergroup)
    {
        // to be overwritten by inherited class if needed
        $this->users = [];
    }

    public function load_article_pickup_states()
    {
        foreach ($this->load_data("pickup", "states", true) as $entry) {
            $this->articles_pickedup[$entry["id"]] = [
                "pickedup" => $entry["pickedup"],
                "date" => $entry["date"]
            ];
        }
    }

    public function has_current_user_ordergroup()
    {
        return $this->ordergroup_id != -1;
    }

    // --- submit helpers --------------------------------
    public function count($property)
    {
        // counts the number of non-empty array elemets of a submitted array 
        return count(array_filter($this->post[$property] ?? []));
    }


    // --- data and protocoll functions --------------------------------

    public function data_filename($app_name, $dir, $week = 0, $ordergroup = false)
    {
        return $app_name . "/$dir/" .
            date("Y/Y-W", strtotime("-$week weeks")) .
            ($ordergroup ? ($ordergroup === "*" ? "-*" : sprintf("-%04d", $this->ordergroup_id)) : "") .
            ".txt";
    }



    public function protocoll_entry($article = null)
    {
        // return protocoll entry for article
        // if no article is given, return the keys and default values for empty protocoll entries:
        //     called by generate_table_from_protocoll()
        // to be overwritten by inherited app classes 
        return [];
    }

    public function save_data($dir, $data, $ordergroup = false)
    {
        $path = $this->data_filename($this->app_name, $dir, 0, $ordergroup);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            print html_tag(
                "p",
                ["class" => "info"],
                "Daten-Verzeichnis existiert noch nicht, erstelle Verzeichnis '$dir'..."
            );
            mkdir($dir, 0777, true);
        }
        $file = fopen($path, "a");
        $n_tries = 0;
        while (!flock($file, LOCK_EX)) {
            usleep(100000); // Wait 100ms before retrying
            if (++$n_tries > 100) {
                // After 10 second of trying, give up to prevent infinite loop
                error_log("Could not acquire lock for protocoll file after 10 seconds.");
                return false;
            }
        }
        if (is_array($data)) {
            foreach ($data as $entry) {
                fwrite($file, json_encode($entry) . "\n");
            }
        } else { // protocoll is a json string 
            fwrite($file, $data . "\n");
        }

        flock($file, LOCK_UN);
        fclose($file);
        return true;
    }
    public function save_protocoll()
    {
        return $this->save_data($this->protocoll_dir, $this->protocoll);
    }

    public function load_data($app_name, $dir, $ordergroup)
    {
        print "<pre class='disabled'>";
        $data = [];
        for ($week = $this->n_weeks - 1; $week >= 0; $week--) {
            $filename = $this->data_filename($app_name, $dir, $week, $ordergroup);
            if ($ordergroup === "*") {
                // load files from all ordergroups
                foreach (glob($filename) as $filename) {
                    print "loading*:   $filename ...\n";
                    $file = fopen($filename, "r");
                    while (($line = fgets($file)) !== false) {
                        $data[] = json_decode($line, true);
                    }
                    fclose($file);
                }
            } else { // no or single ordergroup 
                if (file_exists($filename)) {
                    print "loading:    $filename ...\n";
                    $file = fopen($filename, "r");
                    while (($line = fgets($file)) !== false) {
                        $data[] = json_decode($line, true);
                    }
                    fclose($file);
                } else {
                    print "not exists: $filename ...\n";
                }
            }
        }
        //print_r($data);
        print "</pre>";
        return $data;
    }

    public function load_protocolls()
    {
        print "<pre class='disabled'>";
        $this->protocoll = [];
        for ($week = $this->n_weeks - 1; $week >= 0; $week--) {
            $this->protocoll_last_modified = null;
            $this->protocoll = array_merge($this->protocoll, $this->load_protocoll($week));
        }
        // print_r($this->protocoll);
        print "</pre>";
    }
    public function load_protocoll($week = 0, $start_index = 0)
    {
        $protocoll = [];
        foreach ($this->load_protocoll_json($week, $start_index) as $json) {
            $protocoll[] = json_decode($json, true);
        }
        return $protocoll;
    }
    public function load_protocoll_json($week = 0, $start_index = 0)
    {
        $filename = $this->data_filename(
            $this->app_name,
            $this->protocoll_dir,
            $week
        );
        clearstatcache();
        if ($this->protocoll_last_modified && filemtime($filename) == $this->protocoll_last_modified) {
            return []; // no updates available since last call
        }
        $protocoll = [];
        print "Loading protocoll from file: $filename";
        if (file_exists($filename)) {
            $this->protocoll_last_modified = filemtime($filename);
            $file = fopen($filename, "r");
            $index = 0;
            while (($line = fgets($file)) !== false) {
                if ($index++ >= $start_index)
                    $protocoll[] = $line;
            }
            fclose($file);
        } else {
            // return empty protocoll array
            print " (file does not exist yet)";
        }
        print "\n";
        return $protocoll;
    }





    public function generate_table_from_protocoll()
    {
        $this->table_default_values = $this->protocoll_entry();
        $this->table_keys = array_keys($this->table_default_values);
        $this->table = $this->protocoll;
    }

    public function sort_table($sort_by, $order = "asc")
    {
        // sort the data table by the given column name

        $sort_values = array_column($this->table, $sort_by);
        $sort_order = $order == "desc" ? SORT_DESC : SORT_ASC;
        array_multisort($sort_values, $sort_order, $this->table);

    }


    // --- local functions --------------------------------
    public function loc_floatval($string)
    {
        // decimal separators "." and/or "," are automatically detected

        $pos_comma = strpos($string, ",");
        $pos_dot = strpos($string, ".");

        # 123 or 123.45
        if ($pos_comma === false) {
            return floatval($string);
        }

        # 123,45
        if ($pos_dot === false) {
            return floatval(str_replace(",", ".", $string));
        }

        #1.234,56 
        if ($pos_comma > $pos_dot) {
            $string = str_replace(".", "", $string);
            $string = str_replace(",", ".", $string);
            return floatval($string);
        }

        #1,234.45
        $string = str_replace(",", "", $string);
        return floatval($string);
    }

    public function loc_floatstr($string)
    {
        // string has "." decimal separator like "123.45" or "1,234.56"?

        if ($this->decimal_separator == ",") {
            $string = str_replace(".", ",", $string);
            $i = strpos($string, ",");
            $n = 0;
            while ($i > 0 && ctype_digit($string[--$i])) {
                if (++$n == 3 && $i > 0 && ctype_digit($string[$i - 1])) {
                    $string = substr_replace($string, ".", $i, 0);
                    break;
                }
            }
        }
        return $string;
    }

    public function local_currency_str($amount, $plus_sign = false)
    {
        return ($plus_sign && $amount > 0 ? "+" : "") .
            $this->loc_floatstr(sprintf("%.2f €", $amount));
    }



    // --- date and time functions ----------------------------- 
    public function date_str($datetime = "now", $with_time = False)
    {
        if ($datetime == "now") {
            print "time now: " . $this->time_now . "<br>";
            $datetime = date_create($this->time_now);
        }
        return strtr(
            date_format(
                $datetime,
                $with_time ? 'D d.m.Y H:i:s' : 'D d.m.Y'
            ),
            [
                "Mon" => "Mo",
                "Tue" => "Di",
                "Wed" => "Mi",
                "Thu" => "Do",
                "Fri" => "Fr",
                "Sat" => "Sa",
                "Sun" => "So"
            ]
        );
    }

    public function loc_date($date_str)
    {
        // api: 2021-12-01T16:30:00.000+01:00 => 01.12.2021
        return
            substr($date_str, 8, 2) . "." . // 01.
            substr($date_str, 5, 2) . "." . // 12.
            substr($date_str, 0, 4);        // 2021
    }

    function days_ago($d2, $d1_str = 'today')
    {
        // float number of days that $d2 is before $d1 (positive), negative if it is after
        // days consideres hours, minutes and seconds as decimal part
        if ($d1_str == "today")
            $d1_str = $this->time_now;
        $d1 = date_create($d1_str);
        if (is_string($d2))
            $d2 = date_create($d2);
        $diff = date_diff($d2, $d1);
        return ($diff->invert ? -1 : 1) * (
            $diff->days +
            $diff->h / 24.0 +
            $diff->i / (24 * 60.) +
            $diff->s / (24. * 60 * 60)
        );
    }

    function date_diff_str($days)
    {
        if ($days < -7) {
            $date_str = "in " .
                $this->loc_floatstr(
                    sprintf("%.1f", -$days / 7.0)
                ) . " Wochen";
        } elseif ($days == -7) {
            $date_str = "in 1 Woche";
        } elseif ($days < -1) {
            $date_str = "in " . (-$days) . " Tagen";
        } elseif ($days == -1) {
            $date_str = "morgen";
        } elseif ($days == 0) {
            $date_str = "heute";
        } elseif ($days == 1) {
            $date_str = "gestern";
        } elseif ($days < 7) {
            $date_str = "vor " . $days . " Tagen";
        } elseif ($days == 7) {
            $date_str = "vor 1 Woche";
        } else {
            $date_str = "vor " .
                $this->loc_floatstr(
                    sprintf("%.1f", $days / 7.0)
                ) . " Wochen";
        }
        return $date_str;
    }

    public function date_and_time_ago($datetime, $days_in_past)
    {
        return $this->date_str($datetime) . " " .
            $this->date_diff_str($days_in_past);
    }

}


function weight_str($weight)
{
    if ($weight >= 1000) {
        $s = sprintf("%g kg", $weight / 1000);
    } else {
        $s = sprintf("%g g", $weight);
    }
    return str_replace(".", ",", $s);
}

function unit_str($unit)
{
    if (strpos($unit, "St") === 0) {
        return " " . $unit;
    } else {
        return " x " . $unit;
    }
}
?>