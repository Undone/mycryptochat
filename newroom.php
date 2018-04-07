<?php
require 'inc/constants.php';
require 'inc/conf.php';
require 'inc/init.php';
require 'inc/functions.php';
require 'inc/classes.php';
require 'inc/dbmanager.php';

if(array_key_exists($_POST['nbMinutesToLive'], $allowedTimes)) {
    $nbMinutesToLive = $_POST['nbMinutesToLive'];
} else {
    exit('cheater');
}

$time = time();

$selfDestroys = isset($_POST['selfDestroys']) && $_POST['selfDestroys'] == 'true';

$isRemovable = isset($_POST['isRemovable']) && $_POST['isRemovable'] == 'true';
$removePassword = $_POST['removePassword'];

// we generate a random key
$key = randomString(20);

// we create the chat room object
$chatRoom 						= new ChatRoom($key);
$chatRoom->dateCreation 		= $time;
$chatRoom->dateEnd 				= $nbMinutesToLive != 0 ? $time + ($nbMinutesToLive * 60) : 0;
$chatRoom->noMoreThanOneVisitor = $selfDestroys;
$chatRoom->isRemovable 			= $isRemovable;
$chatRoom->removePassword 		= $removePassword;

$dbManager = new DbManager();

// we delete old chatrooms
$dbManager->cleanChatrooms();

// we save the chat room in the database
$dbManager->createChatroom($chatRoom);

header('Location: chatroom.php?id=' . $key);