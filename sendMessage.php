<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager = new DbManager();

$roomid 	= filter_input(INPUT_POST, "roomId");
$time 		= time();
$chatRoom 	= $dbManager->GetChatroom($roomid);
$message 	= filter_input(INPUT_POST, "message");
$session	= ChatUser::GetSession($roomid);
$user		= $dbManager->getUser($session);

if($user && $chatRoom)
{
	$chatMessage 			= new ChatMessage();
	$chatMessage->message 	= $message;
	$chatMessage->user 		= $user->username;
	$chatMessage->isEvent	= false;
	$chatMessage->date 		= $time;
	
    $dbManager->addMessage($roomid, $chatMessage);
    
    echo 'true';
    exit;
}

echo 'false';