
var cryptoOptions = {
	cipher: "aes",
	mode: "gcm", // Mode: AES-GCM
	ks: 256 // Keysize: 256 bits, not actually used when using bit arrays as an encryption key
};

// What's the id of the latest message received
var lastMessageId 			= 0;
var messageReceived 		= new Audio("beep.ogg");
var eventMessageReceived 	= new Audio("beep2.ogg");

// Generate a key using sjcl, a word is 32 bits, 32 * 8 = 256 bits
function generateKey()
{
	return sjcl.random.randomWords(8);
}

// Adds the key to the URL, browsers don't send the values after # to the server, it is completely client-side
function setLocationHash(value)
{
	document.location.hash = "#" + sjcl.codec.base64url.fromBits(value);
}

function addChatUser(encryptedUsername, key)
{
	var username;
	
	try {
		username = sjcl.decrypt(key, encryptedUsername);
	}
	catch(e)
	{
		username = "*Encrypted*";
	}
	
	// Create a span element, set the contents to a html encoded username
	var node = document.createElement("span");
	node.innerHTML = htmlEncode(username);
	
	// Add span to the users bar
	document.getElementById("chatusers").appendChild(node);
}

function addChatMessage(elem, chatMessage, key)
{
	var id			= Number(chatMessage.id);
	var user 		= chatMessage.user;
	var message		= chatMessage.message;
	var date		= chatMessage.date;
	var isEvent		= chatMessage.isEvent == "1";
	
	if (id > lastMessageId)
	{
		lastMessageId = id;
	}
	
	// Play different sound on event and user messages
	if (isEvent)
	{
		eventMessageReceived.play();
	}
	else
	{
		messageReceived.play();
	}

	// Try if we can decrypt the username and message
	try {
		user		= sjcl.decrypt(key, user);
		
		// Event messages are not encrypted, the server does not have encryption keys it could use for this
		if (!isEvent)
		{
			message = sjcl.decrypt(key, message);
		}
		
		// Don't use any html tags input by the users
		user		= htmlEncode(user);
		message		= htmlEncode(message);
	}
	catch (e) {
		// Content is encrypted and we don't have the right decryption key
		user		= "<i>*Encrypted*</i>";
		message		= "<i>*Encrypted*</i>";
	}
	
	var html = "<span class='" + (isEvent ? "chat-message-event" : "chat-message") + "'>";
	html += "[" + getDateFromTimestamp(date) + "] <b>" + user + "</b> ";
	
	if (!isEvent)
	{
		html += ": ";
		
		// Make links clickable
		message = replaceUrlTextWithUrl(message);
	}

	html += message + "</span><br/>";
	
	elem.insertAdjacentHTML("beforeend", html);
}

function sendMessage()
{
	// Retrieve the base64 key from the URL
	var key = pageKey();
	
	if (key == "" || key == "=")
	{
		alert("The key is missing (the part of the website url after '#').");
	}
	else
	{
		// Get the user input box
		var elem = document.getElementById("textMessage");
		var message = elem.value.trim();
		
		if (message != "")
		{
			// Convert the key back to bits from base64
			var encryptionKey = sjcl.codec.base64url.toBits(key);
			
			var formData = new FormData();
			formData.append("roomId", roomId);
			formData.append("action", "send_message");
			formData.append("message", sjcl.encrypt(encryptionKey, message, cryptoOptions));
			
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "action.php");
			
			xhr.onreadystatechange = function()
			{
				if (xhr.readyState == XMLHttpRequest.DONE)
				{
					// If the server returns HTTP status code 201 Created, the message has been sent
					if (xhr.status === 201)
					{
						// Clear the user input box
						elem.value = "";
					}
					else
					{
						document.getElementById("chatroom").insertAdjacentHTML("beforeend", "<i>Failed to send your message</i><br/>");
					}
				}
			}
			
			xhr.send(formData);
		}
	}
}

function getMessages(changeTitle)
{
	// Retrieve the base64 key from the URL
	var key = pageKey();
	
	var formData = new FormData();
	formData.append("roomId", roomId);
	formData.append("action", "get_messages");
	formData.append("lastId", lastMessageId);
	
	var xhr = new XMLHttpRequest();
	xhr.open("POST", "action.php");
	xhr.responseType = "json";
	
	xhr.onreadystatechange = function()
	{
		if (xhr.readyState == XMLHttpRequest.DONE)
		{
			var chatRoom = document.getElementById("chatroom");
			
			if (xhr.status === 200)
			{
				var data = xhr.response;
				
				if (data && data.messages)
				{
					if (key == "" || key == "=")
					{
						chatRoom.innerHTML = "<i>The key is missing (the part of the website url after '#').</i>";
						stopTimerCheck();
						return;
					}

					// We need to convert the key from base64 back to bits
					var decryptionKey = sjcl.codec.base64url.toBits(key);

					for (i = 0; i < data.messages.length; i++)
					{
						addChatMessage(chatRoom, data.messages[i], decryptionKey);
					}
					
					// Clear the users panel
					document.getElementById("chatusers").innerHTML = "";
					
					// Add all current users to the panel
					// This is not the most optimised way to do this, TODO a better way
					for(i = 0; i < data.users.length; i++)
					{
						addChatUser(data.users[i], decryptionKey);
					}
					
					if (data.messages.length > 0)
					{
						// Set the chat scrollbar to bottom
						chatRoom.scrollTop = chatRoom.scrollHeight;
						
						if (changeTitle && !isRefreshTitle)
						{
							refreshTitleInterval = setInterval(function()
							{
								if (document.title == "MyCryptoChat")
								{
									document.title = "New messages!";
								}
								else
								{
									document.title = "MyCryptoChat";
								}
									
							}, 3000);
							
							isRefreshTitle = true;
						}
					}
				}
			}
			else if (xhr.status === 400)
			{
				if (chatRoom !== null)
				{
					chatRoom.innerHTML = "<i>This conversation is over... You should start a new one to keep talking!</i>";
					stopTimerCheck();
				}
			}
			else if (xhr.status === 403)
			{
				chatRoom.innerHTML = "<i>This conversation self-destroyed. It was only created for one visitor.</i>";
				stopTimerCheck();
			}
		}
	}
	
	xhr.send(formData);
}

var regexProtocol = /((.*):\/\/(\S|,)+)/ig;
var regexImage = /((https|http):\/\/.*\/.*\.(png|jpeg|jpg|gif)$)/ig;
var regexURL = /((https|http|ftp):\/\/(\S|,)+)/ig;
var regexVideo = /((https|http):\/\/.*\.(webm|mp4)$)/ig;

function replaceUrlTextWithUrl(content)
{
	var shouldRenderMedia = document.getElementById("chatroom-render").checked;
	
	if (content.match(regexProtocol))
	{
		if (content.match(regexImage) && shouldRenderMedia)
		{
			content = content.replace(regexImage, '<br/><a href="$1" rel="nofollow" target="_blank"><img class="chat-message-image" src="$1" alt="$1"/></a>');
		}
		else if (content.match(regexVideo) && shouldRenderMedia)
		{
			content = content.replace(regexVideo, '<br/><video class="chat-message-video" controls><source src="$1" type="video/$3"></video>');
		}
		else if (content.match(regexURL))
		{
			content = content.replace(regexURL, '<a href="$1" rel="nofollow" target="_blank">$1</a>');
		}
		else
		{
			content = content.replace(regexProtocol, '<a href="$1">$1</a>');
		}
	}	
	
	return content;
}

function htmlEncode(str)
{
    return str
		.replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function getDateFromTimestamp(date) {
	var date = new Date(date * 1000);
	var hours = date.getHours();
	var minutes = date.getMinutes();
	hours = hours > 9 ? hours : "0" + hours;
	minutes = minutes > 9 ? minutes : "0" + minutes;

	return hours + ':' + minutes;
}

function removeChatroom(withPassword)
{
	if (confirm('Are you sure?'))
	{
		var removePassword = '';
		
		if (withPassword) {
			var removePassword = prompt("Please enter the password to remove the chat room", "");
		}
		
		var formData = new FormData();
		formData.append("roomId", roomId);
		formData.append("action", "delete_chatroom");
		formData.append("removePassword", removePassword);
		
		var xhr = new XMLHttpRequest();
		xhr.open("POST", "action.php");
		
		xhr.onreadystatechange = function()
		{
			if (xhr.readyState == XMLHttpRequest.DONE)
			{
				if (xhr.status === 200)
				{
					window.location = 'index.php';
				}
				else if (xhr.status === 403)
				{
					alert('Wrong password');
				}
				else
				{
					alert('An error occured');
				}
			}
		}
		
		xhr.send(formData);
	}
}

/**
 * ZeroBin 0.19
 * @link http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @author sebsauvage
 * Return the deciphering key stored in anchor part of the URL
 */
function pageKey()
{
	var key;
	
	if (sessionStorage && sessionStorage.getItem(roomId))
	{
		key = sessionStorage.getItem(roomId);
	}
	else
	{
		key = window.location.hash.substring(1);  // Get key

		// Some stupid web 2.0 services and redirectors add data AFTER the anchor
		// (such as &utm_source=...).
		// We will strip any additional data.

		// First, strip everything after the equal sign (=) which signals end of base64 string.
		i = key.indexOf('='); if (i>-1) { key = key.substring(0,i+1); }

		// If the equal sign was not present, some parameters may remain:
		i = key.indexOf('&'); if (i>-1) { key = key.substring(0,i); }

		// Then add trailing equal sign if it's missing
		if (key.charAt(key.length-1)!=='=') key+='=';
	}

	return key;
}