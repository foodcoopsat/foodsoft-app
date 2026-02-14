<?php
require_once("order.php");
class OrderDistribute extends Order
{
    public $distribute;

    public function __construct($app, $data)
    {
        parent::__construct($app, $data);

        $this->distribute = $this->parameters["distribute"] ?? false; // @pickup:{"distribute":true}
    }

    public function create_article($article_data)
    {
        return new ArticleDistribute($this, $article_data);
    }

    public function html_heading()
    {
        print html_tag(
            "h2",
            ["id" => "order-$this->id"],
            $this->producer . " " . $this->date_str
        );
    }
}