<?php

class Bot{
    protected $BotToken;
    protected $BotId;
    protected $BotName;
    protected $BotUserName;
    protected $Debug;
    protected $DBConn;
    protected $DBPerfix;
    protected $beautifi = true;
    protected $update = null;
    protected $webHook = null;
    protected $webPagePreview = true;
    protected $Notification = false;
    protected $ParseMode = null;

    public function __construct($token, $Debug = false){
        $botInfo = json_decode(file_get_contents("https://api.telegram.org/bot".$token."/getMe"), true);
        if($Debug && 0)
            $this->logging($botInfo, false, "BotInfoOutput: Success!", true);
        if($botInfo['ok'] == true && $botInfo['result']['is_bot'] == true){
            $this->BotToken = $token;
            $this->Debug = $Debug;
            $this->BotId = $botInfo['result']['id'];
            $this->BotName = $botInfo['result']['first_name'];
            $this->BotUserName = $botInfo['result']['username'];
            $this->DBPerfix = $this->BotId."__";
            
            $this->DB("init");
            return true;
        }
        else return false;
    }
    
    public function __destruct(){
        if(isset($this->DBConn)){
            $this->DBConn->close();
        }
    }
    
    
    protected function DB($mode = "init", $q = null, ...$params){
        if($mode == "init" || !isset($this->DBConn)){
            $this->DBConn = new mysqli(DB['host'], DB['username'], DB['password'], DB['dbname']);
            mysqli_set_charset($this->DBConn, "utf8mb4");
        }
    }
    
    public function userExist($id){
        $res = $this->DBConn->query("select * from `".$this->DBPerfix."users` WHERE `tg_id` = ".$id.";");
        
        return $res->num_rows > 0 ? true : false;
    }
    
    public function SaveID($id, $name){
        if(!$this->userExist($id)){
            $this->DBConn->query("INSERT INTO `".$this->DBPerfix."users` 
                (`id`, `tg_id`, `name`, `admin`, `blocked`, `tg_block`) VALUES 
                (NULL, '".$id."', '".$this->DBConn->real_escape_string($name)."', 0, 0, 0)");
        }
    }
    
    //Setters && Getters
    public function getBotID(){
        return $this->BotId;
    }
    public function getBotUserName(){
        return $this->BotUserName;
    }
    public function getBotName(){
        return $this->BotName;
    }
    public function getBotToken(){
        return $this->BotToken;
    }
    
    
        //Debug Mode
    public function GetDebug(){
        return $this->Debug;
    }
    public function SetDebug($val){
        $this->Debug = $val;
    }
        //WebHook
    public function GetWebHook(){
        return $this->webHook;
    }
    public function SetWebHook($val){
        $this->webHook = $val;
        return $this->Request('setwebhook', array( "url" => $val))['ok'];
    }
    public function DetWebHook(){
        $this->webHook = NULL;
        return $this->Request('setwebhook', array("url"))['ok'];
    }
        //Updates - BETA!
    /*public function SetUpdate($update){
        $this->Update = $update;
        if($this->Debug)
            $this->logging($update, false, "Update input:", true);
    }
    public function GetUpdate(){
        return $this->Update;
    }*/
        //WebPagePreview Mode
    public function GetWebPagePreview(){
        return $this->webPagePreview;
    }
    public function SetWebPagePreview($val){
        $this->webPagePreview = $val;
    }
        //Notification Mode
    public function GetNotification(){
        return $this->Notification;
    }
    public function SetNotification($val){
        $this->Notification = $val;
    }
        //ParseMode Mode
    public function GetParseMode(){
        return $this->ParseMode;
    }
    public function SetParseMode($val){
        if($val == null || "markdown" == strtolower($val) || "html" == strtolower($val))
            $this->ParseMode = $val;
    }
    
    //SendRequest
    protected function Request($method, $data =[] ==null){
        $BaseUrl = "https://api.telegram.org/bot".$this->BotToken."/".$method;
    	
        $ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $BaseUrl);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch ,CURLOPT_POSTFIELDS, $data);
       
        $res = curl_exec($ch);
        
        if(curl_error($ch)){
            if($this->GetDebug())
                $this->logging(curl_error($ch), "Curl: ".$method, false, false, $data);
    		curl_close($ch);
        }else{
            curl_close($ch);
            $res = json_decode($res, true);
            if($this->GetDebug())
                $this->logging($res, "Curl: ".$method, true, true, $data);
            return $res;
        }
    }
    
    //Logging
    public function logging($data, $method = null, $success = false, $array = false, $helpArgs = null){
        $tmp = ($this->beautifi ? JSON_PRETTY_PRINT : null ) | JSON_UNESCAPED_UNICODE;
        if(!$array)
            $data = array("data" => $data);
        
        $data['added_by_log']['helpArgs'] = $helpArgs;
        $data['added_by_log']['date'] = date(DATE_RFC850);
        $data['added_by_log']['botUserName'] = $this->BotUserName;
        $data['added_by_log']['success'] = ($success ? "Success!" : "Error");
        $data['added_by_log']['method'] = $method;
        
        $data = json_encode($data, $tmp);
        file_put_contents($this->BotUserName." - log.log", $data.",\n", FILE_APPEND | LOCK_EX);
    }
    
    
    //Methods
    public function sendMessage($id, $text, $replyMarkup = null, $replyMessage = null){
        $data["chat_id"] = $id;
        $data["text"] = $text;
        $data["parse_mode"] = $this->ParseMode;
        $data["disable_web_page_preview"] = $this->webPagePreview;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendMessage", $data);
    }
    public function forwardMessage($id, $fromChatId, $messageId){
        $data["chat_id"] = $id;
        $data["from_chat_id"] = $fromChatId;
        $data["disable_notification"] = $this->Notification;
        $data["message_id"] = $messageId;
        return $this->Request("forwardMessage", $data);
    }
    public function sendPhoto($id, $photo, $caption = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["photo"] = $photo;
        $data["caption"] = $caption;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendPhoto", $data);
    }
    public function sendAudio($id, $audio, $duration = null, $performer = null, $title = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["audio"] = $audio;
        $data["duration"] = $duration;
        $data["performer"] = $performer;
        $data["title"] = $title;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendAudio", $data);
    }
    public function sendDocument($id, $document, $caption = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["document"] = $document;
        $data["caption"] = $caption;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendDocument", $data);
    }
    public function sendSticker($id, $sticker, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["sticker"] = $sticker;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendSticker", $data);
    }
    public function sendVideo($id, $video, $duration = null, $width = null, $height = null, $caption = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["video"] = $video;
        $data["duration"] = $duration;
        $data["width"] = $width;
        $data["height"] = $height;
        $data["caption"] = $caption;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendVideo", $data);
    }
    public function sendVoice($id, $voice, $duration = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["voice"] = $voice;
        $data["duration"] = $duration;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendVoice", $data);
    }
    public function sendVideoNote($id, $videoNote, $duration = null, $length = null, $thumb = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["video_note"] = $videoNote;
        $data["duration"] = $duration;
        $data["length"] = $duration;
        $data['thumb'] = $thumb;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendVoice", $data);
    }
    public function sendLocation($id, $latitude, $longitude, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["latitude"] = $latitude;
        $data["longitude"] = $longitude;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendLocation", $data);
    }
    public function sendVenue($id, $latitude, $longitude, $title, $address, $foursquare = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["latitude"] = $latitude;
        $data["longitude"] = $longitude;
        $data["title"] = $title;
        $data["address"] = $address;
        $data["foursquare_id"] = $foursquare;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendVenue", $data);
    }
    public function sendContact($id, $phoneNumber, $firstName, $lastName = null, $replyMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["phone_number"] = $phoneNumber;
        $data["first_name"] = $firstName;
        $data["last_name"] = $lastName;
        $data["disable_notification"] = $this->Notification;
        $data["reply_to_message_id"] = $replyMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("sendContact", $data);
    }
    public function sendChatAction($id, $action){
        if(!in_array($action, ["typing", "upload_photo", "record_video", "upload_video", "record_audio", "upload_audio", "upload_document", "find_location"]))
            return false;
        $data["chat_id"] = $id;
        $data["action"] = $action;
        return $this->Request("sendChatAction", $data);
    }
    public function getUserProfilePhotos($uId, $offset = null, $limit = null){
        $data["user_id"] = $uId;
        $data['offset'] = $offset;
        $data['limit'] = $limit;
        return $this->Request("getUserProfilePhotos", $data);
    }
    public function kickChatMember($id, $uId){
        $data["chat_id"] = $id;
        $data["user_id"] = $uId;
        return $this->Request("kickChatMember", $data);
    }
    public function unbanChatMember($id, $uId){
        $data["chat_id"] = $id;
        $data["user_id"] = $uId;
        return $this->Request("unbanChatMember", $data);
    }
    public function getFile($fileId){
        $data["file_id"] = $fileId;
        return $this->Request("getFile", $data);
    }
    public function leaveChat($id){
        $data["chat_id"] = $id;
        return $this->Request("leaveChat", $data);
    }
    public function getChat($id){
        $data["chat_id"] = $id;
        return $this->Request("getChat", $data);
    }
    public function getChatAdministrators($id){
        $data["chat_id"] = $id;
        return $this->Request("getChatAdministrators", $data);
    }
    public function getChatMembersCount($id){
        $data["chat_id"] = $id;
        return $this->Request("getChatMembersCount", $data);
    }
    public function getChatMember($id, $uId){
        $data["chat_id"] = $id;
        $data["user_id"] = $uId;
        return $this->Request("getChatMember", $data);
    }
    public function answerCallbackQuery($callback, $text = null, $alert = false){
        $data["callback_query_id"] = $callback;
        $data["text"] = $text;
        $data["show_alert"] = $alert;
        return $this->Request("answerCallbackQuery", $data);
    }
    public function editMessageText($id = null, $messageId = null, $inlineMessage = null, $text, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["message_id"] = $messageId;
        $data["inline_message_id"] = $inlineMessage;
        $data["text"] = $text;
        $data["parse_mode"] = $this->ParseMode;
        $data["disable_web_page_preview"] = $this->webPagePreview;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("editMessageText", $data);
    }
    public function editMessageCaption($id = null, $messageId = null, $inlineMessage = null, $caption = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["message_id"] = $messageId;
        $data["inline_message_id"] = $inlineMessage;
        $data["caption"] = $caption;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("editMessageCaption", $data);
    }
    public function editMessageMedia($id = null, $messageId = null, $inlineMessage = null, $media = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["message_id"] = $messageId;
        $data["inline_message_id"] = $inlineMessage;
        $data["media"] = $media;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("editMessageMedia", $data);
    }
    public function editMessageReplyMarkup($id = null, $messageId = null, $inlineMessage = null, $replyMarkup = null){
        $data["chat_id"] = $id;
        $data["message_id"] = $messageId;
        $data["inline_message_id"] = $inlineMessage;
        $data["reply_markup"] = $replyMarkup;
        return $this->Request("editMessageReplyMarkup", $data);
    }
    public function deleteMessage($id, $messageId){
        $data["chat_id"] = $id;
        $data["message_id"] = $messageId;
        return $this->Request("deleteMessage", $data);
    }
    public function answerInlineQuery($inlineMessage, $res, $cacheTime = null, $isPersonal = null, $nextOffset = null, $switchPmText = null, $switchPmParameter = null){
        $data["inline_query_id"] = $inlineMessage;
        $data["results"] = $res;
        $data["cache_time"] = $cacheTime;
        $data["is_personal"] = $isPersonal;
        $data["next_offset"] = $nextOffset;
        $data["switch_pm_text"] = $switchPmText;
        $data["switch_pm_parameter"] = $switchPmParameter;
        return $this->Request("answerInlineQuery", $data);
    }
}

class MashovBot extends Bot{
    
    protected $creatorBlocked = false;
    protected $botBlocked = false;
    
    public function __construct($token, $debug){
        parent::__construct($token, $debug);
        if(defined('BOT') && !isset(BOT['main']))
            $this->updateBlockedMode();
    }
    public function __destruct(){
        parent::__destruct();
    }
    
    private function updateBlockedMode(){
        if($this->creatorBlocked || $this->botBlocked) return true;
        
        $result = $this->DBConn->query("SELECT * FROM `blocks`");
        
        while ($row = $result->fetch_assoc()) {
            if($row['tg_auth'] == $this->getCreatorID() || $row['tg_auth'] == $this->getBotToken()){
                if($row['tg_auth'] == $this->getCreatorID())
                    $this->creatorBlocked = true;
                elseif($row['tg_auth'] == $this->getBotToken())
                    $this->botBlocked = true;
                
                return true;
            }
        }
        return false;
    }
    public function getBotBlocked(){
        return $this->botBlocked;
    }
    public function getCreatorBlocked(){
        return $this->creatorBlocked;
    }

    public function createBot($creatorId, $name){
        $this->DBConn->query("INSERT INTO `" . (DB['dbname'] ?? "botMashov") . "`.`BotsData` (`bot_id`, `token`, `username`, `creator`, `time`) VALUES ('".$this->BotId."', '".$this->BotToken."', '".$this->BotUserName."', '".$creatorId."', CURRENT_TIMESTAMP);");

        //$this->DBConn->query("DROP TABLE IF EXISTS `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users`;");
        $this->DBConn->query("CREATE TABLE IF NOT EXISTS `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `tg_id` INT NOT NULL ,
                `name` TEXT NOT NULL ,
                `admin` BOOLEAN NOT NULL ,
                `blocked` BOOLEAN NOT NULL ,
                `tg_block` BOOLEAN NOT NULL ,
                
                PRIMARY KEY (`id`)) ENGINE = InnoDB;");
        
        //$this->DBConn->query("DROP TABLE IF EXISTS `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message`;");
        $this->DBConn->query("CREATE TABLE IF NOT EXISTS `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message` ( 
                `message_id` INT(100) NOT NULL , 
                `type` TEXT NOT NULL , 
                `value` JSON NOT NULL , 
                `chat_id` BIGINT NOT NULL , 
                `from_id` INT NOT NULL , 
                `to_id` JSON NOT NULL , 
                `is_edit` BOOLEAN NOT NULL , 
                
                PRIMARY KEY (`message_id`)) ENGINE = InnoDB;");
                
                
        $this->DBConn->query("DROP TABLE IF EXISTS `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data`;");
        $this->DBConn->query("CREATE TABLE `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data` (
                `name` VARCHAR(250) NOT NULL ,
                `value` TEXT NOT NULL ,
                
                PRIMARY KEY (`name`)) ENGINE = InnoDB;");
                
                
        $this->DBConn->query('INSERT INTO `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` (`name`, `value`)
                VALUES ("token", "'.$this->BotToken.'");');
        $this->DBConn->query('INSERT INTO `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` (`name`, `value`)
                VALUES ("Creator_id", '.$creatorId.');');
        $this->DBConn->query('INSERT INTO `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` (`name`, `value`)
                VALUES ("credit", "yes");');
        $this->DBConn->query('INSERT INTO `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` (`name`, `value`)
                VALUES ("group_id", "");');
        $this->DBConn->query('INSERT INTO `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` (`name`, `value`)
                VALUES ("StartMessage", "");');
        
        $this->setGroupID("");
        $this->setStartMessage("היי ! 👋🏼\nדרך רובוט זה תוכל להתכתב איתי. \nתוכל לשלוח טקסט / מדיה ואני אענה לך בהקדם האפשרי.");
        
        $this->SetWebHook("https://mashov.telegram-bots.yehudae.ga/NewVersion/TheNewBot.php?id=".$this->BotId);
        
        $this->SaveID($creatorId, $name);
        $this->setAdmin($creatorId, true);
        $this->updateBlockedMode();
    }
    
    public static function isBlockForwardMes($str, $entities){
        if($str == "‏⚠️משתמש זה הגדיר בהגדרות הפרטיות שלו שלא ניתן להעביר ממנו הודעות.\nבכדי לענות למשתמש זה אנא השב על ההודעה הזו." && $entities['offset'] == 0 && $entities['length'] == 1 && $entities['type'] == "text_mention" && preg_match('{[\d]}' ,$entities['user']['id'])){
            return $entities['user']['id'];
        }
        elseif(strpos($str, "‏⚠️הודעה זו הועברה מהמשתמש") == 0 && $entities['offset'] == 0 && $entities['length'] == 1 && $entities['type'] == "text_mention" && preg_match('{[\d]}' ,$entities['user']['id'])){
            return $entities['user']['id'];
        }
        elseif($str == "‏⚠️משתמש זה הגדיר בהגדרות הפרטיות שלו שלא ניתן להעביר ממנו הודעות.\nבכדי לענות למשתמש זה אנא השב על ההודעה הזו." && $entities['offset'] == 0 && $entities['length'] == 1 && $entities['type'] == "text_link" && strpos($entities['url'], "https://mashov.telegram-bots.yehudae.ga/HiddenSender?id=") === 0){ 
            return str_replace("https://mashov.telegram-bots.yehudae.ga/HiddenSender?id=", "", $entities['url']);
        }
        elseif(strpos($str, "‏⚠️הודעה זו הועברה מהמשתמש") == 0 && $entities['offset'] == 0 && $entities['length'] == 1 && $entities['type'] == "text_link" && strpos($entities['url'], "https://mashov.telegram-bots.yehudae.ga/HiddenSender?id=") === 0){
            return str_replace("https://mashov.telegram-bots.yehudae.ga/HiddenSender?id=", "", $entities['url']);
        }
        return false;
    }
    
    public function startMesCredit(){
        $results = $this->DBConn->query("SELECT `value` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data` WHERE `name` = 'credit'");
        return $results->fetch_array()['value'];
    }
    
    public function setStartMessage($val){
        $this->DBConn->query('UPDATE `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` SET `value` = "'.$this->DBConn->real_escape_string($val).'"
                WHERE `name` = "StartMessage";');
    }
    public function getStartMessage(){
        $results = $this->DBConn->query("SELECT `value` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data` WHERE `name` = 'StartMessage'");
        return $results->fetch_array()['value'];
    }
    
    public function setGroupID($val){
        $this->DBConn->query('UPDATE `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'data` SET `value` = "'.$this->DBConn->real_escape_string($val).'"
                WHERE `name` = "group_id";');
    }
    public function getGroupID(){
        $results = $this->DBConn->query("SELECT `value` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data` WHERE `name` = 'group_id'");
        return $results->fetch_array()['value'];
    }
    
    public function getCreatorID(){
        $results = $this->DBConn->query("SELECT `value` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."data` WHERE `name` = 'Creator_id'");
        return $results->fetch_array()['value'];
    }
    
    public function SetUpdate($update, $messageId, $type, $value, $chatId = null, $fromID = null, $toID = null){
        $this->Update = $update;
        if($this->Debug)
            $this->logging($update, false, "Update input:", true);
        
        if($type == "edited"){
            $results = $this->DBConn->query("SELECT `type` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message` WHERE `message_id` = '".$messageId."'");
            $type =  $results->fetch_array()['type'];

            $value = array($type => $value);
            $this->DBConn->query("UPDATE `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message` SET 
            `value` = '".$this->DBConn->real_escape_string(json_encode($value))."', `is_edit` = 1
            WHERE `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message`.`message_id` = '".$messageId."';");
        }
        else{
            /*if(isset($update['message']['text'])){
                $messageId = $update['message']['message_id'];
                $value = array("text" => $update['message']['text']);
                $chatId = $update['message']['chat']['id'];
                $fromID = $update['message']['from']['id'];
                //if(!$this->isAdmin($update['message']['from']['id']))
                $toID = $this->getAdminIDS();
            }*/
            
            $value = array($type => $value);
            
            $this->DBConn->query("INSERT INTO `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message` 
                    (`message_id`, `type`, `value`, `chat_id`, `from_id`, `to_id`, `is_edit`) VALUES 
                    (".$messageId.", '".$type."', '".$this->DBConn->real_escape_string(json_encode($value))."', '".$chatId."', '".$fromID."', '".json_encode($toID)."', 0);");
        }
    }
    
    public function isAdmin($id){
        $results = $this->DBConn->query("SELECT `admin` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `tg_id` = '".$id."'");
        return $results->fetch_array()['admin'];
    }
    public function setAdmin($id, $mode){
        $this->DBConn->query('UPDATE `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'users` SET `admin` = '.$mode.' WHERE `tg_id` = '.$id.';');
    }
    public function getAdminIDS(){
        $result = $this->DBConn->query("SELECT `tg_id` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `admin` = 1");
        $admins = array();
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row['tg_id'];
        }
        
        return $admins;
    }
    
    public function isBlocked($id){
        $results = $this->DBConn->query("SELECT `blocked` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `tg_id` = '".$id."'");
        return $results->fetch_array()['blocked'];
    }
    public function blockUser($id, $mode){
        $this->DBConn->query('UPDATE `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'users` SET `blocked` = '.$mode.' WHERE `tg_id` = '.$id.';');
    }
    public function getBlockedIDS(){
        $result = $this->DBConn->query("SELECT `tg_id` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `blocked` = 1");
        $blocks = array();
        while ($row = $result->fetch_assoc()) {
            $blocks[] = $row['tg_id'];
        }
        
        return $blocks;
    }
    
    public function getToIdByMessageId($messageId){
        $results = $this->DBConn->query("SELECT `to_id` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."message` WHERE `message_id` = '".$messageId."'");
        return json_decode($results->fetch_array()['to_id'], true);
    }
    
    public function isTgBlocked($id){
        $results = $this->DBConn->query("SELECT `tg_block` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `tg_id` = '".$id."'");
        return $results->fetch_array()['tg_block'];
    }
    public function tgBlockUser($id, $mode){
        $this->DBConn->query('UPDATE `' . (DB['dbname'] ?? "botMashov") . '`.`'.$this->DBPerfix.'users` SET `tg_block` = '.$mode.' WHERE `tg_id` = '.$id.';');
    }

    public function sendUsersList($chatId){
        $result = $this->DBConn->query("SELECT * FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users`");
        
        $i = 1;
        $text = "רשימת משתמשים:\n";
        
        while ($row = $result->fetch_assoc()) {
            if($i % 51 == 0){
                $this->SetParseMode('html');
                $this->sendMessage($chatId, $text);
                $text = "המשך רשימת משתמשים:\n";
            }
            
            if($chatId == $row['tg_id']) continue;
            
            $linkId = "<a href=\"tg://user?id=".$row['tg_id']."\">".$row['tg_id']."</a>";
            
            $tmp = $i.". ".$linkId;
            if($row['admin'])
                $tmp .= " - (👮‍♂️)";
            
            if($row['blocked'])
                $tmp .= " - (⛔️)";
                
            if($row['tg_block'])
                $tmp .= " - (מחק את הרובוט)";
            
            $tmp .= "\n\n";
            $text .= $tmp;
            
            $i++;
        }
        
        if($i == 1 || $text == "רשימת משתמשים:\n"){
            $this->sendMessage($chatId, "אין משתמשים ברובוט חוץ ממך");
        }
        else{
            $this->SetParseMode('html');
            $this->sendMessage($chatId, $text);
        }
    }
    public function sendActiveUsersList($chatId){
        $result = $this->DBConn->query("SELECT * FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `blocked` != 1 AND `tg_block` != 1;");
        
        $i = 1;
        $text = "רשימת המשתמשים הפעילים:\n";
        
        while ($row = $result->fetch_assoc()) {
            if($i % 51 == 0){
                $this->SetParseMode('html');
                $this->sendMessage($chatId, $text);
                $text = "המשך רשימת המשתמשים הפעילים:\n";
            }
            
            if($chatId == $row['tg_id']) continue;
            
            $linkId = "<a href=\"tg://user?id=".$row['tg_id']."\">".$row['tg_id']."</a>";
            
            $tmp = $i.". ".$linkId;
            if($row['admin'])
                $tmp .= " - (👮‍♂️)";
            
            $tmp .= "\n\n";
            $text .= $tmp;
            
            $i++;
        }
        
        if($i == 1 || $text == "רשימת המשתמשים הפעילים:\n"){
            $this->sendMessage($chatId, "אין משתמשים ברובוט חוץ ממך");
        }
        else{
            $this->SetParseMode('html');
            $this->sendMessage($chatId, $text);
        }
    }
    public function sendCountsUser($chatId){
        $result = $this->DBConn->query("SELECT * FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users`");
        
        $counts = 0;
        $admins = 0;
        $blocks = 0;
        $tg_blocks = 0;
        $text = "נתונים על משתמשים:\n";
        
        while ($row = $result->fetch_assoc()) {
            if($row['admin'])
                $admins++;
            if($row['blocked'])
                $blocks++;
            if($row['tg_block'])
                $tg_blocks++;
            
            $counts++;
        }
        
        $text .= "סה\"כ משתמשים: ".$counts." \n";
        $text .= "מתוכם מנהלים: ".$admins." \n";
        $text .= "מתוכם חסומים: ".$blocks." \n";
        $text .= "מתוכם מחקו את הרובוט: ".$tg_blocks." \n";
        
        $this->sendMessage($chatId, $text);
    }
    public function sendAdminsList($chatId){
        $result = $this->DBConn->query("SELECT * FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `admin` = 1");
        
        $i = 1;
        $text = "רשימת מנהלים:\n";
        
        while ($row = $result->fetch_assoc()) {
            if($chatId == $row['tg_id']) continue;
            if($i % 51 == 0){
                $this->SetParseMode('html');
                $this->sendMessage($chatId, $text);
                $text = "המשך רשימת מנהלים\n";
            }
            
            $linkId = "<a href=\"tg://user?id=".$row['tg_id']."\">".$row['tg_id']."</a>";
            
            $tmp = $i.". ".$linkId;
            
            if($row['tg_block'])
                $tmp .= " - מחק את הרובוט";
            
            $tmp .= "\n\n";
            $text .= $tmp;
            
            $i++;
        }
        if($i == 1 || $text == "רשימת מנהלים:\n"){
            $this->sendMessage($chatId, "אתה המנהל היחיד :)");
        }
        else{
            $this->SetParseMode('html');
            $this->sendMessage($chatId, $text);
        }
    }
    public function sendBlockList($chatId){
        $result = $this->DBConn->query("SELECT * FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `blocked` = 1");
        
        $i = 1;
        $text = "רשימת חסומים:\n";
        
        while ($row = $result->fetch_assoc()) {
            if($i % 51 == 0){
                $this->SetParseMode('html');
                $this->sendMessage($chatId, $text);
                $text = "המשך רשימת חסומים:\n";
            }
            
            $linkId = "<a href=\"tg://user?id=".$row['tg_id']."\">".$row['tg_id']."</a>";
            
            $tmp = $i.". ".$linkId;
            
            if($row['tg_block'])
                $tmp .= " - מחק את הרובוט";
            
            $tmp .= "\n\n";
            $text .= $tmp;
            
            $i++;
        }
        if($i == 1){
            $this->sendMessage($chatId, "איזה כיף 😁 אף אחד לא חסום😎");
        }
        else{
            $this->SetParseMode('html');
            $this->sendMessage($chatId, $text);
        }
    }

    public function getUsersArray($blocks = null, $admins = null){
        $add = "";
        if($blocks === false){
            $add .= " AND `blocked` != '1'";
        }
        elseif($blocks === true){
            $add .= " AND `blocked` = '1'";
        }
        if($admins === false){
            $add .= " AND `admin` != '1'";
        }
        elseif($admins === true){
            $add .= " AND `admin` = '1'";
        }
        $result = $this->DBConn->query("SELECT `tg_id` FROM `" . (DB['dbname'] ?? "botMashov") . "`.`".$this->DBPerfix."users` WHERE `tg_block` = '0' {$add}");
        return $result->fetch_all();
    }
}