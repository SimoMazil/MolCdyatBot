<?php

// Report all errors except E_NOTICE
// This is the default value set in php.ini
error_reporting(E_ALL & ~E_NOTICE);

$VERIFY_TOKEN = '124SQFSQJFDSQ';
$PAGE_ACCESS_TOKEN = 'EAAVrmqhiCOEBAAc52E5tTP0Mi1tqaDPelSNxP34hZBXmAByhhZBxhpslBEKYhFlFuLr3UrwpFokMe51lWjiySjU8E16aA3tIPmIaPt7IwQjmjQBHExTtCDOIZBIMTtaH1sSZBI9on2OTXZCZCwY3ZCH6tlOw06y4EtGqIkIYzRpaQZDZD';

$challenge = $_REQUEST['hub_challenge'];
$verify_token = $_REQUEST['hub_verify_token'];
if ($verify_token === $VERIFY_TOKEN) {
  	//If the Verify token matches, return the challenge.
  	echo $challenge;
}else {
	print "Hello World!";
}

  $input = json_decode(file_get_contents('php://input'), true);
  // Get the Senders Page Scoped ID
  $sender = $input['entry'][0]['messaging'][0]['sender']['id'];
  // Get the message text sent
  $message = $input['entry'][0]['messaging'][0]['message']['text'];
  // Get the message payload sent
  $payload = $input['entry'][0]['messaging'][0]['postback']['payload'];

  if(!empty($message)){
  	if(strtolower($message) == 'hi'){
      send_qst($sender, "", $PAGE_ACCESS_TOKEN);
  	}else{
      send_replay($sender, "plz say Hi ! or i can't offer you my services", $PAGE_ACCESS_TOKEN);
    }
  }


function send_message($access_token, $payload) {
	// Send/Recieve API
	$url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;
	// Initiate the curl
	$ch = curl_init($url);
	// Set the curl to POST
	curl_setopt($ch, CURLOPT_POST, 1);
	// Add the json payload
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	// Set the header type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	// SSL Settings
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Send the request
	$result  = curl_exec($ch);
	//Return the result
	return $result;
}

function first_qst_to_user($sender, $name){
	// Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
      "attachment":{
        "type":"template",
        "payload":{
          "template_type":"button",
          "text":"Great Mr '.$name.'",
          "buttons":[
            {
              "type":"postback",
              "title":"Search movie",
              "payload":"searchMovie"
            }
          ]
        }
      }
    }

	}';
	return $jsonData;
}

function replay_ask_to_say_hi($sender, $message){
	// Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
      "text": "'.$message.'"
    }

	}';
	return $jsonData;
}

function send_qst($sender, $name, $access_token){
	$jsonData = first_qst_to_user($sender, $name);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function send_replay($sender, $message, $access_token){
	$jsonData = replay_ask_to_say_hi($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}
