<?php
$update = json_decode(file_get_contents('php://input'), true); 

if($update == NULL || !defined('BOT')){
    http_response_code(403);
    include '../error!/403.html';
    die();
}

//Inline
$inlineQ = $update["inline_query"]["query"]                          ?? null;
$InlineQId = $update["inline_query"]["id"]                           ?? null;
$InlineMsId = $update["callback_query"]["inline_message_id"]         ?? null;

//Callbeck
$callId = $update["callback_query"]["id"]                            ?? null;
$callData = $update["callback_query"]["data"]                        ?? null;
$callFromId = $update["callback_query"]["from"]["id"]                ?? null;
$callMessageId = $update["callback_query"]["message"]["message_id"]  ?? null;

//EditMessage
$isEdited = false;
$var_in_arr = "message";
if(isset($update['edited_message'])){
    $isEdited = true;
    $var_in_arr = "edited_message";
}

//text
$message = $update[$var_in_arr]['text']                                ?? null;
//photo
$tphoto = $update[$var_in_arr]['photo']                                ?? null;
if(!empty($tphoto))
    $phid = $update[$var_in_arr]['photo'][count($tphoto)-1]['file_id'] ?? null;
//audio
$auid = $update[$var_in_arr]['audio']['file_id']                       ?? null;
//document
$did = $update[$var_in_arr]['document']['file_id']                     ?? null;
//video
$vidid = $update[$var_in_arr]['video']['file_id']                      ?? null;
//voice
$void = $update[$var_in_arr]['voice']['file_id']                       ?? null;
//video_note
$vnid = $update[$var_in_arr]['video_note']['file_id']                  ?? null;
//contact
$conid = $update[$var_in_arr]['contact']['phone_number']               ?? null;
$conf = $update[$var_in_arr]['contact']['first_name']                  ?? null;
$conl = $update[$var_in_arr]['contact']['last_name']                   ?? null;
//location
$locid1 = $update[$var_in_arr]['location']['latitude']                 ?? null;
$locid2 = $update[$var_in_arr]['location']['longitude']                ?? null;
//Sticker
$sti = $update[$var_in_arr]['sticker']['file_id']                      ?? null;
//Venue
$venLoc1 = $update[$var_in_arr]['venue']['location']['latitude']       ?? null;
$venLoc2 = $update[$var_in_arr]['venue']['location']['longitude']      ?? null;
$venTit = $update[$var_in_arr]['venue']['title']                       ?? null;
$venAdd = $update[$var_in_arr]['venue']['address']                     ?? null;
//all media
$cap = $update[$var_in_arr]['caption']                                 ?? null;

//Global parmeters
$chatId = $update[$var_in_arr]['chat']['id']                           ?? null;
$fromId = $update[$var_in_arr]['from']['id']                           ?? null;
$chatType = $update[$var_in_arr]["chat"]["type"]                       ?? null;
$messageId = $update[$var_in_arr]['message_id']                        ?? null;
$rfid = $update[$var_in_arr]['reply_to_message']['forward_from']['id'] ?? null;
$rtx = $update[$var_in_arr]['reply_to_message']['text']                ?? null;
$rtmfid = $update[$var_in_arr]['reply_to_message']['from']['id']       ?? null;
$fName = $update[$var_in_arr]["from"]["first_name"]                    ?? null;
$lName = $update[$var_in_arr]["from"]["last_name"]                     ?? null;

if(preg_match('/^[0-9]{6,10}$/' ,$message) && strlen($message) < 10 && strlen($message) > 5){
    $rfid = $message;
    $rtx = "^הודעת מערכת^";
    
    $message = $update['message']['reply_to_message']['text'] ?? null;
    //photo
    $tphoto = $update['message']['reply_to_message']['photo'] ?? null;
    if(isset($tphoto))
        $phid = $update['message']['reply_to_message']['photo'][count($tphoto)-1]['file_id'];
    //audio
    $auid = $update['message']['reply_to_message']['audio']['file_id'] ?? null;
    //document
    $did = $update['message']['reply_to_message']['document']['file_id'] ?? null;
    //video
    $vidid = $update['message']['reply_to_message']['video']['file_id'] ?? null;
    //voice
    $void = $update['message']['reply_to_message']['voice']['file_id'] ?? null;
    //video_note
    $vnid = $update['message']['reply_to_message']['video_note']['file_id'] ?? null;
    //contact
    $conid = $update['message']['reply_to_message']['contact']['phone_number'] ?? null;
    $conf = $update['message']['reply_to_message']['contact']['first_name'] ?? null;
    $conl = $update['message']['reply_to_message']['contact']['last_name'] ?? null;
    //location
    $locid1 = $update['message']['reply_to_message']['location']['latitude'] ?? null;
    $locid2 = $update['message']['reply_to_message']['location']['longitude'] ?? null;
    //Sticker
    $sti = $update['message']['reply_to_message']['sticker']['file_id'] ?? null;
    //poll
    $poll = $update['message']['reply_to_message']['poll'] ?? null;
    //all media
    $cap = $update['message']['reply_to_message']['caption'] ?? null;
}

$name = trim($fName." ".$lName);

//CallBeck
if(isset($update['callback_query'])){
    $message = $update["callback_query"]["data"] ?? null;
    $chatId = $update["callback_query"]["from"]["id"] ?? null;
    $messageId = $update["callback_query"]["message"]["message_id"] ?? null;
}

$chatLinkId = "<a href=\"tg://user?id=".$chatId."\">".$chatId."</a>";
$hiddenUserLink = "<a href=\"https://mashov.telegram-bots.yehudae.ga/HiddenSender?id=".$chatId."\">‏</a>";
$editedText = "ההודעה נערכה ✏️\n\n";

