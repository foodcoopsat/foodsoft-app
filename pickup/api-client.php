<?php
class ApiClient
{
    public $runs_on_local_server;
    public $http;

    public $foodcoop_name;
    public $foodsoft_url;
    public $foodsoft_host;
    private $authorize_url;
    private $token_url;
    private $callback_uri;
    private $base_api_url;
    private $client_id;
    private $client_secret;
    public $access_token;
    private $debug = TRUE;

    public function __construct($config)
    {
        $url = $_SERVER['REQUEST_URI'];
        $path = parse_url($url, PHP_URL_PATH);
        $foodcoop = basename($path);
        $this->foodcoop_name = $config["foodcoop_name"] ?? ucfirst($foodcoop);
        $foodsoft_url = $config["foodsoft_url"] ??
            "https://app.foodcoops.at/$foodcoop";
        $this->foodsoft_url = $foodsoft_url;
        $this->foodsoft_host = parse_url($foodsoft_url, PHP_URL_HOST);
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
            if (!$access_token) {
                //print "No access token - exiting app.";token 
                print "Code $code für access_token gesendet.\n";
                print "Kein access_token für API erhalten - Anwendung beendet.";
                exit();
            }
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
        // print "<pre>";
        // print "CURLOPT_POSTFIELDS: " . implode("&", [
        //     "grant_type=authorization_code",
        //     "code=$authorization_code",
        //     "redirect_uri=$this->callback_uri"
        // ]) . "\n";

        $response = curl_exec($curl);
        // curl_close($curl);

        //print_r($response);

        if ($response === false) {
            echo "Failed";
            echo curl_error($curl);
            return;
        }
        $response = json_decode($response);
        if ($response->error ?? false) {
            print $response->error . "\n";
            print $response->error_description . "\n";
        }
        return $response->access_token ?? null;
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
        // print "response:\n$response";
        $response = json_decode($response, true);
        // print ("response: ");
        //print_r($response);
        // var_dump($response);
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