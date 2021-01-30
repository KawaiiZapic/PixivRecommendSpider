<?php
require_once "./vendor/autoload.php";
require_once "./Exceptions.php";
use Curl\Curl;

class PixivAppAPIPass extends PixivAppAPI {

    protected $realAPIIp = "";
    protected $realImgIp = "";
    protected $api_host = "app-api.pixiv.net";

    public function __construct(array $host) {
        $this->realAPIIp = $host['api'][random_int(0,count($host['api']) - 1)];
        $this->realImgIp = $host['img'][random_int(0,count($host['img']) - 1)];
        $this->oauth_url = "https://{$this->realAPIIp}/auth/token";
        $this->api_prefix = "https://{$this->realAPIIp}";
        parent::__construct();
    }

     /**
     * Refresh Token セット
     *
     * @param $refresh_token
     */
    public function setRefreshToken($refresh_token) {
        $this->refresh_token = $refresh_token;
    }

    /**
     * ログイン
     *
     * @param $user
     * @param $pwd
     * @param $refresh_token
     */
    public function login($user = null, $pwd = null, $refresh_token = null) {
        $local_time = date('Y-m-d') . 'T' . date('H:i:s+00:00');
        $request = array(
            'client_id' => $this->oauth_client_id,
            'client_secret' => $this->oauth_client_secret,
            'get_secure_url' => 1,
        );
        if ($user != null && $pwd != null) {
            $request = array_merge($request, array(
                'username' => $user,
                'password' => $pwd,
                'grant_type' => 'password',
            ));
        } elseif ($refresh_token != null || $this->refresh_token != null) {
            $request = array_merge($request, array(
                'refresh_token' => is_null($refresh_token) ? $this->refresh_token : $refresh_token,
                'grant_type' => 'refresh_token'
            ));
        } else {
            throw new Exception('login params error.');
        }
        $curl = new Curl();
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setHeader('Host', 'oauth.secure.pixiv.net');
        $curl->setHeader('User-Agent', 'PixivAndroidApp/5.0.64 (Android 6.0)');
        $curl->setHeader('X-Client-Time', $local_time);
        $curl->setHeader('X-Client-Hash', md5($local_time . $this->hash_secret));
        if($request['grant_type'] == "refresh_token") {
            $curl->setHeader('Authorization', 'Bearer ' . $this->getAccessToken());
        }
        $curl->post($this->oauth_url, $request);
        $result = $curl->response;
        if(!$result) {
            throw new NetworkException('Login network error: '. $curl->errorMessage);
        }
        $curl->close();
        if (isset($result->has_error)) {
            throw new APIException('Login error: ' . $result->errors->system->message);
        }
        $this->setAuthorizationResponse($result->response);
        $this->setAccessToken($result->response->access_token);
        $this->setRefreshToken($result->response->refresh_token);
    }

     /**
     * ネットワーク要求
     *
     * @param $uri
     * @param $method
     * @param array $params
     * @return mixed
     */
    protected function fetch($uri, $options = array()) {
        $method = isset($options['method']) ? strtolower($options['method']) : 'get';
        if (!in_array($method, array('post', 'get', 'put', 'delete'))) {
            throw new Exception('HTTP Method is not allowed.');
        }
        $body = isset($options['body']) ? $options['body'] : array();
        $headers = isset($options['headers']) ? $options['headers'] : array();
        $url = $this->api_prefix . $uri;
        foreach ($body as $key => $value) {
            if (is_bool($value)) {
                $body[$key] = ($value) ? 'true' : 'false';
            }
        }
        $curl = new Curl();
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $curl->setHeader($key, $value);
            }
        }
        $curl->setHeader("Host", $this->api_host);
        $curl->$method($url, $body);

        $result = $curl->response;
        if(!$result) {
            throw new NetworkException("API network error: " . $curl->errorMessage);
        }
        $curl->close();
        $array = json_decode(json_encode($result), true);
        if(isset($array['error'])){
            throw new APIException("API error: " . $array['error']['message']);
        }
        return $array;
    }

    /**
     * Recommended artwork.
     *
     * @return array
     */
    public function recommended_artwork()
    {
        return $this->fetch('/v1/illust/recommended', array(
            'method' => 'get',
            'headers' => array_merge($this->noneAuthHeaders, $this->headers),
            'body' => array(
                "content_type"=>"illust",
                "include_ranking_label"=>"true",
                "include_ranking_illusts"=>"true",
                "filter"=>"for_ios"
            ),
        ));
    }

    /**
     * Download Image.
     * 
     * @return boolean
     */
    function download_image(string $url,string $path) {
        $url = str_replace("i.pximg.net",$this->realImgIp,$url);
        $curl = new Curl();
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 10);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setReferer("https://www.pixiv.net/");
        $curl->setHeader("Host", "i.pximg.net");
        $result = $curl->download($url,$path);
        if(!$result){
            throw new NetworkException("Failed to download img: " . $curl->errorMessage);
        }
        return true;
    }
}
