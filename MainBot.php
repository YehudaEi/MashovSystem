<?php 
header('Content-Type: text/HTML; charset=utf-8');
date_default_timezone_set('Asia/Jerusalem');

$update = file_get_contents('php://input');
$update = json_decode($update, true); 

if(json_decode(file_get_contents('php://input'), true) == NULL){
    http_response_code(403);
    die();
}

function curlPost($method, $datas=[]==NULL){
    $token = "";
    
    $urll = "https://api.telegram.org/bot".$token."/".$method;
	
    $ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$urll);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
   
    $res = curl_exec($ch);
    $tmp = json_decode($res, true);
    $tmp['log_type'] = "output - main";
    $tmp['log_date'] = date(DATE_RFC850);
    $tmp['log_bot_token'] = "MainToken";
    file_put_contents("log.log", json_encode($tmp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).",\n", FILE_APPEND | LOCK_EX);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
		curl_close($ch);
    }else{
		curl_close($ch);
        return json_decode($res, true);
      
    }
}
function sendMessage($id, $message, $pm = "Markdown"){
    $PostData = array(
        'chat_id' => $id,
        'text' => $message,
        'parse_mode' => $pm, 
        'disable_web_page_preview' => true
    );
    $out = curlPost('sendMessage',$PostData);
    return $out;
}
function deleteMessage($id, $messageId){
    $data["chat_id"] = $id;
    $data["message_id"] = $messageId;
    return curlPost("deleteMessage", $data);
}
function getToken($token) {
	$searchstring1 = "You can use this token to access HTTP API:";
	$searchstring2 = "Use this token to access the HTTP API:";
	$searchstring3 = "New token is:";
	$endstring = "For a description of the Bot API, see this page: https://core.telegram.org/bots/api";
	$pos1 = strpos($token,$searchstring1);
	if ($pos1 !== FALSE) {
		$pos1 += strlen($searchstring1) + 1;
		$length = strpos($token,$endstring);
		if ($length !== FALSE) {
			$length -= ($pos1 + 2);
			$token = substr($token,$pos1,$length);
		} else {
			$token = substr($token,$pos1);
		}} 
	$pos2 = strpos($token,$searchstring2);
    if ($pos2 !== FALSE && !($pos1 !== FALSE)){
			$pos2 += strlen($searchstring2) + 1;
			$length = strpos($token,$endstring);
			if ($length !== FALSE) {
				$length -= ($pos2 + 2);
				$token = substr($token,$pos2,$length);
			} else {
				$token = substr($token,$pos2);
			}
	}
	$pos3 = strpos($token,$searchstring3);
    if ($pos3 !== FALSE && !($pos1 !== FALSE) && !($pos2 !== FALSE)){
		$pos3 += strlen($searchstring3) + 2;
		$length = strpos($token,$endstring);
		if ($length !== FALSE) {
			$length -= ($pos3 + 2);
			$token = substr($token,$pos3,$length);
		} else {
			$token = substr($token,$pos3);
		}
	}
	if(strlen($token) == 45 || strlen($token) == 46)
	    $bot_info = json_decode(file_get_contents("https://api.telegram.org/bot".$token."/getMe"), true);
	else
	    $bot_info['ok'] = false;
	if ($bot_info['ok'] == true) {
		$res = [
			'ok' => true,
		    'id' => $bot_info['result']['id'],
			'username' => $bot_info['result']['username'],
			'first_name' => $bot_info['result']['first_name'],
			'token' => $token,
		];} 
	else {
		$res = [
			'ok' => false,
			'token' => $token,
		];
	}
	return $res;
}

$msg = $update['message']['text'] ?? null;
$userId = $update['message']['chat']['id'] ?? null;
$firstName = $update["message"]["from"]["first_name"] ?? null;
$lastName = $update["message"]["from"]["last_name"] ?? null;
$userName = trim($firstName." ".$lastName);

if(isset($msg) && $msg != "/start"){
    $res = getToken($msg);
    if($res['ok']){
        
        $text = "יש 💪
הצלחנו להוסיף את הבוט שלך למערכת שלנו 🥳

הנה פרטים שמצאנו על הבוט שלך:
טוקן - <code>".$res['token']."</code>
שם - ".$res['first_name']."
שם משתמש - @".$res['username']."

תהנה 😎

ועוד משהו קטן, אם אתה נתקע אנחנו תמיד פה בשבילך @MashovSupport 😄";
        
        $BOT = array('token' => $res['token'], 'webHookUrl' => "https://mashov.tg-bots.yehudae.net/NewVersion/TheNewBot.php?id=".$res['id'], 'debug' => false, 'MAIN' => true);
        
        $_GET['id'] = $res['id'];
        $update = null;
        include('TheNewBot.php');
        
        $bot->createBot($userId, $userName);
        if($bot->getCreatorBlocked()){
            $text = "עדכון קטן,
לצערינו נחסמת⛔️ במערכת משוב עקב דיווחים רבים של משתמשים👥 בנוגע להתחזות ופישינג🐠.

חושב שחלה טעות? ניתן לפנות ל-@MashovSupport ונבדוק את הסיפור.
⚠️ הודעה חשובה: אנחנו לא מטפלים בפניות לא מכובדות ופניות דורשות ‼️.

כל טוב.";
            deleteMessage($userId, $resMessage['result']['message_id']);
            sendMessage($userId, $text, "html");
        }
        die();
    }
    else sendMessage($userId, "לא הצלחתי להבין מה אתה רוצה..", "html");
}
sendMessage($userId, "👋🏼 היי, ברוכים הבאים למערכת משוב😎

🤖 רובוט זה נועד בשביל לאפשר למשתמשים ליצור איתך קשר גם כשהם במצב ספאם, ולא יכולים לשלוח הודעות בצ'אט פרטי.

📩 על מנת ליצור רובוט משוב משלך, שלח לי בבקשה את ה[טוקן](http://t.me/help_Fatherbot?start=ODUyNjYyMjk1IDE3) שקיבלת מ[בוט פאזר](http://t.me/BotFather).

⛔️️ הודעה חשובה!! חל איסור מוחלט להשתמש במערכת המשוב לדברים שאינם חוקיים!! ⛔

 👤 לכל בעיה/תקלה/הערה/הארה ניתן לפנות לתמיכה @MashovSupport");