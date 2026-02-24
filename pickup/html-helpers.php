<?php

function html_hidden_input($name, $value)
{
    return html_tag(
        "input",
        [
            "type" => "hidden",
            "name" => $name,
            "value" => $value
        ]
    );
}

function html_button($text, $id, $on_click, $visible = true)
{
    return html_tag(
        "button",
        [
            "type" => "button",
            "id" => $id,
            "onclick" => $on_click,
            "style" => ($visible ? "" : "display:none"),
        ],
        $text
    );
}

function html_checkbox($name, $value, $id, $onchange = "", $large = false, $checked = false)
{
    if ($large) {
        $px = 30;
        $style =
            "width: " . $px . "px; " .
            "height: " . $px . "px; " .
            "vertical-align: -40%; ";
    } else
        $style = "";

    return html_tag("input", [
        "type" => "checkbox",
        "name" => $name,
        "value" => $value,
        "id" => $id,
        "style" => $style,
        "onchange" => $onchange,
        $checked ? "checked" : ""
    ]);
}

function html_list($items, $ordered = false)
{
    if (!$items)
        return "";
    $list_type = $ordered ? "ol" : "ul";
    return "<$list_type><li>" .
        implode("</li><li>", $items) .
        "</li></$list_type>";
}



function html_attribute($name, $value = "")
{
    if (is_array($value)) { // ["attr.name" => ["value1", "value2"]]
        return " $name='" . implode(" ", $value) . "'";
    } elseif (is_string($name) && $value !== "") { // ["attr.name" => "value"] 
        if (str_contains($value, '"') && str_contains($value, "'")) {
            error_log("Value for HTML attribute $name = $value contains both single and double quotes, " .
                "which is not supported.");
            return "";
        } elseif (str_contains($value, "'")) {
            return " $name=\"$value\"";
        } else {
            return " $name='$value'";
        }
    } elseif ($value) // ["attr.name"] withut value gives $name=index, $value="attr.name", like e.g. "checked" in <input type="checkbox" checked>
        return " $value";
    else
        return "";
}

function html_attributes($attributes)
{
    $html = "";
    foreach ($attributes as $name => $value) {
        $html .= html_attribute($name, $value);
    }
    return $html;
}

function html_tag($tag_name, $attributes = [], $content = "")
{
    if ($attributes == "close") {
        return "</$tag_name>";
    }
    $html = "<$tag_name";
    $html .= html_attributes($attributes);
    $html .= ">";
    if ($content)
        $html .= "$content</$tag_name>";
    return $html;
}

function html_tags($tag_name, $attribute_name, $values, $attributes, $content = "")
{
    $html = "";
    foreach ($values as $value) {
        $attributes[$attribute_name] = $value;
        $html .= html_tag($tag_name, $attributes, $content);
    }
    return $html;
}
class form_input
{

    private $name, $class, $id;
    private $value_max;
    private $value_init, $value_reset;
    private $update_function = "";
    private $null_button = "", $reset_button = "", $clear_button = "";
    private $buttons_on_both_sides = FALSE;
    private $data_attributes = array();

    //public function __construct() {}


    public function set_name($name)
    {
        $this->name = $name;
        if (!$this->id)
            $this->id = strtr($name, array("[" => "-", "]" => ""));
    }

    public function set_class($class)
    {
        $this->class = $class;
    }

    private function show_weight_unit()
    {
        return strpos($this->class, "weight") !== FALSE && strpos($this->class, "unit") !== FALSE;
    }

    public function set_id($id)
    {
        $this->id = $id;
    }

    public function set_init_value($value_init)
    {
        if (!is_numeric($value_init))
            $value_init = 0;
        $this->value_init = $value_init;
        $this->value_reset = $value_init;
        if ($this->class == "number") {
            $this->value_max = $value_init + 50;
        } else {
            $this->value_max = $value_init * 3; // weight
            if ($value_init == 0) {
                $this->value_max = 10000;
            }
        }
    }


    public function set_max_value($value)
    {
        $this->value_max = $value;
    }

    public function set_update_function($update_function)
    {
        $this->update_function = "updateInput(this,0," . $this->value_max . "); " . $update_function;
    }

    public function add_update_function($update_function)
    {
        $this->update_function .= " " . $update_function;
    }

    public function set_null_button($button_text = "0", $value_reset = FALSE)
    {
        // no argument, "text", or "text not received|text received"
        $this->null_button = $button_text;
        if ($value_reset !== FALSE) {
            $this->value_reset = $value_reset;
            $this->set_data_attribute("reset-value", sprintf("%.0f", $value_reset));
        }

    }

    public function set_reset_button($value_reset, $button_text = "R")
    {
        $this->value_reset = $value_reset;
        $this->reset_button = $button_text;
        $this->set_data_attribute("reset-value", sprintf("%.0f", $value_reset));
    }

    public function set_clear_button($button_text = "C")
    {
        $this->clear_button = $button_text;
    }

    public function set_buttons_on_both_sides($buttons_on_both_sides = TRUE)
    {
        $this->buttons_on_both_sides = $buttons_on_both_sides;
    }

    public function set_data_attribute($name, $value)
    {
        $this->data_attributes[$name] = $value;
    }

    private function data_attributes()
    {
        $s = "";
        foreach ($this->data_attributes as $name => $value) {
            $s .= sprintf('data-%s="%s" ', $name, $value); // https://www.w3schools.com/TAGS/att_data-.asp  
        }
        return $s;
    }

    public function set_article_name($article_name)
    {
        $this->set_data_attribute("article", $article_name);
    }



    public function print()
    {
        print $this->html();
    }

    public function html()
    {
        $id = $this->id;
        $html_left = "";
        $html_right = "";
        $html_center = sprintf(
            '<input class="%s" name="%s" id="input-%s" type="number" value="%.0f" ' .
            '%s min="0" max="%.0f" %s>',
            $this->class,
            $this->name,
            $this->id,
            $this->value_init,
            $this->data_attributes(),
            $this->value_max,
            strlen($this->update_function) > 0 ? "onChange='" . $this->update_function . "'" : ""
        );

        if ($this->show_weight_unit())
            $html_center .= " Gramm ";

        if ($this->reset_button) {
            $html_left .= '<button type="button" ' .
                'id="input-' . $id . '-reset" ' .
                'onclick="my_reset(' . "'input-" . $id . "'" . ',' . $this->value_init . ')">' .
                $this->reset_button . '</button>' . "\n";
        }

        $html_left .= '<button type="button" onclick="increment(' . "'input-$id'" . ',' . $this->value_max . ')">+</button>';
        $html_right .= '<button type="button" onclick="decrement(' . "'input-$id'" . ',0)">&#8722;</button>';

        if ($this->null_button) {
            $button_texts = explode("|", $this->null_button);
            if (count($button_texts) >= 2) {
                $html_right .= "\n" . '<button type="button" ' .
                    'id="input-' . $id . '-null" ' .
                    'data-text-0="' . $button_texts[0] . '" data-text-1="' . $button_texts[1] . '" ' .
                    'onclick="zero(' . "'input-$id'" . ', this)">' . $button_texts[$this->value_init > 0 ? 0 : 1] . '</button>';
            } else {
                $html_right .= "\n" . '<button type="button" ' .
                    'onclick="zero(' . "'input-$id'" . ', false)">' . $button_texts[0] . '</button>';
            }
        }
        if ($this->clear_button) {
            $html_right .= "\n" . '<button type="button" onclick="my_clear(' . "'input-$id'" . ')">' . $this->clear_button . '</button>';
        }
        if ($this->buttons_on_both_sides) {
            return $html_left . "\n" . $html_center . "\n" . $html_right . "\n";
        } else {
            return $html_center . "\n" . $html_left . "\n" . $html_right . "\n";
        }
    }
}

?>