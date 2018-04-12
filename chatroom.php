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
	<div id="body" style="display: none">
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
	<div id="body_username">
		<section class="content-wrapper main-content clear-fix">
			<h2>Join chatroom</h2>
			<div class="mb20">You need to choose a username before joining the chatroom</div>
			<form method="POST" onsubmit="return setUsername()">
				<label>Username: <input type="text" id="username" required/> <input type="submit" value="Enter"/></label>
			</form>
		</section>
	</div>
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
		
		function displayChat()
		{
			document.getElementById("body").style.display 			= "block";
			document.getElementById("body_username").style.display 	= "none";

			getMessages(false);

			// try to get new messages every 1.5 seconds
			checkIntervalTimer = setInterval("getMessages(true)", 1500);
		}
		
		function setUsername()
		{
			var key = pageKey();
			var username = document.getElementById("username").value;
			
			if (key != "" && key != "=")
			{
				key = sjcl.codec.base64url.toBits(key);
				username = sjcl.encrypt(key, username, cryptoOptions);
			}
			else
			{
				return false;
			}
			
			var formData = new FormData();
			formData.append("roomId", roomId);
			formData.append("action", "register");
			formData.append("username", username);
			
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "action.php");
			
			xhr.onreadystatechange = function()
			{
				if (xhr.readyState == XMLHttpRequest.DONE)
				{
					if (xhr.status === 201)
					{
						displayChat();
					}
				}
			}
			
			xhr.send(formData);
			return false;
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
			
			<?php if ($user) { ?>
				displayChat();
			<?php } ?>
		}
		
		// Send disconnect message after closing the window
		function onUnload()
		{
			var formData = new FormData();
			formData.append("roomId", roomId);
			formData.append("action", "disconnect");
			
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "action.php");
			xhr.send(formData);
		}
		
		window.addEventListener("load", onLoad);
		window.addEventListener("unload", onUnload);
		window.addEventListener("mousemove", stopRefreshTitle);
	</script>
</body>
</html>