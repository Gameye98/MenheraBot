<?php

/*
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <http://unlicense.org/>
 */

require 'antibot.php';
require 'fake.php';
require 'Scrapping.php';
require 'vendor/autoload.php';
include 'ascii.php';
system('stty cbreak -echo');
use YoutubeDl\YoutubeDl;
use seregazhuk\PinterestBot\Factories\PinterestBot;
use masokky\QuoteMaker;
$pinterest = PinterestBot::create();
$pinterest->auth->login('', ''); // username and password pinterest
class Telegram {
    protected $api_dev_key = ''; // pastebin api dev key
    protected $api_paste_private = '0';
    protected $api_paste_expire_date ='10M';
    protected $api_paste_format ='text';
    protected $api_user_key = '';

    public function __construct()
    {
        set_time_limit(0);
        ignore_user_abort(0);
        ini_set('max_execution_time', 0); //exec time
        ini_set('memory_limit', '999999999M'); //memmory limit
        date_default_timezone_set('Asia/Jakarta'); // timezone
        define('BOT_TOKEN', ''); // Token
        define('USERNAME', ''); // author Username
        define('BOTNAME',''); // alias bot Name
    }
    public function post_data($url, $fields) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($ch, CURLOPT_NOBODY, 0);
        return curl_exec($ch);
        curl_close($ch);
    }
    public function hex($str)
    {
        $ec = bin2hex($str);
        $ec = chunk_split($ec, 2, '\x');
        $ec = '\x' . substr($ec, 0, strlen($ec) - 2);
        return $ec;
    }
    public function bot($method, $datas = [])
    {
        return json_decode(self::post_data('https://api.telegram.org/bot'.BOT_TOKEN.'/'.$method,$datas), true);
    }
    public function getupdates($up_id)
    {
        $get=$this->bot('getupdates', ['offset' => $up_id]);
        return @end($get['result']);
    }
    public function pastebin($code, $name)
    {
        $api_paste_code=urlencode($code);
        $api_paste_name=urlencode($name);
        $options=[
            'api_option'=>'paste',
            'api_user_key'=>$this->api_user_key,
            'api_paste_private'=>$this->api_paste_private,
            'api_paste_name'=>$api_paste_name,
            'api_paste_expire_date'=>$this->api_paste_expire_date,
            'api_paste_format'=>$this->api_paste_format,
            'api_dev_key'=>$this->api_dev_key,
            'api_paste_code'=>$api_paste_code
        ];
        return self::post_data('http://pastebin.com/api/api_post.php',$options);
    }
}
$dl = new YoutubeDl([
    'extract-audio' => true,
    'audio-format' => 'mp3',
    'audio-quality' => 0, // best
    'output' => '%(title)s.%(ext)s',
]);
$telegram=new Telegram();
$zodiak=new Scrap();
$last_up=0;
$cycle=1;
while (true) {
    $start=microtime(true);
    $last_up=$telegram->getupdates($last_up['update_id'] + 1);
    $msg = @$last_up['message'];
    if(!(empty($msg['text']))) {
        if(empty($msg['chat']['title'])) {
            $msg['chat']['title']=null;
        }
        if(empty($msg['from']['username'])) {
            $msg['from']['username']=null;
        }
        echo "\n";
        echo 'Chat Title : '.$msg['chat']['title']."\n";
        echo 'Chat ID : '.$msg['chat']['id']."\n";
        echo 'Name : '.$msg['from']['first_name']."\n";
        echo 'Username: '.$msg['from']['username']."\n";
        echo 'Text : '.$msg['text']."\n";
        echo 'Date : '.date('d/m/Y H:i:s',$msg['date'])."\n\n";

        $data=array(
            'username'=>$msg['from']['username'],
            'chat_title'=>$msg['chat']['title'],
            'chat_id'=>$msg['chat']['id'],
            'name'=>$msg['from']['first_name'],
            'username'=>$msg['from']['username'],
            'text'=>$msg['text'],
            'date'=>date('d/m/Y H:i:s', $msg['date'])
        );

        $tulis=fopen('storage/chat_logs.json','a');
        fwrite($tulis, json_encode($data)."\n");
        fclose($tulis);
        unset($tulis);
    }
    else {
        continue;
    }

    if(preg_match('/^\/pinterest/',strtolower($msg['text']))) {
        $pins = $pinterest->pins->search(substr($msg['text'],11))->toArray();
        $pages=explode(' ',$msg['text']);
        $pages=end($pages);
        $pages-=1;
        $status = $telegram->bot('sendPhoto',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'photo'=>$pins[$pages]['images']['orig']['url'],
            'caption'=>"total : ".sizeof($pins)."\n{$pins[$pages]['description']}"
            ]);
    }

    elseif(preg_match('/^\/zodiac/', strtolower($msg['text']))) {
        $zodiak->setUrl('http://astrology.com/horoscope/daily/'.substr($msg['text'],8).'.html');
        $title=$zodiak->getData($zodiak->data, '<title>','</title>');
        $article=str_replace('</span> ','',$zodiak->getData($zodiak->data, '<span class="date">','</p>'));
        $article=str_replace(':', "\n", $article);

        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'parse_mode'=>'html',
            'text'=>'<b>'.$title."\n</b><pre>".$article.'</pre>'
            ]);
    }

    elseif(preg_match('/^\/finance/', strtolower($msg['text']))) {
        $zodiak->setUrl('http://astrology.com/horoscope/daily-finance/'.substr($msg['text'],9).'.html');
        $title=$zodiak->getData($zodiak->data, '<title>','</title>');
        $article=str_replace('</span> ','',$zodiak->getData($zodiak->data, '<span class="date">','</p>'));
        $article=str_replace(':', "\n", $article);

        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'parse_mode'=>'html',
            'text'=>'<b>'.$title."\n</b><pre>".$article.'</pre>'
            ]);
    }
    elseif(preg_match('/^\/sexscope/', strtolower($msg['text']))) {
        $zodiak->setUrl('http://www.astrology.com/horoscope/monthly-sex/'.substr($msg['text'],10).'.html');
        $title=$zodiak->getData($zodiak->data, '<title>','</title>');
        $article=str_replace('</span> ','',$zodiak->getData($zodiak->data, '<span class="date">','</p>'));
        $article=str_replace(':', "\n", $article);
        $article=str_replace('<br>',"\n",$article);

        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'parse_mode'=>'html',
            'text'=>'<b>'.$title."\n</b><pre>".$article.'</pre>'
            ]);
    }

    elseif(preg_match('/^\/business/', strtolower($msg['text']))) {
        $zodiak->setUrl('https://www.astrology.com/horoscope/monthly-business/'.substr($msg['text'],10).'.html');
        $title=$zodiak->getData($zodiak->data, '<title>','</title>');
        $article=str_replace('</span> ','',$zodiak->getData($zodiak->data, '<span class="date">','</p>'));
        $article=str_replace(':', "\n", $article);
        $article=str_replace('<br>',"\n",$article);

        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'parse_mode'=>'html',
            'text'=>'<b>'.$title."\n</b><pre>".$article.'</pre>'
            ]);
    }

    elseif(preg_match('/^\/help/', strtolower($msg['text']))) {
        $list=file_get_contents(__DIR__.'/storage/list_command.txt');
        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'text'=>"Callable command List : \n".$list
            ]);
        unset($list);
    }

    elseif(preg_match('/^\/faker/', strtolower($msg['text']))) {
        $fake=new Faker();
        ob_start();
        echo "===============INFO===============\n";
        echo '\[NAME : *'.$fake->getName()."*]\n";
        echo '\[ADDRESS : *'.$fake->getAddress()."*]\n";
        echo '\[PHONE : *'.$fake->getPhone()."*]\n";
        echo "================CC================\n";
        echo '\[CARD NUMBER : *'.$fake->getCard()."*]\n";
        echo '\[CCV : *'.$fake->getCcv()."*]\n";
        echo '\[EXP-DATE : *'.$fake->getDate()."*]\n";
        $data=ob_get_clean();

        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'parse_mode'=>'markdown',
            'text'=>$data
            ]);
    }

    elseif(preg_match('/^\/quote/',strtolower($msg['text']))) {
        try{
            $text = mb_substr($msg['text'], 6,1024,'UTF-8');
            $watermark=$msg['from']['first_name'];
            (new QuoteMaker)
                ->setBackgroundFromUnsplash(['9e33a4b9f59c0dbb71800d9eae09377d70c0de27c815b73a508f5f35dd6494ed'],'Dark')
                ->quoteText($text)
                ->setQuoteFontSize(55)
                ->setQuoteFont(__DIR__.'/storage/Shadow Brush.ttf')
                ->watermarkText($watermark)
                ->setWatermarkFontSize(80)
                ->toFile(__DIR__.'/storage/result.jpg');

            $photo=new CURLFile(__DIR__.'/storage/result.jpg');
            $status=$telegram->bot('sendPhoto',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'photo'=>$photo
                ]);
        } catch(Exception $e){
            $status=$telegram->bot('sendMessage',
                ['chat_id'=>$msg['chat']['id'],
                'text'=> $e->getMessage(),
                'reply_to_message_id'=>$msg['message_id']
                ]);
        }
    }
    elseif(preg_match('/^\/upfile/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id'=> $msg['message_id'],
            'parse_mode'=>'html',
            'text'=> 'Uploading <b>'.substr($msg['text'], 8).'</b>'
            ]);
        $file=new CURLFile(__DIR__.'/storage/'.substr($msg['text'], 8));
        $status=$telegram->bot('sendDocument',
            ['chat_id' => $msg['chat']['id'],
            'document' => $file
            ]);
    }
    elseif(preg_match('/^\/ytmp3/',strtolower($msg['text']))) {
        if(filter_var(substr($msg['text'],7),FILTER_VALIDATE_URL,FILTER_FLAG_PATH_REQUIRED)) {

            $status=$telegram->bot('sendMessage',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'text'=>'Downloading the audio, please wait'
                ]);

            $dl->setDownloadPath(__DIR__.'/storage/'); 
            $video = $dl->download(substr($msg['text'], 7));
            $filename=$video->getFile()->getBasename();
            $audio=new CURLFile(__DIR__.'/storage/'.$filename);

            $status=$telegram->bot('sendMessage',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'text'=>'Uploading '.$filename
                ]);

            $status=$telegram->bot('sendAudio',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'audio'=>$audio
                ]);
        }
        else {
            $status=$telegram->bot('sendMessage',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'text'=>'Url is not valid'
                ]);
        }

    }

    elseif(preg_match('/^\/promote/', strtolower($msg['text'])) AND !empty($msg['reply_to_message'])) {
        $status=$telegram->bot('promoteChatMember',
            ['chat_id' => $msg['chat']['id'],
            'user_id' => $msg['reply_to_message']['from']['id'],
            'can_invite_users' => true,
            'can_restrict_members' => true,
            'can_pin_messages' => true,
            'can_delete_messages' => true,
            'can_promote_members' => false,
            'can_change_info' => true
            ]);
        if($status['ok']) {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=> $msg['message_id'],
                'text'=> $msg['reply_to_message']['from']['first_name'].' has been promoted!'
                ]);

            $status=$telegram->bot('sendDocument',
                ['chat_id' => $msg['chat']['id'],
                'document' => 'CAADAgADWEAAAuCjggciGwapGpz4dBYE'
                ]);
        }
        else {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=> $msg['message_id'],
                'text'=>'Can\'t promote '.$msg['reply_to_message']['from']['first_name']
                ]);

            $status=$telegram->bot('sendDocument',
                ['chat_id' => $msg['chat']['id'],
                'document' => 'CAADAgADWUAAAuCjggc35LUFXNY5gBYE'
                ]);
        }
    }

    elseif(preg_match('/^\/getfile/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
        $status=$telegram->bot('getFile',
            ['file_id' => $msg['reply_to_message']['document']['file_id']
            ]);
        if($status['ok']) {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=> $msg['message_id'],
                'text'=> 'Downloading '.$msg['reply_to_message']['document']['file_name'].' to local storage'
                ]);
            $file=file_get_contents('https://api.telegram.org/file/bot'.BOT_TOKEN.'/'.$res['result']['file_path']);
            $tulis=fopen('storage/'.$msg['reply_to_message']['document']['file_name'],'w+');
            fwrite($tulis, $file);
            fclose($tulis);
            unset($tulis);

            $status=$telegram->bot('sendDocument',
                ['chat_id' => $msg['chat']['id'],
                'document' => 'CAADAgADWEAAAuCjggciGwapGpz4dBYE'
                ]);
        }
        else {
            $status=$telegram->bot('sendMessage',
                ['chat_id'=>$msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'text'=>'Can\'t Donload the file'
                ]);
        }
    }

    elseif(preg_match('/^\/demote/', strtolower($msg['text'])) AND !empty($msg['reply_to_message'])) {
        $status=$telegram->bot('promoteChatMember',
            ['chat_id' => $msg['chat']['id'],
            'user_id' => $msg['reply_to_message']['from']['id'],
            'can_change_info'=> false,
            'can_post_messages' => false,
            'can_edit_messages' => false,
            'can_delete_messages' => false,
            'can_promote_members' => false,
            'can_invite_users' => false,
            'can_restrict_members' => false,
            'can_pin_messages' => false
            ]);
        if($status['ok']) {
            $telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=> $msg['message_id'],
                'text'=> $msg['reply_to_message']['from']['first_name'].' has been demoted!'
                ]);
            $status=$telegram->bot('sendDocument',
                ['chat_id' => $msg['chat']['id'],
                'document' => 'CAADAgADWEAAAuCjggciGwapGpz4dBYE'
                ]);
        }
        else {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=> $msg['message_id'],
                'text'=>'Failed to demote '.$msg['reply_to_message']['from']['first_name']
                ]);
            $status=$telegram->bot('sendDocument',
                ['chat_id' => $msg['chat']['id'],
                'document' => 'CAADAgADWUAAAuCjggc35LUFXNY5gBYE'
                ]);
        }
    }

    elseif(isset($msg['location'])) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id'=> $msg['message_id'],
            'parse_mode'=>'Markdown',
            'text'=>'Latitude : *'.$msg['location']['latitude']."*\n".
            'Longitude : *'.$msg['location']['longitude'].'*'
            ]);
    }

    elseif (preg_match('/^\/date/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id'=> $msg['message_id'],
            'text' => date("D M d  H:i:s Y")
            ]);
    }

    elseif(preg_match('/^\/paste/', strtolower($msg['text']))) {
        $status = $telegram->pastebin(substr($msg['text'],7), $msg['message_id']);   
        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=> $msg['message_id'],
            'text'=>$status
            ]);
    }

    elseif (preg_match('/^\/base64_decode/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.base64_decode(substr($msg['text'], 15)).'</pre>'
            ]);
    }

    elseif (preg_match('/^\/base64_encode/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.base64_encode(substr($msg['text'], 15)).'</pre>'
            ]);
    }

    elseif (preg_match('/^\/sha1/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.sha1(substr($msg['text'], 6)).'</pre>'
            ]);
    }

    elseif (preg_match('/^\/md5/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.md5(substr($msg['text'], 5)).'</pre>'
            ]);
    }

    elseif (preg_match('/^\/hex/', strtolower($msg['text']))) {
        $status= $telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.$telegram->hex(substr($msg['text'], 5)).'</pre>'
            ]);
    }

    elseif (preg_match('/^\/binhex/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id'=> $msg['message_id'],
            'parse_mode'=>'html',
            'text' => '<pre>'.$telegram->hex(gzdeflate(substr($msg['text'], 8))).'</pre>'
            ]);

    }

    elseif (preg_match("/^hi|^holla|^hallo|^hello/", strtolower($msg['text']))) {
        if (date('A') === 'PM') {
            $msgs = 'Good Night';
        }
        else if(date('A') === 'AM') {
            $msgs = 'Good Morning';
        }
        else {
            $msgs = 'I love u';
        }
        $status=$telegram->bot('SendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => "Hi, $msgs ^~^"
            ]);
    }

    elseif(preg_match(strtolower("/".BOTNAME."/"), strtolower($msg['text']))) {
        $status=$telegram->bot('SendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'Nani '.$msg['from']['first_name'].'?.'
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgADezoAAuCjggdU7iY6lzU_KgI',
            'chat_id' => $msg['chat']['id']
            ]);
    }

    elseif(preg_match('/^\/say /', strtolower($msg['text'])) && strlen($msg['text']) >4) {
        $status=$telegram->bot('SendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => substr($msg['text'], 5)
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgAESwAC4KOCB0k4ZMPSyM90Ag',
            'chat_id' => $msg['chat']['id']
            ]);
    }

    elseif(preg_match('/stah| ster|^gay|kontol|tolol|jembut|memek/', strtolower($msg['text']))) {
        $status=$telegram->bot('deleteMessage',
            ['chat_id' => $msg['chat']['id'],
            'message_id'=> $msg['message_id']
            ]);
        if(!$status['ok']) {
            $status=$telegram->bot('SendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id'=>$msg['message_id'],
                'text'=> 'Can\'t delete this chat'
                ]);
        }
    }

    elseif(preg_match('/^ohayo/', strtolower($msg['text']))) {
        if(date('A') == 'PM') {
            $msgs='Endasmu :voss';
        }
        else if(date('A') == 'AM') {
            $msgs='^~^';
        }
        else {
            $msgs=':v';
        }
        $status=$telegram->bot('SendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'Ohayo '.$msgs
            ]);
    }

    elseif (preg_match('/^\/debug/', strtolower($msg['text'])) && $msg['from']['username'] == USERNAME) {

        $r_var=json_encode(array_keys(get_defined_vars()));
        $end=round((microtime(true)-$start)*1000);
        $msgs=json_encode($msg);
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'message_id' => $msg['message_id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode'=>'markdown',
            'text' => "`{$msgs}`\n*defined vars:*\n`{$r_var}`\n*Elapsed time:*_{$end} ms_"
            ]);
    }

    elseif (preg_match("/".BOTNAME."/", strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'Nani ' . $msg['from']['first_name'] . ' Kun :)?'
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgADbDoAAuCjggcEHVJ3T4JFuwI',
            'chat_id' => $msg['chat']['id']
            ]);
    }

    elseif (preg_match('/daisuki/', strtolower($msg['text']))) {
        if (rand(0, 1) == 1) {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id' => $msg['message_id'],
                'text' => $msg['from']['first_name'] . ' No Bakka >///<'
                ]);

            $status=$telegram->bot('sendDocument',
                ['document' => 'CAADAgAD_0oAAuCjggfHbVTyHTzuIwI',
                'chat_id' => $msg['chat']['id']
                ]);
        }
        else {
            $status=$telegram->bot('sendMessage',
                ['chat_id' => $msg['chat']['id'],
                'reply_to_message_id' => $msg['message_id'],
                'text' => 'Yameroo ' . $msg['from']['first_name'] . ' Kun >:('
                ]);

            $status=$telegram->bot('sendDocument',
                ['document' => 'CAADAgADejoAAuCjggc_xgdO_9hkJQI',
                'chat_id' => $msg['chat']['id']
                ]);
        }
    }

    elseif (preg_match('/shindeiru|shine/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'NANI1!1!1!?'
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADBAADNgcAAt5A-AeXskprUzhaUgI',
            'chat_id' => $msg['chat']['id']
            ]);
    }

    elseif(preg_match('/^se~no|^seno|^se no/', strtolower($msg['text']))) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'text' => "Demo sonnanja dame\nMou sonnanja hora\nKokoro wa shinka suru yo motto motto"
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgADEksAAuCjggcQcjjCC0T6MRYE',
            'chat_id' => $msg['chat']['id']
            ]);
    }

    elseif (preg_match('/^get out|^\/leave|^\/exit/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'Ok ' . USERNAME . ' i will leave now :('
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgADVjoAAuCjggdSNL1tyPlbSAI',
            'chat_id' => $msg['chat']['id']
            ]);

        $status=$telegram->bot('leaveChat',
            ['chat_id' => $msg['chat']['id']
            ]);
    }

    elseif (preg_match('/^\/shutdown|^\/die|^\/halt/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'text' => 'Have a nice day '.USERNAME.' :)'
            ]);

        $status=$telegram->bot('sendDocument',
            ['document' => 'CAADAgADXToAAuCjggdWcwXYdRVtnwI',
            'chat_id' => $msg['chat']['id']
            ]);
        exit(0);
    }

    elseif (preg_match('/^!/', $msg['text']) AND $msg['from']['username'] == USERNAME) {
        exec('doalarm 60 '.substr($msg['text'], 1).' 2>&1', $ret);

        $len=sizeof($ret);
        $res='';
        for($x=0;$x<$len;$x++) {
            $res.=$ret[$x]."\n";
        }
        $status=$telegram->bot('sendMessage',
            ['chat_id' => $msg['chat']['id'],
            'reply_to_message_id' => $msg['message_id'],
            'parse_mode' => 'markdown',
            'text' => '`'.mb_substr($res, 0, 4096,'UTF-8').'`'
            ]);
        unset($len,$res,$ret,$x);
    }
    elseif(preg_match('/^\/eval/',$msg['text']) AND $msg['from']['username'] == USERNAME) {
        ob_start();
        eval(substr($msg['text'], 6));
        $text=ob_get_clean();
        $status=$telegram->bot('sendMessage',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'text'=>$text
            ]);
        unset($text);
    }
}
