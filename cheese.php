<?php
session_start();
?><?php
	//	Setup for HybridAuth
	$config = dirname(__FILE__) . '/hybridauth-2.9.1/hybridauth/config.php';
	require_once( "hybridauth-2.9.1/hybridauth/Hybrid/Auth.php" );

	function insert_user($tw_name,$tw_id)
	{
		//	Connect to the Database
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}

		//	Does this already exist?
		$stmt = $mysqli->prepare("SELECT twitter_username FROM users WHERE twitter_id = ?");
		$stmt->bind_param("s",$tw_id);
		$stmt->execute();
		$stmt->bind_result($result);
		$stmt->fetch();
		if ($result == null) {
			//	Generate a GUID for the user
			$guid = guidv4();

			$stmt = $mysqli->prepare("INSERT INTO users(user_id, twitter_username, twitter_id) VALUES (?, ?, ?)");
			$stmt->bind_param('sss', $guid, $tw_name, $tw_id);

			$stmt->execute();

		}
		/* close statement and connection */
		$stmt->close();
		/* close connection */
		$mysqli->close();
	}

	function insert_checkin($tw_id,$cheese_id,$comment)
	{
		//	Get the GUID for the user
		$user_id = get_user($tw_id);
		//	Connect to the Database
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}


		$stmt = $mysqli->prepare("INSERT INTO checkins(checkin_id, checkin_time,        user_id,  cheese_id,  comment) VALUES (?, ?, ?, ?, ?)");
		$stmt->bind_param('sssss',                     guidv4(),      date("Y-m-d H:i:s"), $user_id, $cheese_id, $comment);

		$stmt->execute();

		/* close statement and connection */
		$stmt->close();
		/* close connection */
		$mysqli->close();
	}

	function get_user($tw_id)
	{
		//	Connect to the Database
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}

		//	Does this already exist?
		$stmt = $mysqli->prepare("SELECT user_id FROM users WHERE twitter_id = ?");
		$stmt->bind_param("s",$tw_id);
		$stmt->execute();
		$stmt->bind_result($result);
		$stmt->fetch();
		if ($result == null) {
			die();
		} else {
			return $result;
		}
	}

	function get_cheese($cheese_id)
	{
		//	Connect to the Database
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}

		//	Does this already exist?
		$stmt = $mysqli->prepare("SELECT * FROM cheeses WHERE cheese_id = ?");
		$stmt->bind_param("s",$cheese_id);
		$stmt->execute();
		$stmt->bind_result($id,$name,$url);
		$stmt->fetch();
		if ($id == null) {
			die();
		} else {
			$cheese = array(
				"name" => $name,
				"url" => $url,
			);
			return $cheese;
		}
	}


	function guidv4()
	{
		//	See http://php.net/manual/en/function.com-create-guid.php
		$data = random_bytes(16);	// PHP7+ only
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		//	Magic
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	function get_cheese_list() {
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		$results = $mysqli->query("SELECT * FROM cheeses");
		$cheeses = $results->fetch_all();

		$form = "<select name='cheese'>";
		foreach ($cheeses as $cheese) {
			$form .= "<option value='" . $cheese[0] . "'>" . $cheese[1] . "</option>";
		}
		$form .= "</select>";
		return $form;
	}

	function get_previous($tw_id) {
		$user = get_user($tw_id);
		$mysqli = new mysqli("10.169.0.141", "cheeser", "password", "CHEESE");
		if ($mysqli->connect_errno) {
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
		$results = $mysqli->query("SELECT * FROM checkins WHERE user_id='".$user."'");
		$checkins = $results->fetch_all();

		$previous = "<ol>";
		foreach ($checkins as $checkin) {
			$cheese = get_cheese($checkin[3]);
			$previous .= "<li>".$checkin[1] ." <a href='" . $cheese['url'] . "'>".$cheese['name']."</a> ".$checkin[4]."</li>";
		}
		$previous .= "</ol>";
		return $previous;
	}


	try{
		$hybridauth = new Hybrid_Auth( $config );

		$twitter = $hybridauth->authenticate( "Twitter" );

		$user_profile = $twitter->getUserProfile();
		$tw_name = $user_profile->displayName;
		$tw_id = $user_profile->identifier;

		if($_POST['tw_id'] != null) {
			insert_checkin($_POST['tw_id'],$_POST['cheese'],$_POST['status']);
			$cheese = get_cheese($_POST['cheese']);
			$tweet = "Checked in to " . $cheese["name"] . "\n" . $_POST['status'] . "\n" . $cheese["url"];
			$twitter->setUserStatus( $tweet );
		}
		echo "<!doctype html><html><head></head><body>";
		echo "<h1>Hi there! " . $tw_name . "</h1>";
		insert_user($tw_name,$tw_id);

		$previous = get_previous($tw_id);

		echo $previous;

		echo '<form method="post" action="" enctype="multipart/form-data">
			<input name="tw_id" value="'.$tw_id.'" type="hidden"/>';
		echo	get_cheese_list();
		echo '	<textarea id="status" name="status" rows="4"></textarea>
			<input type="submit" value="Check in to this Cheese"/>';

	}
	catch( Exception $e ){
		echo "Ooops, we got an error: " . $e->getMessage();
	}
