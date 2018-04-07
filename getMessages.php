<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager 			= new DbManager();
$roomid 			= filter_input(INPUT_POST, "roomId");
$lastId				= filter_input(INPUT_POST, "lastId", FILTER_VALIDATE_INT);
$chatRoom 			= $dbManager->GetChatroom($roomid);
$session 			= ChatUser::GetSession($roomid);
$currentUser		= $dbManager->getUser($session);
$time 				= time();

// Room doesn't exist
if (!$chatRoom)
{
	echo "noRoom";
	exit;
}

// Room has expired
if ($chatRoom->dateEnd <= $time)
{
	$dbManager->deleteChatroom($roomid);
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
	$dbManager->deleteChatroom($roomid);
	echo "destroyed";
	exit;
}

// Get the latest messages, but not messages we have already received
$messages = $dbManager->getLastMessages($chatRoom->id, NB_MESSAGES_TO_KEEP, $lastId);

header('Content-Type: application/json');
echo '{"messages": ', json_encode($messages), ', "userCount": ', count($chatRoom->users),'}';