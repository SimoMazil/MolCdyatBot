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
  	send_qst($sender, "", $PAGE_ACCESS_TOKEN);
  }else if(!empty($payload)){
    if($payload == 'title'){
      ask_user($sender, "Write your movie title", $PAGE_ACCESS_TOKEN);
    }else if($payload == 'genre'){
      ask_user($sender, "Choose your favourite genre", $PAGE_ACCESS_TOKEN);
      quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
    }else if($payload == 'actor'){
      ask_user($sender, "Write your actor name", $PAGE_ACCESS_TOKEN);
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
          "text":"Hi i\'m MolCdyat Bot :D How are you ? Hope you are good, How do you like to find your movie by :",
          "buttons":[
            {
              "type":"postback",
              "title":"Movie Title",
              "payload":"title"
            },
            {
              "type":"postback",
              "title":"Movie Genre",
              "payload":"genre"
            },
            {
              "type":"postback",
              "title":"Actor Name",
              "payload":"actor"
            }
          ]
        }
      }
    }

	}';
	return $jsonData;
}

function ask_user_to_write($sender, $message){
	// Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
      "text":"'.$message.'"
    }

	}';
	return $jsonData;
}

function quick_replies_genre($sender, $message){
  // Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
    "text":"Pick a color:",
    "quick_replies":[
      {
        "content_type":"text",
        "title":"Red",
        "payload":"DEVELOPER_DEFINED_PAYLOAD_FOR_PICKING_RED"
      },
      {
        "content_type":"text",
        "title":"Green",
        "payload":"DEVELOPER_DEFINED_PAYLOAD_FOR_PICKING_GREEN"
      }
    ]
  }

	}';
	return $jsonData;
}

function send_qst($sender, $name, $access_token){
	$jsonData = first_qst_to_user($sender, $name);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function ask_user($sender, $message, $access_token){
	$jsonData = ask_user_to_write($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function quick_replies($sender, $message, $access_token){
	$jsonData = quick_replies_genre($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}
