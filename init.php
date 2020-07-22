<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Jerusalem');

set_time_limit(0);
ignore_user_abort(true);
header('Connection: close');
header('Content-Length: 0');
header("Content-type: application/json");
flush();
fastcgi_finish_request();

define('DATA_PATH', '/var/www/mashov/'); // change this
define('CONTACT_TOKEN', ''); // and this
define('WEBMASTER_TG_ID', ''); // yes, also this

if(!defined('BOT')){
    http_response_code(403);
    die();
}

if(!is_array(BOT)) throw 'define BOT is not array!';

if(!isset(BOT['token'])) throw 'Bot token is undefined!';
if(!isset(BOT['debug'])) throw 'Bot debug mode is undefined!';
if(!isset(BOT['webHookUrl'])) throw 'Bot webhook url mode is undefined!';

if(!BOT['debug'])
    error_reporting(0);
else
    error_reporting(1);

$bot = new MashovBot(BOT['token'], BOT['debug']);

//$bot->SetUpdate($update);
if(isset($chatId) && isset($name))
    $bot->SaveID($chatId, $name);


if(isset($update['channel_post'])){
    $bot->sendMessage($update['channel_post']['chat']['id'], "בוט זה אינו פעיל כרגע בקבוצות / ערוצים. סליחה.");
    $bot->leaveChat($update['channel_post']['chat']['id']);
    die();
}
if(isset($update['callback_query'])){
    $bot->sendMessage($update['callback_query']['message']['chat']['id'], "בוט זה אינו תומך במקלדת צפה. סליחה.");
    die();
}


if($bot->getBotBlocked()){
    $bot->sendMessage($chatId, $lang['botBlocked']);
    die();
}

if($bot->getCreatorBlocked()){
    $bot->sendMessage($chatId, $lang['creatorBlocked']);
    die();
}


