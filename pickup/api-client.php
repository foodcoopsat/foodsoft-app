<?php
class ApiClient
{
    public $runs_on_local_server;
    public $http;

    public $foodcoop_name;
    public $foodsoft_url;
    private $authorize_url, $token_url, $callback_uri, $base_api_url;
    private $client_id, $client_secret;
    public $access_token;

    public function __construct($config)
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $foodcoop = basename($path);
        $this->foodcoop_name = $config["foodcoop_name"] ?? ucfirst($foodcoop);

        $foodsoft_url = $config["foodsoft_url"] ??
            "https://app.foodcoops.at/$foodcoop";
        $this->foodsoft_url = $foodsoft_url;
        $this->runs_on_local_server = $_SERVER["HTTP_HOST"] == "localhost";
        $this->http = $this->runs_on_local_server ? "http://" : "https://";
        $this->authorize_url = $foodsoft_url . "/oauth/authorize";
        $this->token_url = $foodsoft_url . "/oauth/token";
        $this->callback_uri = $this->http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $this->base_api_url = $foodsoft_url . "/api/v1";

        $this->client_id = $config["client_id"];
        $this->client_secret = $config["client_secret"];

        // print "callback_uri: " . $this->callback_uri;
        // exit;

        $code = $_GET["code"] ?? NULL;
        if ($code) {
            // print "Code found: $code\n";
            // exit;
            $access_token = $this->getAccessToken($code);
            $this->uri_update_parameters(["code"], ["access_token=$access_token"]);

            // print ("Location: " . $this->callback_uri); exit;
            header("Location: " . $this->callback_uri);
            exit;
        }

        $this->access_token = $_GET["access_token"] ?? NULL;
        if (!$this->access_token) {
            // print "No access Token found!\n";
            // exit;
            $this->getAuthorizationCode();
            exit;
        }
        // print "Access Token found: $this->access_token\n";
    }



    private function uri_update_parameters($remove, $add = [])
    {
        $uri_items = explode("?", $this->callback_uri);
        $this->callback_uri = $uri_items[0];
        $par_items = array_filter(explode("&", $uri_items[1] ?? ""));

        $parameters = [];
        foreach ($par_items as $parameter) {
            $name = explode("=", $parameter)[0];
            if (!in_array($name, $remove))
                $parameters[] = $parameter;
        }

        $parameters = array_merge($parameters, $add);

        if ($parameters) {
            $this->callback_uri .= "?" . implode("&", $parameters);
        }
    }
    private function getAuthorizationCode()
    {
        header(
            "Location: " . $this->authorize_url . "?" .
            implode(
                "&",
                [
                    "response_type=code",
                    "client_id=" . $this->client_id,
                    "redirect_uri=" . $this->callback_uri,
                    "scope=" .
                    implode(
                        "%20",
                        [
                            "user:read",
                            "finance:user",
                            #"group_orders:user",
                            #"orders:read",
                            #"orders:write",
                        ]
                    )
                ]
            )
        );
    }

    private function getAccessToken($authorization_code)
    {
        $authorization = base64_encode("$this->client_id:$this->client_secret");
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->token_url,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic {$authorization}",
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => implode("&", [
                "grant_type=authorization_code",
                "code=$authorization_code",
                "redirect_uri=$this->callback_uri"
            ]),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            echo "Failed";
            echo curl_error($curl);
            echo "Failed";
        } elseif (json_decode($response)->error) {
            echo "Error:<br />";
            echo $authorization_code;
            echo $response;
        }
        return json_decode($response)->access_token;
    }


    public function getResource($api_url, $parameters = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_api_url . $api_url,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->access_token}"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_POSTFIELDS => json_encode($parameters),
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) { // probably invalid token!
            print "no response - foodsoft not running?";
            exit;
        }
        $response = json_decode($response, true);
        // print ("response: ");
        // print_r($response);
        if (($response["error"] ?? "") == "invalid_token") {
            $this->getAuthorizationCode();
            exit;
        }
        return $response;
    }

    public function updateResource(string $api_url, array $updates, $debug = FALSE)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_api_url . $api_url,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->access_token}",
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($updates),

        ]);

        if ($debug)
            print "updateResource() API url: $this->base_api_url$api_url, Authorization: Bearer {$this->access_token}\n";

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }


}

?>