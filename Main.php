<?php
require "./PixivAPI.php";
require "./FuncLib.php";
define("IN_DEBUG",1);
$loginInfo = json_decode(file_get_contents("./login.json"));
$config = json_decode(file_get_contents("./config.json"));
$pixivHosts = json_decode(file_get_contents("./hosts.json"),true);
if(!$loginInfo){
    die("Unabel to get ANY login info.");
}
if(!$pixivHosts || count($pixivHosts['api']) == 0 || count($pixivHosts['img']) == 0) {
    die("Hosts file is empty or not exists.");
}
if(!file_exists($config->SavePath)){throw new Exception("SavePath not exists,check config.json or create directory.");}
$api = new PixivAppAPIPass($pixivHosts);
try {
    if(isset($loginInfo->accessToken) && $loginInfo->accessToken){
        prtLog("Login by accessToken.");
        $api->setAccessToken($loginInfo->accessToken);
        if($loginInfo->accessTokenExpries < time()){
            prtLog("accessToken expried, try to refresh.");
            $api->setRefreshToken($loginInfo->refreshToken);
            for($retry = 0;$retry < $config->MaxRetry; $retry++) {
                try {
                    $api->login();
                    prtLog("accessToken refresh successfully.");
                    break;
                } catch (NetworkException $ignored) {}
            }
            if($retry >= $config->MaxRetry) {throw new Exception();}
        }
    } else {
        throw new APIException();
    }
} catch (APIException $e) {
    for($retry = 0;$retry < 10; $retry++) {
        try {
            $api->login($loginInfo->username, $loginInfo->passwd);
            break;
        } catch (NetworkException $ignored) {}
    }
    if($retry >= $config->MaxRetry) {throw new Exception();}
} catch (Exception $e) {
    throw $e;
}
$loginResp = $api->getAuthorizationResponse();
if(!is_null($loginResp)){
    $loginInfo->accessToken = $loginResp->access_token;
    $loginInfo->accessTokenExpries = intval($loginResp->expires_in) + time();
    $loginInfo->refreshToken = $loginResp->refresh_token;
    file_put_contents("./login.json",json_encode($loginInfo,JSON_PRETTY_PRINT));
    prtLog("accessToken saved.");
}
for($retry = 0;$retry < $config->MaxRetry; $retry++) {
    try {
        $list = $api->recommended_artwork()['illusts'];
        break;
    } catch (NetworkException $ignored) {}
}
if($retry >= $config->MaxRetry) {throw new Exception();}
$list = processFilter($list,$config->filter);
prtLog("Get ". count($list) . " item(s) to download.");
if($config->RemoveOldArtworks) {
    $rList = scandir($config->SavePath);
    foreach($rList as $item) {
        if(in_array($item,[".",".."])){continue;}
        prtLog("Deleting: " . $config->SavePath."/".$item);
        unlink($config->SavePath."/".$item);
    }
}
$count = 0;
foreach($list as $item) {
    if($count >= $config->MaxCountPreDownload){break;}
    $attr = pathinfo($item->url, PATHINFO_EXTENSION);
    if(file_exists("{$config->SavePath}/{$item->id}.{$attr}")){continue;}
    prtLog("Downloading: {$item->id}");
    for($retry = 0;$retry < $config->MaxRetry; $retry++) {
        try {
            $api->download_image($item->url,"{$config->SavePath}/{$item->id}.{$attr}");
            break;
        } catch (NetworkException $ignored) {}
    }
    if($retry >= $config->MaxRetry) {
        prtLog("Download failed: {$item->id}");
        unlink("{$config->SavePath}/{$item->id}.{$attr}.pccdownload");
        continue;
    }
    prtLog("Downloaded: {$item->id}");
    $count++;
}