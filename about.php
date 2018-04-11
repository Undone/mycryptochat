<?php
	require "inc/constants.php";
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>About - MyCryptoChat</title>
		<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
		<meta name="viewport" content="width=device-width" />
		<link href="styles/myCryptoChat.css" rel="stylesheet"/>
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
		<div id="body">
			<section class="content-wrapper main-content clear-fix">
				<h2>About</h2>
				<p>
					MyCryptoChat is a PHP and Javascript based chat with end-to-end encryption.
					The database will only contain your encrypted messages, and will have no knowledge of the decryption key.
					Encryption is provided by the Stanford Javascript Crypto Library, using 256-bit AES-GCM.
					<br/>
					<br/>
					<a href="https://github.com/Undone/mycryptochat" target="_blank">GitHub</a>
					<br/>
					<br/>
					This is a fork of MyCryptoChat v1.0.4 by Tommy of <a href="http://blog.howtommy.net">HowTommy.net</a>
				</p>
			</section>
		</div>
		<footer>
			<p>&copy; 2018 MyCryptoChat <?php echo MYCRYPTOCHAT_VERSION; ?> by <a href="https://github.com/Undone/mycryptochat">Undone</a></p>
		</footer>
	</body>
</html>