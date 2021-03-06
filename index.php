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
    $interaction = search_user_interaction($sender);
    if($quickPayload == 'keepSearching'){
      if($interaction == "movie"){
          ask_user($sender, "Tape your movie title B-)", $PAGE_ACCESS_TOKEN);
      }else if($interaction == "genre"){
        quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
      }else if($interaction == "discuss"){
        ask_user($sender, "Tape another genre B-)", $PAGE_ACCESS_TOKEN);
      }

    }else if($quickPayload == 'reset'){
      update_user_interaction($sender, "default");
      send_qst($sender, "", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "action"){
      search_movie_by_genre($sender, "action", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "drama"){
      search_movie_by_genre($sender, "drama", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "fiction"){
      search_movie_by_genre($sender, "sci-fi", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "animation"){
      search_movie_by_genre($sender, "animation", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "discussYES"){
      send_rate_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "discussNO"){
      ask_user($sender, "Ok then i advicee you to check it :)", $PAGE_ACCESS_TOKEN);
      send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
    }else if($quickPayload == "rateYES"){
      ask_user($sender, "Entre your rating between 0 to 9", $PAGE_ACCESS_TOKEN);
      update_user_interaction($sender, "rateMovie");
    }else if($quickPayload == "rateNO"){
      ask_user($sender, "Ok then as you like :p", $PAGE_ACCESS_TOKEN);
      send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
    }

  }else if(!empty($message)){
    if(strtolower($message) == "bye"){
      send_gif($sender, "https://media.giphy.com/media/11eDYhyA5BgL0k/giphy.gif", $PAGE_ACCESS_TOKEN);
    }else if(strtolower($message) == "reset"){
      update_user_interaction($sender, "default");
      send_qst($sender, "", $PAGE_ACCESS_TOKEN);
    }else if(search_user_interaction($sender)){
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

      }else if($interaction == "discuss"){
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        $response = file_get_contents("https://yts.ag/api/v2/list_movies.json?genre=".$message."&limit=1&minimum_rating=7&order_by=desc", false, stream_context_create($arrContextOptions));
        $data = json_decode($response, true);
        if($data["status"] == "ok"){
          if($data["data"]["movie_count"] == "0"){
            ask_user($sender, "sorry, i can't find this genre of movies in my DB! can you try another type ?", $PAGE_ACCESS_TOKEN);
          }else{
            $movies = $data["data"]["movies"];
            $carousel = fetch_movies_to_carousel($movies);
            ask_user($sender, "According this is one of the best movie in this genre so far B-)", $PAGE_ACCESS_TOKEN);
            send_movie_carousel($sender, $carousel, $PAGE_ACCESS_TOKEN);
            send_discuss_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
          }
        }else{
          ask_user($sender, "status not ok!", $PAGE_ACCESS_TOKEN);
        }
      }else if($interaction == "rateMovie"){
        if($message >= 0 && $message <= 9){
          ask_user($sender, "your rating has been made", $PAGE_ACCESS_TOKEN);
          update_user_interaction($sender, "discuss");
          send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
        }else{
          ask_user($sender, "plz enter number between 0 adn 9", $PAGE_ACCESS_TOKEN);
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

      quick_replies($sender, "", $PAGE_ACCESS_TOKEN);

      if(search_user_interaction($sender)){
        update_user_interaction($sender, "genre");
      }

    }else if($payload == "getStarted"){
      send_qst($sender, "", $PAGE_ACCESS_TOKEN);
      if(search_user_interaction($sender)){
        update_user_interaction($sender, "default");
      }else{
        add_user_interaction($sender, "default");
      }
    }else if($payload == "discuss"){

      ask_user($sender, "What's your favorite genre ?", $PAGE_ACCESS_TOKEN);

      if(search_user_interaction($sender)){
        update_user_interaction($sender, "discuss");
      }

    }else if(split('_',$payload)[0] == "movieInfos"){
      $interaction = search_user_interaction($sender);
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
          if($interaction != "discuss"){
              send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
          }
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
          "text":"Hi, How do you like to find your movie B-)",
          "buttons":[
            {
              "type":"postback",
              "title":"by Title",
              "payload":"title"
            },
            {
              "type":"postback",
              "title":"by Genre",
              "payload":"genre"
            },
            {
              "type":"postback",
              "title":"by discussion",
              "payload":"discuss"
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
    "text":"Pick a genre:",
    "quick_replies":[
      {
        "content_type":"text",
        "title":"Action",
        "payload":"action"
      },
      {
        "content_type":"text",
        "title":"Sci-fiction",
        "payload":"fiction"
      },
      {
        "content_type":"text",
        "title":"Drama",
        "payload":"drama"
      },
      {
        "content_type":"text",
        "title":"Animation",
        "payload":"animation"
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
      "text":"Title : '.$message["title_long"].'\nGenres : '.$message["genres"][0].' '.$message["genres"][1].' '.$message["genres"][2].' '.$message["genres"][3].'\nRating : '.$message["rating"].'\nRuntime : '.$message["runtime"].' min"
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
    "text":"B-)",
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

function discuss_quick_replies($sender, $message){
  // Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
    "text":"have you seen this movie ?",
    "quick_replies":[
      {
        "content_type":"text",
        "title":"Yes",
        "payload":"discussYES"
      },
      {
        "content_type":"text",
        "title":"No",
        "payload":"discussNO"
      }
    ]
  }

	}';
	return $jsonData;
}

function rate_quick_replies($sender, $message){
  // Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
    "text":"Oh! cool, do you wanna rate this movie ?",
    "quick_replies":[
      {
        "content_type":"text",
        "title":"Yes",
        "payload":"rateYES"
      },
      {
        "content_type":"text",
        "title":"No",
        "payload":"rateNO"
      }
    ]
  }

	}';
	return $jsonData;
}

function bye($sender, $message){
	// Build the json payload data
	$jsonData = '{
    "recipient":{
      "id":"'.$sender.'"
    },
    "message":{
      "attachment":{
        "type":"image",
        "payload":{
          "url":"'.$message.'"
        }
      }
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

function send_discuss_quick_replies($sender, $message, $access_token){
	$jsonData = discuss_quick_replies($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function send_rate_quick_replies($sender, $message, $access_token){
	$jsonData = rate_quick_replies($sender, $message);
	$result = send_message($access_token, $jsonData);
	return $result;
}

function send_gif($sender, $message, $access_token){
	$jsonData = bye($sender, $message);
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
    "buttons"=> array(array("type"=> "web_url", "url"=> "https://www.youtube.com/watch?v=".$val["yt_trailer_code"], "title"=> "Watch Trailler"),array("type"=> "postback", "title"=> "More Information", "payload"=> "movieInfos_".$val["id"]))));
  }
  return json_encode($data);

}

function search_movie_by_genre($sender, $genre, $PAGE_ACCESS_TOKEN){
  $arrContextOptions=array(
      "ssl"=>array(
          "verify_peer"=>false,
          "verify_peer_name"=>false,
      ),
  );
  $response = file_get_contents("https://yts.ag/api/v2/list_movies.json?genre=".$genre."&limit=10&minimum_rating=8&order_by=desc", false, stream_context_create($arrContextOptions));
  $data = json_decode($response, true);
  if($data["status"] == "ok"){
    if($data["data"]["movie_count"] == "0"){
      ask_user($sender, "genre not found, try another genre", $PAGE_ACCESS_TOKEN);
    }else{
      $movies = $data["data"]["movies"];
      $carousel = fetch_movies_to_carousel($movies);
      ask_user($sender, "These are the ten best rating movie in this genre :D", $PAGE_ACCESS_TOKEN);
      send_movie_carousel($sender, $carousel, $PAGE_ACCESS_TOKEN);
      send_ask_quick_replies($sender, "", $PAGE_ACCESS_TOKEN);
    }
  }else{
    ask_user($sender, "status not ok!", $PAGE_ACCESS_TOKEN);
  }
}
