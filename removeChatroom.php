<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

$dbManager = new DbManager();

$roomid 	= filter_input(INPUT_POST, "roomId");
$password 	= filter_input(INPUT_POST, "password");

$chatRoom = $dbManager->GetChatroom($roomid);

if (!$chatRoom || !$chatRoom->isRemovable)
{
	header("HTTP/1.1 400 Bad Request");
	exit;
}

if ($chatRoom->removePassword != $password)
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}

$dbManager->deleteChatroom($roomid);