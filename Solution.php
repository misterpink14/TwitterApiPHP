<html>
 <head>
  <title></title>
 </head>
 <body>

	<?php 

    # LINKS I FOUND USEFUL TO CONNECT TO TWITTER'S home-search
		# used the following tools from twitter
		# https://dev.twitter.com/rest/tools/console
		# https://dev.twitter.com/rest/public/search
		# https://dev.twitter.com/rest/reference/get/statuses/user_timeline
		# pigie backed, and modified, code from
		# http://stackoverflow.com/questions/12916539/simplest-php-example-for-retrieving-user-timeline-with-twitter-api-version-1-1
		function add_quotes($str) 
		{ 
			return '"'.$str.'"'; 
		}

		$search = array('flexsim', 'byu');
		foreach ($search as $value) 
		{

      // You must create a dev.twitter.com account and then a twitter app
      // You can click Manage Your Apps at the bottom of any page in the dev site to do this
      // Select the app you created and navigate to Keys and Access Tokens
      // Generate them if needed, insert below

			$token = '';  // Insert Access Token
			$token_secret = ''; // Insert Access Token Secret
			$consumer_key = ''; // Consumer Key (API Key)
			$consumer_secret = '';  // Consumer Secret (API Secret)

			$host = 'api.twitter.com';
			$method = 'GET';
			$path = '/1.1/search/tweets.json'; // api call path
			// I used this path since I was trying to access the twitter.com/home-search equivallent
			// https://dev.twitter.com/rest/public is where all the urls are located
			
			$oauth = array(
			    'oauth_consumer_key' => $consumer_key,
			    'oauth_token' => $token,
			    'oauth_nonce' => (string)mt_rand(),
			    'oauth_timestamp' => time(),
			    'oauth_signature_method' => 'HMAC-SHA1',
			    'oauth_version' => '1.0'
			);
			
			// The following parameters are for the http call
			// parameter => value
			$query = array( // query parameters
			    'q' => $value,
			    'count' => '1'
			);

			$oauth = array_map("rawurlencode", $oauth); // must be encoded before sorting
			$query = array_map("rawurlencode", $query);

			$arr = array_merge($oauth, $query); // combine the values THEN sort

			asort($arr); // secondary sort (value)
			ksort($arr); // primary sort (key)

			// http_build_query automatically encodes, but our parameters
			// are already encoded, and must be by this point, so we undo
			// the encoding step
			$querystring = urldecode(http_build_query($arr, '', '&'));
			$url = "https://$host$path";

			// mash everything together for the text to hash
			$base_string = $method."&".rawurlencode($url)."&".rawurlencode($querystring);

			// same with the key
			$key = rawurlencode($consumer_secret)."&".rawurlencode($token_secret);

			// generate the hash
			$signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));

			// this time we're using a normal GET query, and we're only encoding the query params
			// (without the oauth params)

			$url .= "?".http_build_query($query);
			$url=str_replace("&amp;","&",$url); //Patch by @Frewuill, stack overload

			$oauth['oauth_signature'] = $signature;
			ksort($oauth);

			// also not necessary, but twitter's demo does this too
			
			$oauth = array_map("add_quotes", $oauth);

			// this is the full value of the Authorization line
			$auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));

			// if you're doing post, you need to skip the GET building above
			// and instead supply query parameters to CURLOPT_POSTFIELDS
			$options = array( CURLOPT_HTTPHEADER => array("Authorization: $auth"),
			                  CURLOPT_HEADER => false,
			                  CURLOPT_URL => $url,
			                  CURLOPT_RETURNTRANSFER => true,
			                  CURLOPT_SSL_VERIFYPEER => false);

			$feed = curl_init();
			curl_setopt_array($feed, $options);
			$json = curl_exec($feed);
			curl_close($feed);

			$twitter_data = json_decode($json);

			$d = $twitter_data->statuses[0]->created_at;

			echo "The last <strong>#$value</strong> tweet was posted: <br />";
			echo (print_r($d, true)) . " (Standard Time)<br />";


			$dd = strtotime($d);
			$dd = date("r", $dd);
			echo $dd . " (Local Time)<br/>";
			
			?>
			<p>
		   	HTTP Request: GET<br />
	   		URL: <?php echo $url; ?><br />
	   		HTTP Status Code: 200 OK<br /><br /><br />
	   		</p>
	   		<?php
	   	}
	   	?>
 </body>
</html>
