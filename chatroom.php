<?php
	require 'inc/constants.php';
	require 'inc/conf.php';
	require 'inc/init.php';
	require 'inc/functions.php';
	require 'inc/classes.php';
	require 'inc/dbmanager.php';

	$dbManager = new DbManager();

	$roomid		= filter_input(INPUT_GET, "id");
	$username 	= filter_input(INPUT_POST, "username");
	$chatRoom 	= $dbManager->GetChatroom($roomid);
	$session	= ChatUser::GetSession($roomid);
	$user		= $dbManager->getUser($session);
	
	if ($chatRoom && !$user && $username)
	{
		// Create new session
		$user = ChatUser::Create($roomid);
		$user->setUsername($username);
		
		$dbManager->saveUser($user);
		$chatRoom->addUser($user);
		
		$dbManager->addEventMessage($user, "joined the room");
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>MyCryptoChat</title>
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<meta name="viewport" content="width=device-width" />
	<link href="styles/myCryptoChat.css" rel="stylesheet" />
</head>
<body>
	<header>
		<div class="content-wrapper">
			<div class="float-left">
				<p class="site-title"><a href="index.php">MyCryptoChat</a></p>
			</div>
			<div class="float-right">
				<nav>
					<ul id="menu">
						<li><a href="index.php">Home</a></li>
						<li><a href="stats.php">Stats</a></li>
						<li><a href="about.php">About</a></li>
					</ul>
				</nav>
			</div>
		</div>
	</header>
	<?php if ($user) { ?>
	<div id="body">
		<section class="content-wrapper main-content clear-fix">
			<div class="container">
				<div class="chat-container">
					<div id="chatroom"></div>
					<div id="chatusers"></div>
				</div>
				<div id="chatbar">
					<input type="text" id="textMessage" placeholder="Type to chat" onkeydown="if (event.keyCode == 13) { sendMessage(); }"/>
				</div>
			</div>
			<div>
				<?php 
					if($chatRoom && $chatRoom->isRemovable) {
				?>
					<br/>
					<div id="divButtonRemoveChatroom">
						<input type="button" value="Remove the chat room" onclick="removeChatroom(<?php if($chatRoom->removePassword != '') { echo 'true'; } else { echo 'false'; } ?>);" />
					</div>
				<?php
					}
				?>
			</div>
		</section>
	</div>
	<script type="text/javascript">
		var sessionToken = "<?php echo $user->id; ?>";
	</script>
	<?php } else { ?>
	<div id="body_username">
		<section class="content-wrapper main-content clear-fix">
			<h2>Join chatroom</h2>
			<div class="mb20">You need to choose a username before joining the chatroom</div>
			<form method="POST" onsubmit="encryptUsername()">
				<label>Username:  <input type="text" id="username" required/> <input type="submit" value="Enter"/></label>
				<input type="hidden" id="username_encrypted" name="username"/>
			</form>
		</section>
	</div>
	<?php } ?>
	<footer>
		<p>&copy; 2018 MyCryptoChat <?php echo MYCRYPTOCHAT_VERSION; ?> by <a href="https://github.com/Undone/mycryptochat">Undone</a></p>
	</footer>
	<script type="text/javascript" src="scripts/sjcl.js"></script>
	<script type="text/javascript" src="scripts/myCryptoChat.js"></script>
	<script type="text/javascript">
		var roomId = '<?php echo htmlspecialchars($roomid, ENT_QUOTES, 'UTF-8'); ?>';
		var checkIntervalTimer;
		var isRefreshTitle = false;
		var refreshTitleInterval;
		
		// Before submitting the form, encrypt the username
		// The plain-text value is never sent to the server
		function encryptUsername()
		{
			var elem	= document.getElementById("username");
			var elem2	= document.getElementById("username_encrypted");
			var key		= pageKey();
			
			if (key != "" && key != "=")
			{
				key = sjcl.codec.base64url.toBits(key);

				elem2.value = sjcl.encrypt(key, elem.value, cryptoOptions);
			}
		}

		function stopTimerCheck()
		{
			clearInterval(checkIntervalTimer);
		}
		
		function stopRefreshTitle()
		{
			if (isRefreshTitle)
			{
				clearInterval(refreshTitleInterval);
				document.title = "MyCryptoChat";
				isRefreshTitle = false;
			}
		}

		function onLoad()
		{
			var key = pageKey();
			
			// If an encryption key has not been set
			if (key === "=" || key == "")
			{
				// We need to generate a new key
				var newkey = generateKey();
				
				// Append it to the URL
				setLocationHash(newkey);
			}
			
			getMessages(false);

			// try to get new messages every 1.5 seconds
			checkIntervalTimer = setInterval("getMessages(true)", 1500);
		}
		
		window.addEventListener("load", onLoad);
		window.addEventListener("mousemove", stopRefreshTitle);
	</script>
</body>
</html>