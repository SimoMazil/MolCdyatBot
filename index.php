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

  print "Hello World !";

}

  $input = json_decode(file_get_contents('php://input'), true);
  // Get the Senders Page Scoped ID
  $sender = $input['entry'][0]['messaging'][0]['sender']['id'];
  // Get the message text sent
  $message = $input['entry'][0]['messaging'][0]['message']['text'];
  // Get the message payload sent
  $payload = $input['entry'][0]['messaging'][0]['postback']['payload'];
  // Get the message payload quick replies sent
  $quickPayload = $input['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];

  if(!empty($quickPayload)){
    if($quickPayload == 'keepSearching'){
      ask_user($sender, "Tape your movie title :D", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == 'reset'){
      update_user_interaction($sender, "default");
      send_qst($sender, "", $PAGE_ACCESS_TOKEN);
    }
  }else if(!empty($message)){

    if(search_user_interaction($sender)){
      $interaction = search_user_interaction($sender);
      if($interaction == "default"){
          send_qst($sender, "", $PAGE_ACCESS_TOKEN);
          if(search_user_interaction($sender)){
            update_user_interaction($sender, "default");
          }else{
            add_user_interaction($sender, "default");
          }
      }else if($interaction == "movie"){
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $title = str_replace(" ","_",$message);
        $response = file_get_contents("https://yts.ag/api/v2/list_movies.json?query_term=".$title."&limit=10&sort_by=year&order_by=desc", false, stream_context_create($arrContextOptions));
        $data = json_decode($response, true);
        if($data["status"] == "ok"){
          if($data["data"]["movie_count"] == "0"){
            ask_user($sender, "Movie not found, try another movie", $PAGE_ACCESS_TOKEN);
          }else{
            $movies = $data["data"]["movies"];
            $carousel = fetch_movies_to_carousel($movies);
            send_movie_carousel($sender, $carousel, $PAGE_ACCESS_TOKEN);
            send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
          }
        }else{
          ask_user($sender, "status not ok!", $PAGE_ACCESS_TOKEN);
        }

      }
    }

  }else if(!empty($payload)){

    if($payload == 'title'){

      ask_user($sender, "Write your movie title", $PAGE_ACCESS_TOKEN);

      if(search_user_interaction($sender)){
        update_user_interaction($sender, "movie");
      }

    }else if($payload == 'genre'){

      ask_user($sender, "Choose your favourite genre", $PAGE_ACCESS_TOKEN);
      quick_replies($sender, "", $PAGE_ACCESS_TOKEN);

      if(search_user_interaction($sender)){
        update_user_interaction($sender, "genre");
      }

    }else if($payload == 'actor'){

      ask_user($sender, "Write your actor name", $PAGE_ACCESS_TOKEN);

      if(search_user_interaction($sender)){
        update_user_interaction($sender, "actor");
      }

    }else if(split('_',$payload)[0] == "movieInfos"){
      $movieId = split('_',$payload)[1];
      $arrContextOptions=array(
          "ssl"=>array(
              "verify_peer"=>false,
              "verify_peer_name"=>false,
          ),
      );
      $response = file_get_contents("https://yts.ag/api/v2/movie_details.json?movie_id=".$movieId, false, stream_context_create($arrContextOptions));
      $data = json_decode($response, true);
      if($data["status"] == "ok"){
        if($data["data"]["movie_count"] == "0"){
          ask_user($sender, "Movie not found, try another movie", $PAGE_ACCESS_TOKEN);
        }else{
          $movie = $data["data"]["movie"];
          send_movie_infos($sender, $movie, $PAGE_ACCESS_TOKEN);
          ask_user($sender, "Description : ".$movie["description_full"], $PAGE_ACCESS_TOKEN);
          send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
        }
      }else{
        ask_user($sender, "status not ok!", $PAGE_ACCESS_TOKEN);
      }
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
  print_r($result);
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
          "text":"Hi, How do you like to find your movie :D",
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


function get_movie($sender, $message){
	// Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
    "attachment":{
      "type":"template",
      "payload":{
        "template_type":"generic",
        "elements":'.$message.'
      }
    }
  }

	}';
	return $jsonData;
}

function get_movie_infos($sender, $message){
  // Build the json payload data
  $jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
      "text":"Title : '.$message["title_long"].'\nGenres : '.$message["genres"][0].' '.$message["genres"][1].' '.$message["genres"][2].' '.$message["genres"][3].'\nRating : '.$message["rating"].'\nRuntime : '.$message["runtime"].'"
    }

  }';
  return $jsonData;
}

function ask_quicke_replies($sender, $message){
  // Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
    "text":":p",
    "quick_replies":[
      {
        "content_type":"text",
        "title":"Keep searching",
        "payload":"keepSearching"
      },
      {
        "content_type":"text",
        "title":"Reset",
        "payload":"reset"
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

function send_movie_carousel($sender, $message, $access_token){
	$jsonData = get_movie($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function send_movie_infos($sender, $message, $access_token){
	$jsonData = get_movie_infos($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function send_ask_quick_replies($sender, $message, $access_token){
	$jsonData = ask_quicke_replies($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function search_user_interaction($userId){
  $inp = file_get_contents('userInteraction.json');
  $tempArray = json_decode($inp, true);
  foreach ($tempArray as $key => $val) {
    if($val["userId"] == $userId){
      return $val["interaction"];
    }
  }
  return false;
}

function add_user_interaction($userId, $interaction){
  $inp = file_get_contents('userInteraction.json');
  $tempArray = json_decode($inp, true);
  array_push($tempArray, array('userId'=> $userId, 'interaction'=> $interaction));
  $jsonData = json_encode($tempArray);
  file_put_contents('userInteraction.json', $jsonData);
}

function update_user_interaction($userId, $interaction){
  $inp = file_get_contents('userInteraction.json');
  $tempArray = json_decode($inp, true);
  foreach ($tempArray as $key => $val) {
    if($val["userId"] == $userId){
      $tempArray[$key]["interaction"] = $interaction;
      break;
    }
  }
  $jsonData = json_encode($tempArray);
  file_put_contents('userInteraction.json', $jsonData);
}

function fetch_movies_to_carousel($movies){
  $data = array();

  foreach($movies as $val){
    array_push($data, array("title"=> substr($val["title_long"],0,80), "item_url"=> "", "image_url"=> $val["medium_cover_image"], "subtitle"=> substr($val["summary"],0,80),
    "buttons"=> array(array("type"=> "web_url", "url"=> "https://www.youtube.com/watch?v=".$val["yt_trailer_code"], "title"=> "View Trailler"),array("type"=> "postback", "title"=> "More Information", "payload"=> "movieInfos_".$val["id"]))));
  }
  return json_encode($data);

}
