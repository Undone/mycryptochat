<?php
require 'inc/constants.php';
require 'inc/functions.php';
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>MyCryptoChat</title>
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<meta name="viewport" content="width=device-width" />
	<link href="style.css" rel="stylesheet" />
</head>
<body>
	<?php
	$showContent = true;
	$configIncluded = false;
	if(is_readable(CONFIG_FILE_NAME)) {
		include CONFIG_FILE_NAME;
		$configIncluded = true;
	} else {
		$showContent = false;
		?>
		<h2>Error: missing inc/conf.php</h2>
		<p>
			MyCryptoChat can't read the configuration file.<br />
			Copy <strong>inc/conf.template.php</strong> into <strong>inc/conf.php</strong>, and don't forget to <strong>customize it</strong>.
		</p>
	<?php
	}
	if(DB_TYPE == DATABASE_SQLITE && !is_writable(DB_FILE_NAME)) {
		$showContent = false;
	?>
	<h2>Error: database access</h2>
	<p>
		MyCryptoChat can't edit the database file.<br />
		Please give all rights to the apache (or current) user on the 'chatrooms.sqlite' file.
	</p>
	<?php
	}
	if (!extension_loaded('PDO')) {
		$showContent = false;
	?>
	<h2>Error: PDO missing</h2>
	<p>
		The PDO module is missing.<br />
		Please add it and load it to make this website work.
	</p>
	<?php
	}
	if (DB_TYPE == DATABASE_SQLITE && !extension_loaded('PDO_SQLITE')) {
		$showContent = false;
	?>
	<h2>Error: PDO SQLite missing</h2>
	<p>
		The PDO SQLite module is missing.<br />
		Please add it and load it to make this website work.
	</p>
	<?php
	}
	if(!is_writable(LOGS_FILE_NAME)) {
		$showContent = false;
	?>
	<h2>Error: logs file access</h2>
	<p>
		MyCryptoChat can't edit the logs file.<br />
		Please give all rights to the apache (or current) user on the 'logs.txt' file.
	</p>
	<?php
	}
	if (version_compare(phpversion(), '5.4.0', '<')) {
		$showContent = false;
	?>
	<h2>Error: php version</h2>
	<p>
		The version of php is too low.<br />
		You need at least PHP 5.4 to run this website.
	</p>
	<?php
	}
	if($showContent) {
	?>
	<noscript>
		This website needs JavaScript activated to work. 
			  <style>
				  div {
					  display: none;
				  }
			  </style>
	</noscript>
	<header>
		<div class="content-wrapper">
			<nav>
				<ul>
					<li id="site-title"><a href="index.php">MyCryptoChat</a></li>
					<div id="site-links">
						<li><a href="stats.php">Stats</a></li>
						<li><a href="about.php">About</a></li>
					</div>
				</ul>
			</nav>
		</div>
	</header>
	<section class="content-wrapper main-content clear-fix">
		<h1>Create a chatroom <span>Chat using end-to-end encryption</span></h1>
		<form method="POST" onsubmit="return createChatroom()">
			<label for="expire">Chatroom is deleted after: </label>
			<select id="expire" name="expire">
				<?php foreach ($allowedTimes as $minutes => $label) { ?>
					<option value="<?php echo $minutes; ?>"><?php echo $label; ?></option>
				<?php } ?>
			</select>
			<br/>
			<p><b>Note:</b> Rooms will be deleted after <?php echo DAYS_TO_DELETE_IDLE_CHATROOM; ?> days of inactivity, regardless of the expiration time.</p>
			<br/>
			<input type="checkbox" id="removable"/>
			<label for="removable" class="checkbox">The chatroom can be manually deleted</label>
			<br/>
			<div id="divRemovePassword">
				<p>You can enter a password or leave the field empty. An empty field means anyone can delete the room.</p>
				<input type="password" id="removePassword" placeholder="Enter a password" value="" />
			</div>
			<br/>
			<input type="checkbox" id="selfDestroys"/>
			<label for="selfDestroys" class="checkbox">Self-destroys if more than two concurrent users join the chat</label>
			<br/>
			<br/>
			<input type="checkbox" id="key-generate" onchange="toggleKeyMenu()" checked/>
			<label for="key-generate" class="checkbox">Use a random generated encryption key</label>
			<div id="key-menu">
				<br/>
				<input type="password" id="key-custom" placeholder="Enter a password" value="" />
			</div>
			<br/>
			<br/>
			<input type="submit" value="Create a new chat room"/>
		</form>
	</section>
	<script type="text/javascript" src="scripts/sjcl.js"></script>
	<script type="text/javascript" src="scripts/myCryptoChat.js"></script>
	<script type="text/javascript">
		function createChatroom()
		{
			var expire 			= document.getElementById("expire").value;
			var removable 		= document.getElementById("removable").checked;
			var removePassword 	= document.getElementById("removePassword").value;
			var customKey		= !document.getElementById("key-generate").checked;
			
			var formData = new FormData();
			formData.append("action", "create_chatroom");
			formData.append("expire", expire);
			formData.append("removable", removable);
			
			if (removable)
			{
				formData.append("removePassword", removePassword);
			}
			
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "action.php");
			xhr.responseType = "json";
			xhr.onreadystatechange = function()
			{
				if (xhr.readyState === XMLHttpRequest.DONE)
				{
					if (xhr.status === 201)
					{
						var key = sjcl.random.randomWords(8);
						var roomid = xhr.response;
						
						if (customKey)
						{
							var password 	= document.getElementById("key-custom").value;
							key 			= sjcl.misc.pbkdf2(password, roomid);
							
							sessionStorage.setItem(roomid, sjcl.codec.base64url.fromBits(key));
							
							window.location = "chatroom.php?id=" + xhr.response;
						}
						else
						{
							window.location = "chatroom.php?id=" + xhr.response + "#" + sjcl.codec.base64url.fromBits(key);
						}
					}
					else
					{
						alert("Failed to create the chatroom!");
					}
				}
			}
			xhr.send(formData);
			
			return false;
		}
		
		function toggleKeyMenu()
		{
			var checkbox = document.getElementById("key-generate");
			var elem = document.getElementById("key-menu");
			
			if (!checkbox.checked)
			{
				elem.style.display = "block";
			}
			else
			{
				elem.style.display = "none";
			}
		}
		
		function removableChanged(event)
		{
			var passwordElement = document.getElementById("divRemovePassword");
			
			if(this.checked)
			{
				passwordElement.style.display = "block";
			}
			else
			{
				passwordElement.style.display = "none";
			}
		}
		
		document.getElementById("removable").addEventListener("change", removableChanged);
	</script>
	<footer>
		<p>&copy; 2018 MyCryptoChat <?php echo MYCRYPTOCHAT_VERSION; ?> by <a href="https://github.com/Undone/mycryptochat">Undone</a></p>
	</footer>
	<?php } ?>
</body>
</html>