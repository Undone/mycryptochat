<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager = new DbManager();

$roomid		= filter_input(INPUT_POST, "roomId");
$action		= filter_input(INPUT_POST, "action");
$username 	= filter_input(INPUT_POST, "username");
$chatRoom 	= $dbManager->GetChatroom($roomid);
$session	= ChatUser::GetSession($roomid);
$user		= $dbManager->getUser($session);

switch($action)
{
	// Create a session for user and set the username after entering username on chatroom join
	case "register":
	{
		if ($chatRoom && !$user && $username)
		{
			// Create new session
			$user = ChatUser::Create($roomid);
			$user->setUsername($username);
			
			$dbManager->saveUser($user);
			$chatRoom->addUser($user);
			
			$dbManager->addEventMessage($user, "joined the room");
			
			header("HTTP/1.1 201 Created");
			exit;
		}
		
		break;
	}
	
	// Send a message
	case "send_message":
	{
		$message = filter_input(INPUT_POST, "message");
		
		if($user && $chatRoom && $message)
		{
			$chatMessage 			= new ChatMessage();
			$chatMessage->roomid	= $roomid;
			$chatMessage->message 	= $message;
			$chatMessage->user 		= $user->username;
			$chatMessage->isEvent	= false;
			$chatMessage->date 		= time();
			
			$dbManager->addMessage($chatMessage);
			
			header("HTTP/1.1 201 Created");
			exit;
		}
		
		break;
	}
	
	// Delete the chatroom, if it's been flagged as removable
	case "delete_chatroom":
	{
		if ($chatRoom && $chatRoom->isRemovable)
		{
			if ($chatRoom->removePassword != $password)
			{
				header("HTTP/1.1 403 Forbidden");
				exit;
			}

			$dbManager->deleteChatroom($roomid);
			header("HTTP/1.1 200 OK");
			exit;
		}
		
		break;
	}
	
	// Disconnect from the chat
	case "disconnect":
	{
		if ($chatRoom && $user)
		{
			$dbManager->addEventMessage($user, "disconnected");
			$chatRoom->removeUser($user);
			$dbManager->deleteUser($user);
			
			header("HTTP/1.1 200 OK");
			exit;
		}
		
		break;
	}
}

header("HTTP/1.1 400 Bad Request");