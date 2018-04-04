<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager 			= new DbManager();
$roomid 			= filter_input(INPUT_POST, "roomId");
$dateLastNewMessage = filter_input(INPUT_POST, "dateLastGetMessages");
$chatRoom 			= $dbManager->GetChatroom($roomid);
$session 			= ChatUser::GetSession($roomid);
$currentUser		= $dbManager->getUser($session);
$time 				= time();

// If the room doesn't exist or the chatroom has expired, exit
if (!$chatRoom || $chatRoom->dateEnd != 0 && $chatRoom->dateEnd <= $time)
{
	echo "noRoom";
	exit;
}

// Exit if user couldn't be retrieved, TODO: better error handling
if (!$currentUser)
{
	echo "invalid_user";
	exit;
}

$dbManager->updateLastSeen($currentUser);

// Loop over the rooms users, check if the current user is associated with the room
foreach($chatRoom->users as $key => $user)
{
	// If the user hasn't pinged for NB_SECONDS_USER_TO_BE_DISCONNECTED, then disassociate the user from the room
	if(($user->lastSeen + NB_SECONDS_USER_TO_BE_DISCONNECTED) < $time)
	{
		if ($user->id != $currentUser->id)
		{
			unset($chatRoom->users[$key]);
			$dbManager->addEventMessage($user, "timed out");
			$dbManager->deleteUser($user);
		}
	}
}

// If the room is only allowed to have 2 users, delete it when a third user joins
if($chatRoom->noMoreThanOneVisitor && count($chatRoom->users) > 2)
{
	$dbManager->DeleteChatroom($roomid);
	echo "destroyed";
	exit;
}

// Check if there are messages that should be sent to the browser
if($dateLastNewMessage < $chatRoom->dateLastNewMessage)
{
	$messages = $dbManager->GetLastMessages($chatRoom->id, NB_MESSAGES_TO_KEEP);
	
	header('Content-Type: application/json');
	echo '{"dateLastGetMessages": ', time(), ', "chatLines": ', json_encode($messages), ', "userCount": ', count($chatRoom->users),'}';
	exit;
}
else
{
	// Update the amount of users online to the browser
    header('Content-Type: application/json');
	echo '{ "userCount": ', count($chatRoom->users),' }';
	exit;
}

echo "noRoom";