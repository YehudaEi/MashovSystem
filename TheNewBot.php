<?php
define('DB', array('host' => 'localhost', 'username' => 'root', 'password' => '', 'dbname' => 'botMashov'));

if(!isset($_GET['id']) || intval($_GET['id']) <= 0){
    http_response_code(403);
    die();
}

$tdb = new mysqli(DB['host'], DB['username'], DB['password'], DB['dbname']);
if($tdb == false || empty($tdb) || $tdb->connect_error){
    http_response_code(500);
    die();
}
$res = $tdb->Query("SELECT `value` FROM `".$_GET['id']."__data` WHERE `name` = 'token'");

if(isset($BOT) && isset($BOT['MAIN'])){
    define('BOT', array(
            'token' => $BOT['token'], 
            'webHookUrl' => $BOT['webHookUrl'], 
            'debug' => $BOT['debug'],
            'main' => true
        ));
}
elseif(!isset($res->num_rows) || $res->num_rows <= 0){
    http_response_code(403);
    $tdb->close();
    die();
}
else{
    define('BOT', array(
            'token' => $res->fetch_array()['value'],
            'webHookUrl' => "https://mashov.tg-bots.yehudae.net/TheNewBot.php?id=".$_GET['id'], 
            'debug' => false
        ));
}
$tdb->close();

require_once('MashovClass.php');
require_once('vars.php');
require_once('lang.php');
require_once('init.php');

if(isset(BOT['main']))
    return;

if(!isset($chatId) || empty($chatId))
    die('chatId not found');


$GroupID = $bot->getGroupID();

if($chatType != "private" && $GroupID != "Waiting" && $GroupID != $chatId){
    $bot->leaveChat($chatId);
}
elseif($chatType != "private" && $GroupID == "Waiting"){
    $bot->setGroupID($chatId);
    $bot->sendMessage($chatId, $lang['successGroup']);
}

elseif($bot->isAdmin($chatId) || $chatId == $GroupID){
    if(isset($update['message']['reply_to_message']['entities'][0]) && MashovBot::isBlockForwardMes($update['message']['reply_to_message']['text'], $update['message']['reply_to_message']['entities'][0])){
        $rfid = MashovBot::isBlockForwardMes($update['message']['reply_to_message']['text'], $update['message']['reply_to_message']['entities'][0]);
        $rtx = $lang['SystemMessage'];
        $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
    }
    
    if($message == "/start"){
        if($bot->isTgBlocked($chatId)){
            $bot->tgBlockUser($chatId, 0);
        }
        $bot->sendMessage($chatId, $lang['startMes']);
    }
    elseif($message == "/help"){
        $bot->sendMessage($chatId, $lang['help']);
    }
    elseif($message == "/welcome"){
        $bot->sendMessage($chatId, $lang['welcome']);
    }
    elseif($message == "/admin"){
        $bot->sendMessage($chatId, $lang['admin']);
    }
    elseif($message == "/ban"){
        $bot->sendMessage($chatId, $lang['ban']);
    }
    elseif($message == "/users"){
        $bot->sendMessage($chatId, $lang['users']);
    }    
    elseif($message == "/sendMessageToAllUSers"){
        $bot->sendMessage($chatId, $lang['sendMessageToAllUSers']);
    }
    elseif($message == "/group"){
        if($chatId == $bot->getCreatorID())
            $bot->sendMessage($chatId, $lang['group']);
        else
            $bot->sendMessage($chatId, $lang['groupError']);
    }
    elseif($message == "/sendFile"){
        $bot->sendMessage($chatId, $lang['sendFile']);
    }
    elseif($message == "/id"){
        $bot->SetParseMode("html");
        if(isset($rfid))
            $bot->sendMessage($chatId, 'האיידי של המשתמש הוא: <code>'.$rfid.'</code> (לחץ להעתקה)');
        else
            $bot->sendMessage($chatId, $lang['replyCommand'], null, $messageId);
    }
    
    elseif($message == $lang['block']){
        if(isset($rfid)){
            if($bot->isAdmin($rfid))
                $bot->sendMessage($chatId, $lang['errorBlockAdmin']);
            elseif($bot->isBlocked($rfid))
                $bot->sendMessage($chatId, $lang['userAlreadyBlocked']);
            else{
                $bot->blockUser($rfid, 1);
                
                $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
                $bot->SetParseMode('html');
                
                $bot->sendMessage($chatId, "🚫 המשתמש  ".$linkId." נחסם בהצלחה.");
                
                if(empty($GroupID)){
                    $adminsArr = $bot->getAdminIDS();
                    foreach ($adminsArr as $admin){
                        if($admin == $chatId || $admin == $fromId) continue;
                        
                        $bot->sendMessage($admin, "🚫 המשתמש  ".$linkId." נחסם בהצלחה ע\"י המנהל ".$chatLinkId);
                    }
                }
            }
        }
        else
            $bot->sendMessage($chatId, $lang['replyCommand'], null, $messageId);
    }
    elseif($message == $lang['unblock']){
        if(isset($rfid)){
            if(!$bot->isBlocked($rfid))
                $bot->sendMessage($chatId, "שגיאה! המשתמש לא חסום...");
            else{
                $bot->blockUser($rfid, 0);
                
                $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
                $bot->SetParseMode('html');
                
                $bot->sendMessage($chatId, "✅ חסימת המשתמש ".$linkId." בוטלה בהצלחה!");
                $bot->sendMessage($rfid, "✅ אתה כבר לא חסום ברובוט 😎");
            }
        }
        else
            $bot->sendMessage($chatId, $lang['replyCommand'], null, $messageId);
    }
    elseif($message == "רשימת חסומים"){
        $bot->sendBlockList($chatId);
    }
    
    elseif($message == "הפוך למנהל" || $message == "הגדר כמנהל"){
        if($chatId == $bot->getCreatorID() || $fromId == $bot->getCreatorID()){
            if(isset($rfid)){
                if($bot->isAdmin($rfid))
                    $bot->sendMessage($chatId, "שגיאה! המשתמש כבר מנהל...");
                elseif($bot->isBlocked($rfid))
                    $bot->sendMessage($chatId, "שגיאה! לא ניתן להפוך למנהל משתמש חסום...");
                else{
                    $bot->setAdmin($rfid, 1);
                    
                    $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
                    $bot->SetParseMode('html');
                    
                    $bot->sendMessage($chatId, "👮‍♂️ המשתמש  ".$linkId." מנהל עכשיו.");
                    $bot->sendMessage($rfid, "👮‍♂️ אתה מנהל עכשיו 😎");
                }
            }
            else
                $bot->sendMessage($chatId, $lang['replyCommand'], null, $messageId);
        }
        else{
            $bot->sendMessage($chatId, "שגיאה! רק יוצר הבוט יכול להוסיף מנהלים...");
        }
    }
    elseif($message == "הסר מניהול" || $message == "ביטול ניהול"){
        if($chatId == $bot->getCreatorID() || $fromId == $bot->getCreatorID()){
            if(isset($rfid)){
                if(!$bot->isAdmin($rfid))
                    $bot->sendMessage($chatId, "שגיאה! המשתמש לא מנהל...");
                else{
                    $bot->setAdmin($rfid, 0);
                    
                    $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
                    $bot->SetParseMode('html');
                    
                    $bot->sendMessage($chatId, "😕 המשתמש ".$linkId." כבר לא מנהל ברובוט.");
                    $bot->sendMessage($rfid, "😞 אתה כבר לא מנהל ברובוט");
                }
            }
            else
                $bot->sendMessage($chatId, $lang['replyCommand'], null, $messageId);
        }
        else{
            $bot->sendMessage($chatId, "שגיאה! רק יוצר הבוט יכול להסיר מנהלים...");
        }
    }
    elseif($message == "רשימת מנהלים"){
        $bot->sendAdminsList($chatId);
    }
    
    elseif($message == "רשימת משתמשים"){
        $bot->sendUsersList($chatId);
    }
    
    elseif($message == "רשימת משתמשים פעילים"){
        $bot->sendActiveUsersList($chatId);
    }

    elseif($message == "משתמשים במספרים"){
        $bot->sendCountsUser($chatId);
    }

    elseif($message == "הודעת פתיחה"){
        if($chatId == $bot->getCreatorID() || $fromId == $bot->getCreatorID()){
            //$kb = json_encode(array('force_reply' => true));
            $bot->sendMessage($chatId, " שלח את התוכן החדש להודעת הפתיחה בהשב על 'הודעת הפתיחה' ששלחת."."\n\n📍לידעתך, הודעת הפתיחה היא:\n\n".$bot->getStartMessage());
        }
        else
            $bot->sendMessage($chatId, "רק היוצר של הבוט יכול לשנות את ההודעת פתיחה ☹️");
    }
    elseif($rtx == "הודעת פתיחה"){
        if($chatId == $bot->getCreatorID() || $fromId == $bot->getCreatorID()){
            if(strlen($message) < 6000){
                $bot->setStartMessage($message);
                $bot->sendMessage($chatId, "הודעת הפתיחה הוגדרה ל:\n\n".$message, null, null);
            }
            else
                $bot->sendMessage($chatId, "הודעת הפתיחה ארוכה מידי... נסה לקצר אותה קצת");
        }
        else
            $bot->sendMessage($chatId, "רק היוצר של הבוט יכול לשנות את ההודעת פתיחה ☹️");
    }
    
    elseif($message == "קבוצה"){
        if($chatId == $bot->getCreatorID()){
            if(!empty($GroupID))
                $bot->sendMessage($chatId, "שגיאה! יש כבר קבוצה מוגדרת ...");
            else{
                $bot->setGroupID("Waiting");
                $keyboard = json_encode(array('inline_keyboard' =>  array(array(array('text' => 'להוספת הרובוט לקבוצה יש ללחוץ כאן ➕', 'url' => 'http://t.me/'.$bot->getBotUserName().'?startgroup=true')))));
                $bot->sendMessage($chatId, "הוסף עכשיו את הרובוט לקבוצה המבוקשת\n\nהערה: הבוט לא יעבוד עד שלא תסיים את התהליך!", $keyboard);
            }
        }
        else
            $bot->sendMessage($chatId, "רק היוצר של הבוט יכול לשנות את הגדרות הקבוצה ☹️");
    }
    elseif($message == "מחיקת קבוצה"){
        if($chatId == $bot->getCreatorID()){
            if(empty($GroupID))
                $bot->sendMessage($chatId, "שגיאה! אין קבוצה מוגדרת...");
            else{
                $bot->leaveChat($bot->getGroupID());
                $bot->setGroupID(null);
                $bot->sendMessage($chatId, "הקבוצה נמחקה בהצלחה 👌");
            }
        }
        else
            $bot->sendMessage($chatId, "רק היוצר של הבוט יכול לשנות את הגדרות הקבוצה ☹️");
    }
    elseif($message == "הודעת תפוצה"){
        if(true){
            $kb = json_encode(array('force_reply' => true));
            $bot->sendMessage($chatId, $lang['sendMessageToAllUsersStep1'], $kb);
        }
        else{
            $bot->sendMessage($chatId, $lang['creatorOnlySendMessageToAllUsers']);
        }
    }
    elseif($rtx == $lang['sendMessageToAllUsersStep1']){
        if(true){
            if($message == $lang['cancelHe'] || $message == $lang['cancelEn']){
                $bot->sendMessage($chatId, $lang['sendMessageToAllUsersCancel']);
            }
            else{
                $bot->sendMessage($chatId, $lang['sendMessageToAllUsersStart']);

                $count = 0;
                $newBlocks = 0;
                $users = $bot->getUsersArray(false);
                foreach($users as $user){
                    $count++;
                    $userId = $user[0];

                    if(isset($message)){
                        $tmp = $bot->sendMessage($userId, $message);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, "text", $message, $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($phid)){
                        $tmp = $bot->sendPhoto($userId, $phid, $cap);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $update['message']['photo']['caption'] = $cap;
                        $bot->SetUpdate($update, $messageId, 'photo', $update['message']['photo'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($auid)){
                        $tmp = $bot->sendAudio($userId, $auid);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, 'audio', $update['message']['audio'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($did)){
                        $tmp = $bot->sendDocument($userId, $did, $cap);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $update['message']['document']['caption'] = $cap;
                        $bot->SetUpdate($update, $messageId, 'document', $update['message']['document'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($vidid)){
                        $tmp = $bot->sendVideo($userId, $vidid, null, null, null, $cap);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $update['message']['video']['caption'] = $cap;
                        $bot->SetUpdate($update, $messageId, 'video', $update['message']['video'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($void)){
                        $tmp = $bot->sendVoice($userId, $void);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, 'voice', $update['message']['voice'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($vnid)){
                        $tmp = $bot->sendVideoNote($userId, $vnid);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, 'video_note', $update['message']['video_note'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($conid)){
                        $tmp = $bot->sendContact($userId, $conid, $conf, $conl);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];

                        $bot->SetUpdate($update, $messageId, 'contact', $update['message']['contact'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($locid1)){
                        $tmp = $bot->sendLocation($userId, $locid1, $locid2);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, 'location', $update['message']['location'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($sti)){
                        $tmp = $bot->sendSticker($userId, $sti);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];
                        
                        $bot->SetUpdate($update, $messageId, 'sticker', $update['message']['sticker'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($venTit)){
                        $tmp = $bot->sendVenue($userId, $venLoc1, $venLoc2, $venTit, $venAdd);
                        $toIdsArr[$userId] = $tmp['result']['message_id'];

                        $bot->SetUpdate($update, $messageId, 'venue', $update['message']['venue'], $chatId, $fromId, $toIdsArr);
                    }
                    elseif(isset($poll)){
                        $bot->sendMessage($chatId, "כרגע אי אפשר לשלוח סקרים דרך בוטים.", null, $messageId);
                        $bot->SetUpdate($update, $messageId, 'poll', $update['message']['poll'], $chatId, $fromId, array());
                    }
                    if(!$tmp['ok']){
                        if($tmp['error_code'] == "403"){
                            if($tmp['description'] == "Forbidden: bot was blocked by the user"){
                                $newBlocks++;
                                $bot->tgBlockUser($userId, 1);
                            }
                        }
                    }
                }

                $textDone = $lang['sendMessageToAllUsersDone'];

                $textDone = str_replace("{1}", $count, $textDone);
                $textDone = str_replace("{2}", $newBlocks, $textDone);

                $bot->sendMessage($chatId, $textDone);
            }
        }
        else{
            $bot->sendMessage($chatId, $lang['creatorOnlySendMessageToAllUsers']);
        }
    }
    
    elseif($isEdited && $rtmfid == $bot->getBotID()){
            $bot->sendMessage($chatId, "כרגע מנהלים לא יכולים לערוך הודעות.", null, $messageId);
            die();
        }
    elseif(isset($rfid)){
        $adminsArr = array();
        if(empty($GroupID))
            $adminsArr = $bot->getAdminIDS();
        $toIdsArr = array();
        
        $linkId = "<a href=\"tg://user?id=".$rfid."\">".$rfid."</a>";
        
        if(isset($message)){
            $tmp = $bot->sendMessage($rfid, $message);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את ההודעה הבאה: \n\n".$message);
                $toIdsArr[$admin] = $res['result']['message_id'];
            }
            $bot->SetUpdate($update, $messageId, "text", $message, $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($phid)){
            $tmp = $bot->sendPhoto($rfid, $phid, $cap);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendPhoto($admin, $phid, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את התמונה הזו\n\n".$cap);
                $toIdsArr[$admin] = $res['result']['message_id'];
            }
            
            $update['message']['photo']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'photo', $update['message']['photo'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($auid)){
            $tmp = $bot->sendAudio($rfid, $auid);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendAudio($admin, $auid);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את הקובץ הזה", null, $res['result']['message_id']);
            }
            $bot->SetUpdate($update, $messageId, 'audio', $update['message']['audio'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($did)){
            $tmp = $bot->sendDocument($rfid, $did, $cap);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendDocument($admin, $did, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את הקובץ הזה\n\n".$cap);
                $toIdsArr[$admin] = $res['result']['message_id'];
            }
            $update['message']['document']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'document', $update['message']['document'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($vidid)){
            $tmp = $bot->sendVideo($rfid, $vidid, null, null, null, $cap);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendVideo($admin, $vidid, null, null, null, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את הסרטון הזה\n\n".$cap);
                $toIdsArr[$admin] = $res['result']['message_id'];
            }
            
            $update['message']['video']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'video', $update['message']['video'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($void)){
            $tmp = $bot->sendVoice($rfid, $void);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendVoice($admin, $void);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את ההקלטה הזו", null, $res['result']['message_id']);
            }
            $bot->SetUpdate($update, $messageId, 'voice', $update['message']['voice'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($vnid)){
            $tmp = $bot->sendVideoNote($rfid, $vnid);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendVideoNote($admin, $vnid);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את הסרטון העגול הזה", null, $res['result']['message_id']);
            }
            $bot->SetUpdate($update, $messageId, 'video_note', $update['message']['video_note'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($conid)){
            $tmp = $bot->sendContact($rfid, $conid, $conf, $conl);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendContact($admin, $conid, $conf, $conl);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את איש קשר הזה", null, $res['result']['message_id']);
            }
            $bot->SetUpdate($update, $messageId, 'contact', $update['message']['contact'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($locid1)){
            $tmp = $bot->sendLocation($rfid, $locid1, $locid2);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
            
            $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendLocation($admin, $locid1, $locid2);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את המיקום הזה", null, $res['result']['message_id']);
            }
            $bot->SetUpdate($update, $messageId, 'location', $update['message']['location'], $chatId, $fromId, $toIdsArr);
        }
		elseif(isset($sti)){
		    $tmp = $bot->sendSticker($rfid, $sti);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
		    
		    $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendSticker($admin, $sti);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את המדבקה הזו", null, $res['result']['message_id']);
            }
		    $bot->SetUpdate($update, $messageId, 'sticker', $update['message']['sticker'], $chatId, $fromId, $toIdsArr);
		}
		elseif(isset($venTit)){
		    $tmp = $bot->sendVenue($rfid, $venLoc1, $venLoc2, $venTit, $venAdd);
            $toIdsArr[$rfid] = $tmp['result']['message_id'];
		    
		    $bot->SetParseMode('html');
            foreach ($adminsArr as $admin){
                if($admin == $chatId || $admin == $fromId) continue;
                
                $res = $bot->sendVenue($admin, $venLoc1, $venLoc2, $venTit, $venAdd);
                $toIdsArr[$admin] = $res['result']['message_id'];
                $bot->sendMessage($admin, "המנהל ".$chatLinkId." שלח למשתמש ".$linkId." את ההזמנה הזו", null, $res['result']['message_id']);
            }
		    $bot->SetUpdate($update, $messageId, 'venue', $update['message']['venue'], $chatId, $fromId, $toIdsArr);
		}
        elseif(isset($poll)){
            $bot->sendMessage($chatId, "כרגע אי אפשר לשלוח סקרים דרך בוטים.", null, $messageId);
            $bot->SetUpdate($update, $messageId, 'poll', $update['message']['poll'], $chatId, $fromId, $toIdsArr);
		}
        else
            $res = $bot->sendMessage($chatId, "לא היה ניתן לשלוח את ההודעה...\nאנא פנה ל-@ContactMashovBot", null, $messageId);
	    if(!$tmp['ok']){
	        if($tmp['error_code'] == "403"){
	            if($tmp['description'] == "Forbidden: bot was blocked by the user"){
                    $bot->tgBlockUser($rfid, 1);
	                $bot->sendMessage($chatId, "❌ ההודעה לא נשלחה למשתמש כי הוא מחק את הבוט!", null, $messageId);
                }
                elseif($tmp['description'] == "Forbidden: bot can't initiate conversation with a user")
	                $bot->sendMessage($chatId, "❌ לא ניתן לשלוח הודעה למשתמש שאינו רשום בבוט!", null, $messageId);
	        }
	        else $bot->sendMessage($chatId, "❌ ההודעה לא נשלחה מסיבה לא ידועה \nאנא פנה ל-@ContactMashovBot", null, $messageId);
	    }
    }
    
    elseif(!isset($BOT['MAIN']) && $chatType == "private"){
        $bot->sendMessage($chatId, "❌ שלח את הודעתך בהשב להודעת המשתמש.\n\nאו השב על ההודעה ID של משתמש רשום בבוט. (ניתן לקבל ID של משתמש באמצעות הגבה על הודעה שלו /id)", null, $messageId);
    }
}
else{
    if($GroupID == "Waiting"){
        $bot->sendMessage($chatId, "היוצר באמצע להגדיר כמה דברים בבוט כרגע. נסה שוב בעוד כמה דקות...");
        $bot->sendMessage($bot->getCreatorID(), "יותר מהר.. אנשים מנסים להכנס לבוט אבל נתקלים בשגיאה כי אתה לא מגדיר את הקבוצה...\n\nלביטול ההגדרה שלח: \"מחיקת קבוצה\"");
        die();
    }
    elseif($bot->isBlocked($chatId) || $bot->isBlocked($fromId)){
        $bot->sendMessage($chatId, "אתה חסום בבוט :(");
        die();
    }
    if($isEdited){
        $adminsArr = $bot->getToIdByMessageId($messageId);
        
        foreach ($adminsArr as $toID => $mesID){
            if(isset($phid)){
                $res = $bot->sendPhoto($toID, $phid, $editedText.$cap, $mesID);
            }
            elseif(isset($auid)){
                $res = $bot->sendAudio($toID, $auid, null, null, null, $mesID);
            }
            elseif(isset($did)){
                $res = $bot->sendDocument($toID, $did, $editedText.$cap, $mesID);
            }
            elseif(isset($vidid)){
                $res = $bot->sendVideo($toID, $vidid, null, null, null, $editedText.$cap, $mesID);
        	}
            elseif(isset($message)){
                $res = $bot->sendMessage($toID, $editedText.$message, null, $mesID);
            }
        }
    }
    
    
    elseif(!empty($GroupID)){
        $toID = $GroupID;
        
        if(strpos($message, "/start") === 0){
            if($bot->isTgBlocked($chatId)){
                $bot->tgBlockUser($chatId, 0);
            }

            $startMes = $bot->getStartMessage();
            if($bot->startMesCredit() == "yes"){
                $startMes .= $lang['credit'];
            }
    	    $bot->SetParseMode("html");
            $res = $bot->sendMessage($chatId, $startMes);
    	    if(!$res['ok'] && strpos($res['description'], "Bad Request: can't parse entities:")){
    	        $bot->SetParseMode(null);
    	        $bot->sendMessage($chatId, $messageText);
    	    }
        }
        $res = $bot->forwardMessage($toID, $chatId, $messageId);
        
        if(!$res['ok'] && $res['description'] == "Forbidden: bot was kicked from the supergroup chat"){
            $bot->setGroupID(null);
            $bot->sendMessage($chatId, "היוצר של הבוט קצת מצחיקול והוא עשה שטות קטנה ולכן הוא לא קיבל את ההודעה הזו, תשלח אותה שוב בבקשה...");
        }
        
        $bot->SetParseMode("html");
        if((isset($res['result']['forward_from_chat']['username']) && $res['result']['forward_from_chat']['username'] == "HiddenSender") || isset($res['result']['forward_sender_name'])){
	        if(isset($res['result']['message_id'])){
	            $bot->sendMessage($toID, $hiddenUserLink.
    	        "⚠️משתמש זה הגדיר בהגדרות הפרטיות שלו שלא ניתן להעביר ממנו הודעות.\nבכדי לענות למשתמש זה אנא השב על ההודעה הזו."
    	        , null, $res['result']['message_id']);
    	        if($message == "/start")
    	            $bot->sendMessage($toID, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
	        }
	    }
	    elseif(isset($update['message']['forward_from']) && $update['message']['forward_from']['id'] != $chatId){
	        if(isset($res['result']['message_id'])){
    	        $bot->sendMessage($toID, $hiddenUserLink.
    	        "⚠️הודעה זו הועברה מהמשתמש ".$chatLinkId.". בכדי לענות לו יש להשיב להודעה זו."
    	        , null, $res['result']['message_id']);
    	        if($message == "/start")
    	            $bot->sendMessage($toID, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
	        }
	    }
	    elseif(isset($update['message']['forward_from_chat']) && $update['message']['forward_from_chat']['id'] != $chatId){
	        if(isset($res['result']['message_id'])){
    	        $bot->sendMessage($toID, $hiddenUserLink.
    	        "⚠️הודעה זו הועברה מהמשתמש ".$chatLinkId.". בכדי לענות לו יש להשיב להודעה זו."
    	        , null, $res['result']['message_id']);
    	        if($message == "/start")
    	            $bot->sendMessage($toID, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
	        }
        }
        
        $toIdsArr = array($toID => $res['result']['message_id']);
    }
    else{
        $adminsArr = $bot->getAdminIDS();
        $toIdsArr = array();
        
        if(strpos($message, "/start") === 0){
            if($bot->isTgBlocked($chatId)){
                $bot->tgBlockUser($chatId, 0);
            }

            $startMes = $bot->getStartMessage();
            if($bot->startMesCredit() == "yes"){
                $startMes .= $lang['credit'];
            }
    	    $bot->SetParseMode("html");
            $res = $bot->sendMessage($chatId, $startMes);
    	    if(!$res['ok'] && strpos($res['description'], "Bad Request: can't parse entities:")){
    	        $bot->SetParseMode(null);
    	        $bot->sendMessage($chatId, $messageText);
    	    }
        }
        
        foreach ($adminsArr as $admin){
            $res = $bot->forwardMessage($admin, $chatId, $messageId);
            
            $toIdsArr[$admin] = $res['result']['message_id'];
            
            $bot->SetParseMode("html");
            if((isset($res['result']['forward_from_chat']['username']) && $res['result']['forward_from_chat']['username'] == "HiddenSender") || isset($res['result']['forward_sender_name'])){
    	        if(isset($res['result']['message_id'])){
    	            $tmp = $bot->sendMessage($admin, $hiddenUserLink.
        	        "⚠️משתמש זה הגדיר בהגדרות הפרטיות שלו שלא ניתן להעביר ממנו הודעות.\nבכדי לענות למשתמש זה אנא השב על ההודעה הזו."
        	        , null, $res['result']['message_id']);
        	        
        	        $toIdsArr[$admin] = $tmp['result']['message_id'];
        	        
        	        if($message == "/start")
    	                $bot->sendMessage($admin, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
    	        }
    	    }
    	    elseif(isset($update['message']['forward_from']) && $update['message']['forward_from']['id'] != $chatId){
    	        if(isset($res['result']['message_id'])){
        	        $bot->sendMessage($admin, $hiddenUserLink.
        	        "⚠️הודעה זו הועברה מהמשתמש ".$chatLinkId.". בכדי לענות לו יש להשיב להודעה זו."
        	        , null, $res['result']['message_id']);
        	        if($message == "/start")
    	                $bot->sendMessage($admin, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
    	        }
    	    }
    	    elseif(isset($update['message']['forward_from_chat']) && $update['message']['forward_from_chat']['id'] != $chatId){
    	        if(isset($res['result']['message_id'])){
        	        $bot->sendMessage($admin, $hiddenUserLink.
        	        "⚠️הודעה זו הועברה מהמשתמש ".$chatLinkId.". בכדי לענות לו יש להשיב להודעה זו."
        	        , null, $res['result']['message_id']);
        	        if($message == "/start")
    	                $bot->sendMessage($admin, "קישור למשתמש הנ\"ל: ".$chatLinkId.".\n(קישור זה לא ישלח כל פעם)", null, $res['result']['message_id']);
    	        }
            }   
        }
    }
    
    
    if($isEdited){
        if(isset($phid)){
            $update['edited_message']['photo']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'edited', $update['edited_message']['photo']);
        }
        elseif(isset($auid)){
            $update['edited_message']['audio']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'edited', $update['edited_message']['audio']);
        }
        elseif(isset($did)){
            $update['edited_message']['document']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'edited', $update['edited_message']['document']);
        }
        elseif(isset($vidid)){
            $update['edited_message']['video']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'edited', $update['edited_message']['video']);
    	}
        elseif(isset($message)){
            $bot->SetUpdate($update, $messageId, "edited", $message);
        }
    }
    else{
        if(isset($phid)){
            $update['message']['photo']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'photo', $update['message']['photo'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($auid)){
            $update['message']['audio']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'audio', $update['message']['audio'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($did)){
            $update['message']['document']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'document', $update['message']['document'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($vidid)){
            $update['message']['video']['caption'] = $cap;
            $bot->SetUpdate($update, $messageId, 'video', $update['message']['video'], $chatId, $fromId, $toIdsArr);
    	}
        elseif(isset($void)){
            $bot->SetUpdate($update, $messageId, 'voice', $update['message']['voice'], $chatId, $fromId, $toIdsArr);
    	}
        elseif(isset($vnid)){
            $bot->SetUpdate($update, $messageId, 'video_note', $update['message']['video_note'], $chatId, $fromId, $toIdsArr);
    	}
        elseif(isset($conid)){
            $bot->SetUpdate($update, $messageId, 'contact', $update['message']['contact'], $chatId, $fromId, $toIdsArr);
    	}
        elseif(isset($locid1)){
            $bot->SetUpdate($update, $messageId, 'location', $update['message']['location'], $chatId, $fromId, $toIdsArr);
    	}
    	elseif(isset($sti)){
            $bot->SetUpdate($update, $messageId, 'sticker', $update['message']['sticker'], $chatId, $fromId, $toIdsArr);
    	}
    	elseif(isset($venTit)){
            $bot->SetUpdate($update, $messageId, 'venue', $update['message']['venue'], $chatId, $fromId, $toIdsArr);
    	}
        elseif(isset($poll)){
            $bot->SetUpdate($update, $messageId, 'poll', $update['message']['poll'], $chatId, $fromId, $toIdsArr);
        }
        elseif(isset($message)){
            $bot->SetUpdate($update, $messageId, "text", $message, $chatId, $fromId, $toIdsArr);
        }
    }
}

