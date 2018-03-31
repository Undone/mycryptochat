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
$updateDatabase		= false;

if (!$chatRoom)
{
	exit;
}

$userHash 	= getHashForIp();
$time 		= time();

// Check if the room has expired
if($chatRoom->dateEnd != 0 && $chatRoom->dateEnd <= $time)
{
	echo 'noRoom';
	exit;
}

$currentUser = null;

// Loop over the rooms users, check if the current user is associated with the room
foreach($chatRoom->users as $key => $user)
{
	if($user['id'] == $userHash)
	{
		$currentUser = $user;
		$currentUser['dateLastSeen'] = $time;
	}

	// If the user hasn't pinged for NB_SECONDS_USER_TO_BE_DISCONNECTED, then disassociate the user from the room
	if(($user['dateLastSeen'] + NB_SECONDS_USER_TO_BE_DISCONNECTED) < $time)
	{
		unset($chatRoom->users[$key]);
		$updateDatabase = true;
	}
}

// If the current user isn't associated with the current room, add him
if (!$currentUser)
{
	$currentUser = array();
	$currentUser['id'] = $userHash;
	$currentUser['dateLastSeen'] = $time;
	
	$chatRoom->addUser($currentUser);
	$updateDatabase = true;
}

// If the room is only allowed to have 2 users, delete it when a third user joins
if($chatRoom->noMoreThanOneVisitor && count($chatRoom->users) > 2)
{
	$dbManager->DeleteChatroom($roomid);
	exit;
}

// If changes have been made, save them to the database
if ($updateDatabase)
{
	$dbManager->UpdateChatRoomUsers($chatRoom);
}

// Check if there are messages that should be sent
if($dateLastNewMessage < $chatRoom->dateLastNewMessage)
{
	$messages = $dbManager->GetLastMessages($chatRoom->id, NB_MESSAGES_TO_KEEP);
	header('Content-Type: application/json');
	echo '{"dateLastGetMessages": ',$time,', "chatLines": ',json_encode($messages),', "nbIps": ', count($chatRoom->users),'}';
	exit;
}
else
{
	// Update the amount of users online to the browser
    header('Content-Type: application/json');
	echo '{ "nbIps": ', count($chatRoom->users),' }';
	exit;
}

echo "noRoom";