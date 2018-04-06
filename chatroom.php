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
	
	if (!$user && $username)
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
    <title>Private chat room - MyCryptoChat by HowTommy.net</title>
    <link href="/favicon.ico" rel="shortcut icon" type="image/x-icon" />
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
                <section id="login">
                </section>
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
            <h2>MyCryptoChat</h2>
            <div class="mb20">Chat with friends without anyone spying on what you say!</div>
            <div id="chatroom"></div>
            <div id="divUsers"><span id="nbUsers">1</span> user(s) online</div>
            <div>
				<p>Username: <span id="usernameDisplay"><?php echo $user->username; ?></span></p>
                <textarea id="textMessage" onkeydown="if (event.keyCode == 13 && !event.shiftKey) { sendMessage(); }"></textarea><br />
                <input type="button" value="Send" id="sendMessage" onclick="sendMessage();" /><br /><br />
				<?php 
					if($chatRoom->isRemovable) {
				?>
					<br /><div id="divButtonRemoveChatroom">
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
        <div class="content-wrapper">
            <div class="float-left">
                <p>&copy; 2018 MyCryptoChat <?php echo MYCRYPTOCHAT_VERSION; ?> by <a href="https://github.com/Undone/mycryptochat">Undone</a></p>
            </div>
        </div>
    </footer>
	<script type="text/javascript" src="scripts/jquery.js"></script>
	<script type="text/javascript" src="scripts/sjcl.js"></script>
	<script type="text/javascript" src="scripts/vizhash.js"></script>
	<script type="text/javascript" src="scripts/myCryptoChat.js"></script>
    <script type="text/javascript">
        var roomId = '<?php echo htmlspecialchars($roomid, ENT_QUOTES, 'UTF-8'); ?>';
        var dateLastGetMessages = '<?php echo microtime(true) - 24*60*60*365*3; ?>';
		
		// Before submitting the form, encrypt the username
		// The plain-text value is never sent to the server
		function encryptUsername()
		{
			var elem 	= document.getElementById("username");
			var elem2	= document.getElementById("username_encrypted");
			var key 	= pageKey();
			
			if (key != "" && key != "=")
			{
				key = sjcl.codec.base64url.toBits(key);

				elem2.value = sjcl.encrypt(key, elem.value, cryptoOptions);
			}
		}
		
		$(function()
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
			else
			{
				var elem = document.getElementById("usernameDisplay");
				
				if (elem !== null)
				{
					// Convert key from base64 to bit array
					key = sjcl.codec.base64url.toBits(key);
					
					// Decrypt the displayed username
					elem.innerHTML = sjcl.decrypt(key, elem.innerHTML);
				}
			}
		});
    </script>
</body>
</html>