<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager = new DbManager();

$roomid		= filter_input(INPUT_POST, "roomId");
$username 	= filter_input(INPUT_POST, "username");
$chatRoom 	= $dbManager->GetChatroom($roomid);
$session	= ChatUser::GetSession($roomid);
$user		= $dbManager->getUser($session);
	
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

header("HTTP/1.1 400 Bad Request");