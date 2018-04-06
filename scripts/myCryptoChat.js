
var cryptoOptions = {
	cipher: "aes",
	mode: "gcm", // Mode: AES-GCM
	ks: 256 // Keysize: 256 bits, not actually used when using bit arrays as an encryption key
};

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

function addChatMessage(elem, chatMessage, key)
{
	var user 		= chatMessage.user;
	var message		= chatMessage.message;
	var date		= chatMessage.date;
	var isEvent		= chatMessage.isEvent == "1";

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
	
	elem.append("<span class='chathour'>(" + getDateFromTimestamp(date) + ")</span> ");
	
	if (!isEvent && vizhash.supportCanvas())
	{
		var vhash = vizhash.canvasHash(user, 15, 10);
		elem.append(vhash.canvas);
		elem.append(" ");
	}
	
	if (!isEvent)
	{
		elem.append("<a onclick='addText(\" @" + user + ": \"); return false;' class='userNameLink' href='#'><b>" + user + "</b></a> : ");
	}
	else
	{
		elem.append("<b>" + user + "</b> ");
	}
	
	elem.append(replaceUrlTextWithUrl(message).replace(/(?:\r\n|\r|\n)/g, '<br />') + "<br />");
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
        if ($.trim($("#textMessage").val()) != "")
		{
			// Convert the key back to bits from base64
			var encryptionKey = sjcl.codec.base64url.toBits(key);
			
            $.post("sendMessage.php", {
				roomId: roomId,
				user: sessionToken,
				message: sjcl.encrypt(encryptionKey, $.trim($("#textMessage").val()), cryptoOptions)
			}, function(data) {
                if (data != false)
				{
                    $("#textMessage").val("");
                    $("#textMessage").focus();
                    getMessages(false);
                }
				else
				{
                    // error : the message was not recorded
                    alert("An error occured... :(");
                }
            });
        }
    }
}

var isRefreshTitle = false;
var refreshTitleInterval;

function getMessages(changeTitle)
{
	// Retrieve the base64 key from the URL
    var key = pageKey();
	
    $.post("getMessages.php", { roomId: roomId, dateLastGetMessages: dateLastGetMessages }, function (data)
	{
        if (data == "noRoom")
		{
            // closed conversation
            $("#chatroom").html("<i>This conversation is over... You should start a new one to keep talking!</i>");
            stopTimerCheck();
        }
		else if (data == "destroyed")
		{
            // closed conversation
            $("#chatroom").html("<i>This conversation self-destroyed. It was only created for one visitor.</i>");
            stopTimerCheck();
        }
		else if (data == "invalid_user")
		{
			// Couldn't retrieve session from database
			$("#chatroom").html("<i>Your session has expired.</i>");
		}
		else if (data)
		{
			$("#nbUsers").html(data.userCount);
			
			if (key == "" || key == "=")
			{
                $("#chatroom").html("<i>The key is missing (the part of the website url after '#').</i>");
                stopTimerCheck();
            }
			else if (data.chatLines)
			{
                var hasErrors = false;
                var hasElements = false;
                var chatRoom = $("#chatroom");
                chatRoom.html("");
				
				// We need to convert the key from base64 back to bits
				var decryptionKey = sjcl.codec.base64url.toBits(key);

                for (i = 0; i < data.chatLines.length; i++)
				{
					addChatMessage(chatRoom, data.chatLines[i], decryptionKey);
					hasElements = true;
                }
				
                if (!hasElements && hasErrors)
				{
                    // wrong key error
                    chatRoom.html("The key seems to be corrupted. Are you sure that you copied the full URL (with #xxxxxxxxxxxxxxxx-xxxxxxx-xxxxxxxx) ?");
                    stopTimerCheck();
                }
				else
				{
					var objDiv = document.getElementById("chatroom");
                    objDiv.scrollTop = objDiv.scrollHeight;
                    dateLastGetMessages = data.dateLastGetMessages;

                    if (changeTitle && !isRefreshTitle) {
                        refreshTitleInterval = setInterval(
                            function () {
                                if (document.title == "Private chat room - MyCryptoChat by HowTommy.net") {
                                    document.title = "New messages ! - MyCryptoChat by HowTommy.net";
                                } else {
                                    document.title = "Private chat room - MyCryptoChat by HowTommy.net";
                                }
                            }, 3000);
                        isRefreshTitle = true;
                    }
                }
            }
        }
    });
}

function replaceUrlTextWithUrl(content) {
    var re = /((http|https|ftp):\/\/[\w?=&.\/-;#@~%+-]+(?![\w\s?&.\/;#~%"=-]*>))/ig;
    content = content.replace(re, '<a href="$1" rel="nofollow">$1</a>');
    re = /((magnet):[\w?=&.\/-;#@~%+-]+)/ig;
    content = content.replace(re, '<a href="$1">$1</a>');
    return content;
}

function stopRefreshTitle() {
    if (isRefreshTitle) {
        clearInterval(refreshTitleInterval);
        document.title = "Private chat room - MyCryptoChat by HowTommy.net";
        isRefreshTitle = false;
    }
}

function htmlEncode(value) {
    return $('<div/>').text(value).html();
}

var checkIntervalTimer;

function stopTimerCheck() {
    clearInterval(checkIntervalTimer);
}

function getDateFromTimestamp(date) {
    var date = new Date(date * 1000);
    var hours = date.getHours();
    var minutes = date.getMinutes();
    hours = hours > 9 ? hours : "0" + hours;
    minutes = minutes > 9 ? minutes : "0" + minutes;

    return hours + ':' + minutes;
}

function removeChatroom(withPassword) {
    if (confirm('Are you sure?')) {
        var removePassword = '';
        if (withPassword) {
            var removePassword = prompt("Please enter the password to remove the chat room", "");
        }
        $.post("removeChatroom.php", { roomId: roomId, removePassword: removePassword }, function (data) {
            if (data == "error") {
                alert('An error occured');
            } else if (data == "wrongPassword") {
                alert('Wrong password');
            } else if (data == "removed") {
                alert('The chat room has been removed.');
                window.location = 'index.php';
            }
        });
    }
}

function addText(text) {
    var editor = $('#textMessage');
    var value = editor.val();
    editor.val("");
    editor.focus();
    editor.val(value + text);
}

/**
 * ZeroBin 0.19
 * @link http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @author sebsauvage
 * Return the deciphering key stored in anchor part of the URL
 */
function pageKey()
{
    var key = window.location.hash.substring(1);  // Get key

    // Some stupid web 2.0 services and redirectors add data AFTER the anchor
    // (such as &utm_source=...).
    // We will strip any additional data.

    // First, strip everything after the equal sign (=) which signals end of base64 string.
    i = key.indexOf('='); if (i>-1) { key = key.substring(0,i+1); }

    // If the equal sign was not present, some parameters may remain:
    i = key.indexOf('&'); if (i>-1) { key = key.substring(0,i); }

    // Then add trailing equal sign if it's missing
    if (key.charAt(key.length-1)!=='=') key+='=';

    return key;
}

$(function () {
    getMessages(false);

    // try to get new messages every 1.5 seconds
    checkIntervalTimer = setInterval("getMessages(true)", 1500);

    $('body').on('mousemove', stopRefreshTitle);
});