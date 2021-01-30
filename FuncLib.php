<?php
function prtLog ($text) {
    if(defined("IN_DEBUG")){
        print_r($text.PHP_EOL);
    }
}

function processFilter(array $list,$filter) {
    //TODO: Filter
    $nlist = [];
    foreach($list as $item) {
        $info = new StdClass();
        $info->id = $item['id'];
        $info->url = isset($item['meta_single_page']['original_image_url']) ? $item['meta_single_page']['original_image_url'] : $item['image_urls']['large'];
        $nlist[] = $info;
    }
    return $nlist;
}