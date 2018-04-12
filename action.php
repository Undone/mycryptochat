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
	
	// Retrieve latest messages
	case "get_messages":
	{
		$lastId = filter_input(INPUT_POST, "lastId", FILTER_VALIDATE_INT);
		
		if ($chatRoom && $user && isset($lastId))
		{
			// Room has expired
			if ($chatRoom->dateEnd <= time())
			{
				$dbManager->deleteChatroom($roomid);
				break;
			}

			// If the room is only allowed to have 2 users, delete it when a third user joins
			if($chatRoom->noMoreThanOneVisitor && count($chatRoom->users) > 2)
			{
				$dbManager->deleteChatroom($roomid);
				header("HTTP/1.1 403 Forbidden");
				exit;
			}

			$dbManager->updateLastSeen($user);

			// Loop over the rooms users, check if the current user is associated with the room
			foreach($chatRoom->users as $key => $value)
			{
				// If the user hasn't pinged for NB_SECONDS_USER_TO_BE_DISCONNECTED, then disassociate the user from the room
				if(($value->lastSeen + NB_SECONDS_USER_TO_BE_DISCONNECTED) < time())
				{
					if ($value->id != $user->id)
					{
						unset($chatRoom->users[$key]);
						$dbManager->addEventMessage($value, "timed out");
						$dbManager->deleteUser($value);
					}
				}
			}

			// Get the latest messages, but not messages we have already received
			$messages = $dbManager->getLastMessages($chatRoom->id, NB_MESSAGES_TO_KEEP, $lastId);

			header("HTTP/1.1 200 OK");
			header('Content-Type: application/json');
			echo '{"messages": ', json_encode($messages), ', "users": ', json_encode($chatRoom->getUsernames()),'}';
			exit;
		}
		
		break;
	}
}

header("HTTP/1.1 400 Bad Request");