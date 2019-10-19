<?php
require 'antibot.php';
require 'vendor/autoload.php';
use seregazhuk\PinterestBot\Factories\PinterestBot;
$pinterest = PinterestBot::create();
$pinterest->auth->login('', ''); // username and password pinterest

$api_dev_key = ""; // pastebin api dev key
$api_paste_private = "0";
$api_paste_expire_date ='10M';
$api_paste_format ='text';
$api_user_key = '';
$api_paste_name = urlencode($api_paste_name);
$api_paste_code = urlencode($api_paste_code);

class Telegram {
	public function __construct()
	{
		set_time_limit(0);
		ignore_user_abort(0);
		ini_set('max_execution_time', 0); //exec time
		ini_set('memory_limit', '999999999M'); //memmory limit
		date_default_timezone_set('Asia/Jakarta'); // timezone

		/*if(function_exists('pcntl_signal')) {
			declare(ticks = 1);
        		function signal_handler($signal) {
                		switch($signal) {
                			case SIGTERM:
                				exit("SIGTERM\n");
                				break;
                			case SIGKILL:
                				exit("SIGKILL\n");
                				break;
                			case SIGINT:
                				exit("program interrupted\n");
                				break;
					}
			}
			pcntl_signal(SIGTERM, "signal_handler");
        		pcntl_signal(SIGINT, "signal_handler");
		}*/
		define('BOT_TOKEN', ''); // Token
		define('USERNAME', ''); // author Username
		define('BOTNAME','menhera'); // alias ot Name
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
		unset($url,$fields,$ch);
	}
	public function hex($str) {
		$ec = bin2hex($str);
		$ec = chunk_split($ec, 2, '\x');
		$ec = '\x' . substr($ec, 0, strlen($ec) - 2);
		return $ec;
		unset($str,$ec);
	}
	public function bot($method, $datas = []) {
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot'.BOT_TOKEN.'/'.$method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$res = curl_exec($ch);
		curl_close($ch);
		return json_decode($res, 1);
		unset($ch,$method,$datas,$res);
	}
	public function getupdates($up_id) {
		$get=$this->bot('getupdates', ['offset' => $up_id]);
		return end($get['result']);
		unset($get,$up_id);
	}

}

$telegram=new Telegram();
while (true) {
	$last_up=$telegram->getupdates(@$last_up['update_id'] + 1);
	$msg = $last_up['message'];

    if(preg_match('/^pinterest/',strtolower($msg['text']))) {
        $pins = $pinterest->pins->search(substr($msg['text'],9))->toArray();
        //print_r($pins[1]);
        $pages=end(explode(' ',$msg['text']));
        $telegram->bot('sendDocument',
            ['chat_id'=>$msg['chat']['id'],
            'reply_to_message_id'=>$msg['message_id'],
            'document'=>$pins[$pages]['images']['orig']['url'],
            'caption'=>'pages : '.$pages."\n".'total : '.sizeof($pins)."\n".$pins[$pages]['description']
            ]);
        unset($pins, $pages);
   }

    elseif(preg_match('/^promote/', strtolower($msg['text']))) {
		$telegram->bot('promoteChatMember',
			['chat_id' => $msg['chat']['id'],
			'user_id' => $msg['reply_to_message']['from']['id'],
			'can_invite_users' => true,
			'can_restrict_members' => true,
			'can_pin_messages' => true,
			'can_delete_messages' => true,
			'can_promote_members' => false,
			'can_change_info' => true
		]);

		$telegram->bot('sendMessage',
                        ['chat_id' => $msg['chat']['id'],
                        'reply_to_message_id'=> $msg['message_id'],
                        'text'=> $msg['reply_to_message']['from']['first_name'].' has been promoted!'
		]);

		$telegram->bot('sendDocument',
			['chat_id' => $msg['chat']['id'],
			'document' => 'CAADAgADWEAAAuCjggciGwapGpz4dBYE'
		]);
	}

    elseif(preg_match('/^demote/', strtolower($msg['text']))) {
                $telegram->bot('promoteChatMember',
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

                $telegram->bot('sendMessage',
                        ['chat_id' => $msg['chat']['id'],
                        'reply_to_message_id'=> $msg['message_id'],
                        'text'=> $msg['reply_to_message']['from']['first_name'].' has been demoted!'
                ]);

		$telegram->bot('sendDocument',
                        ['chat_id' => $msg['chat']['id'],
                        'document' => 'CAADAgADWUAAAuCjggc35LUFXNY5gBYE'
                ]);
        }

	if(isset($msg['location'])) {
		$telegram->bot('sendMessage',
			['chat_id' => $msg['chat']['id'],
			'reply_to_message_id'=> $msg['message_id'],
			'parse_mode'=>'Markdown',
			'text'=>'Latitude : *'.$msg['location']['latitude']."*\n".
            'Longitude : *'.$msg['location']['longitude'].'*'
            ]);
	}

	elseif (preg_match('/^date/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
			['chat_id' => $msg['chat']['id'],
			'reply_to_message_id'=> $msg['message_id'],
			'text' => date("D-m-Y")
		]);
	}

    elseif(preg_match('/^paste/', strtolower($msg['text']))) {
		$api_paste_code=substr($msg['text'], 6);
		$api_paste_name=$msg['message_id'];
		$ch = curl_init('http://pastebin.com/api/api_post.php');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_option=paste&api_user_key='.$api_user_key.'&api_paste_private='.$api_paste_private.'&api_paste_name='.$api_paste_name.'&api_paste_expire_date='.$api_paste_expire_date.'&api_paste_format='.$api_paste_format.'&api_dev_key='.$api_dev_key.'&api_paste_code='.$api_paste_code.'');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
                $response = curl_exec($ch);

                $telegram->bot('sendMessage',
			['chat_id'=>$msg['chat']['id'],
			'reply_to_message_id'=> $msg['message_id'],
			'text'=>$response
			]);
	}

	elseif (preg_match('/^base64_decode/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => base64_decode(substr($msg['text'], 14))
		]);
	}

	elseif (preg_match('/^base64_encode/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => base64_encode(substr($msg['text'], 14))
		]);
	}

	elseif (preg_match('/^sha1/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => sha1(substr($msg['text'], 5))
		]);
	}

	elseif (preg_match('/^md5/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => md5(substr($msg['text'], 4))
		]);
	}

	elseif (preg_match('/^hex/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => $telegram->hex(substr($msg['text'], 4))
		]);
	}

	elseif (preg_match('/^binhex/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id'=> $msg['message_id'],
		     'text' => $telegram->hex(gzdeflate(substr($msg['text'], 7)))
		]);

	}

	elseif (preg_match("/^hi|^holla|^hallo|^hello/", strtolower($msg['text']))) {
		if (date('A') == 'PM') {
			$msgs = 'Good Night';
		}
		else if(date('A') == 'AM') {
			$msgs = 'Good Morning';
		}
		else {
			$msgs = 'I love u';
		}
		$telegram->bot('SendMessage',
			['chat_id' => $msg['chat']['id'],
			'reply_to_message_id' => $msg['message_id'],
			'text' => "Hi, $msgs ^~^"
		]);
		unset($msgs);
	}

	elseif(preg_match(strtolower("/".BOTNAME."/"), strtolower($msg['text']))) {
		$telegram->bot('SendMessage',
		['chat_id' => $msg['chat']['id'],
			'reply_to_message_id' => $msg['message_id'],
			'text' => 'Nani '.$msg['from']['first_name'].'?.'
		]);

		$telegram->bot('sendDocument',
			['document' => 'CAADAgADezoAAuCjggdU7iY6lzU_KgI',
  			'chat_id' => $msg['chat']['id']
 		]);
	}

	elseif(preg_match('/^say /', strtolower($msg['text'])) && strlen($msg['text']) >4) {
		$telegram->bot('SendMessage',
	                ['chat_id' => $msg['chat']['id'],
                	'reply_to_message_id' => $msg['message_id'],
                	'text' => substr($msg['text'], 4)
                ]);

                $telegram->bot('sendDocument',
                	['document' => 'CAADAgAESwAC4KOCB0k4ZMPSyM90Ag',
                	'chat_id' => $msg['chat']['id']
                ]);
	}

        elseif(preg_match('/loli/', strtolower($msg['text']))) {
                $telegram->bot('SendMessage',
                        ['chat_id' => $msg['chat']['id'],
                        'text'=> 'Moshi moshi, keisatsu desuka?'
                ]);

                $telegram->bot('sendDocument',
                        ['document' => 'CAADAgADozoAAuCjggdDbc9CcJkLOAI',
                        'chat_id' => $msg['chat']['id']
                ]);
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
		$telegram->bot('SendMessage',
			['chat_id' => $msg['chat']['id'],
			 'reply_to_message_id' => $msg['message_id'],
			 'text' => 'Ohayo '.$msgs
		]);
		unset($msgs);
	}

	elseif (preg_match('/^debug/', strtolower($msg['text'])) && $msg['from']['username'] == USERNAME) {
		ob_start();
		echo json_encode($msg, 1);
		$msgs = ob_get_clean();
		$telegram->bot('sendMessage',
			['chat_id' => $msg['chat']['id'],
			'message_id' => $msg['message_id'],
			'reply_to_message_id' => $msg['message_id'],
			'parse_mode' => 'html',
			'text' => '<pre>'.$msgs.'</pre>'
		]);
		unset($msgs);
	}

	elseif (preg_match("/".BOTNAME."/", strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => 'Nani ' . $msg['from']['first_name'] . ' Kun :)?'
		]);

		$telegram->bot('sendDocument',
		    ['document' => 'CAADAgADbDoAAuCjggcEHVJ3T4JFuwI',
		     'chat_id' => $msg['chat']['id']
		]);
	}

	elseif (preg_match('/daisuki/', strtolower($msg['text']))) {
		if (rand(0, 1) == 1) {
			$telegram->bot('sendMessage',
			    ['chat_id' => $msg['chat']['id'],
			     'reply_to_message_id' => $msg['message_id'],
			     'text' => $msg['from']['first_name'] . ' No Bakka >///<'
			    ]);

			$telegram->bot('sendDocument',
			    ['document' => 'CAADAgAD_0oAAuCjggfHbVTyHTzuIwI',
			     'chat_id' => $msg['chat']['id']
			    ]);
		}
		else {
			$telegram->bot('sendMessage',
			    ['chat_id' => $msg['chat']['id'],
			     'reply_to_message_id' => $msg['message_id'],
			     'text' => 'Yameroo ' . $msg['from']['first_name'] . ' Kun >:('
			    ]);

			$telegram->bot('sendDocument',
			    ['document' => 'CAADAgADejoAAuCjggc_xgdO_9hkJQI',
			     'chat_id' => $msg['chat']['id']
			    ]);
		}
	}

	elseif (preg_match('/shindeiru|shine/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => 'NANI1!1!1!?'
		    ]);

		$telegram->bot('sendDocument',
		    ['document' => 'CAADBAADNgcAAt5A-AeXskprUzhaUgI',
		     'chat_id' => $msg['chat']['id']
		    ]);
	}

	elseif(preg_match('/^se~no|^seno|^se no/', strtolower($msg['text']))) {
		$telegram->bot('sendMessage',
			['chat_id' => $msg['chat']['id'],
			'text' => "Demo sonnanja dame\nMou sonnanja hora\nKokoro wa shinka suru yo motto motto"
			]);

		$telegram->bot('sendDocument',
			['document' => 'CAADAgADEksAAuCjggcQcjjCC0T6MRYE',
			'chat_id' => $msg['chat']['id']
			]);
	}

	elseif (preg_match('/^get out|^leave|^exit/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => 'Ok ' . USERNAME . ' i will leave now :('
		    ]);

		$telegram->bot('sendDocument',
		    ['document' => 'CAADAgADVjoAAuCjggdSNL1tyPlbSAI',
		     'chat_id' => $msg['chat']['id']
		    ]);

		$telegram->bot('leaveChat',
		    ['chat_id' => $msg['chat']['id']
		    ]);
	}

	elseif (preg_match('/^shutdown|^die|^halt/', strtolower($msg['text'])) AND $msg['from']['username'] == USERNAME) {
		$telegram->bot('sendMessage',
		    ['chat_id' => $msg['chat']['id'],
		     'reply_to_message_id' => $msg['message_id'],
		     'text' => 'Have a nice day '.USERNAME.' :)'
		    ]);

		$telegram->bot('sendDocument',
		    ['document' => 'CAADAgADXToAAuCjggdWcwXYdRVtnwI',
		     'chat_id' => $msg['chat']['id']
		    ]);
		exit(0);
	}

	elseif (preg_match('/^!/', $msg['text']) AND $msg['from']['username'] == USERNAME) {
		exec('doalarm 30 '.substr($msg['text'], 1).' 2>&1', $ret);

        $len=sizeof($ret);
        for($x=0;$x<=$len;$x++) {
            $res.=$ret[$x]."\n";
        }
		$telegram->bot('sendMessage',
                    ['chat_id' => $msg['chat']['id'],
                     'reply_to_message_id' => $msg['message_id'],
                     'parse_mode' => 'html',
                     'text' => '<pre>'.substr($res, 0, 4096).'</pre>'
                    ]);
        unset($res, $ret, $len, $x);
	}

	if(!(empty($msg['text']))) {
	//	print_r($msg);
		ob_start();

		echo 'Chat Title : '.$msg['chat']['title']."\n";
		echo 'Chat ID : '.$msg['chat']['id']."\n";
		echo 'Name : '.$msg['from']['first_name']."\n";
        echo 'Username: '.$msg['from']['username']."\n";
        echo 'Text : '.$msg['text']."\n\n";

		$data=ob_get_clean();
		echo $data;

		$tulis=fopen('storage/chat_logs.txt','a');
		fwrite($tulis, $data);
		fclose($tulis);
	}
}
