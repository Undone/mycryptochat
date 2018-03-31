<?php
class DbManager
{
    private $db;
    
    function __construct()
	{
        try
		{
			if (DB_TYPE == DATABASE_SQLITE)
			{
				$this->db = new PDO('sqlite:' . DB_FILE_NAME);
				$this->db->setAttribute(PDO::ATTR_PERSISTENT, true /*, PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION*/);
				$this->db->exec('PRAGMA temp_store = MEMORY; PRAGMA synchronous=OFF;');
			}
			elseif (DB_TYPE == DATABASE_MYSQL)
			{
				$this->db = new PDO("mysql:dbname=".DB_NAME.";host=".DB_HOST, DB_USER, DB_PASSWORD);
			}
			else
			{
				die("Invalid DB_TYPE, check your configuration");
			}
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function CreateChatroom($chatRoom) {
        if(is_null($chatRoom)) {
            die('Parameter error.');   
        }
        try {
			$query = 'INSERT INTO rooms (id, created, lastmessage, users, expire, singleuser, userhash, removable, removepassword) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
			
			$req = $this->db->prepare($query);

			$req->execute(array(
				$chatRoom->id,
				$chatRoom->dateCreation,
				$chatRoom->dateLastNewMessage,
				json_encode($chatRoom->users),
				$chatRoom->dateEnd,
				$chatRoom->noMoreThanOneVisitor ? 1 : 0,
				$chatRoom->userId,
				$chatRoom->isRemovable ? 1 : 0,
				$chatRoom->removePassword
			));
			
            if($this->db->errorCode() != '00000') 
            {
                die('Error: database error.');
            }
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function CleanChatrooms($time) {
        try {
            $query = 'DELETE FROM messages WHERE roomid IN (SELECT id FROM rooms WHERE ( expire <> 0 AND expire < ? ) OR lastmessage < ?)';
			
			$idleTime = $time - (DAYS_TO_DELETE_IDLE_CHATROOM * 24 * 60 * 60);
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($time, $idleTime));
            
            $query = 'DELETE FROM rooms WHERE ( expire <> 0 AND expire < ? ) OR lastmessage < ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($time, $idleTime));
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function DeleteChatroom($chatRoomId) {
        try {
            $query = 'DELETE FROM messages WHERE roomid = ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($chatRoomId));
            
            $query = 'DELETE FROM rooms WHERE id = ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($chatRoomId));
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function GetChatroom($id)
	{
        if(is_null($id) || $id == '')
		{
            return null;    
        }
        
        try {            
            $query = 'SELECT id, created, lastmessage, users, expire, singleuser, userhash, removable, removepassword FROM rooms WHERE id = ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($id));
            
            $result = $req->fetchAll();
            
            if(is_null($result) || count($result) != 1) {
                return null;
            }
            
            $resultRow = $result[0];
            
            $chatRoom = new ChatRoom;
            $chatRoom->id = $resultRow['id'];
            $chatRoom->dateCreation = $resultRow['created'];
            $chatRoom->dateLastNewMessage = $resultRow['lastmessage'];
            $chatRoom->users = json_decode($resultRow['users'], true);
            $chatRoom->dateEnd = $resultRow['expire'];
            $chatRoom->noMoreThanOneVisitor = $resultRow['singleuser'] == 1;
			$chatRoom->userId = $resultRow['userhash'];
			$chatRoom->isRemovable = $resultRow['removable'] == 1;
			$chatRoom->removePassword = $resultRow['removepassword'];
            
            return $chatRoom;
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function UpdateChatRoomUsers($chatRoom)
	{
        try {
            $query = 'UPDATE rooms SET users = ? WHERE id = ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array(json_encode($chatRoom->users), $chatRoom->id));
            
            if($this->db->errorCode() != '00000') 
            {
                die('Error: database error.');
            }
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function GetLastMessages($chatRoomId, $nbMessages) {
        try {
            $query = 'SELECT message, hash, user, date FROM messages WHERE roomid = :id ORDER BY date DESC LIMIT :limit';
            
            $req = $this->db->prepare($query);
			$req->bindValue(":id", $chatRoomId, PDO::PARAM_STR);
			$req->bindValue(":limit", $nbMessages, PDO::PARAM_INT);
			$req->execute();
            
            $messages = array();
            
            while ($line = $req->fetch()) { 
                $chatMessage = new ChatMessage;
                $chatMessage->message = $line['message'];
                $chatMessage->hash = $line['hash'];
                $chatMessage->userId = $line['user'];
                $chatMessage->date = $line['date'];
                array_unshift($messages, $chatMessage);
            } 
            
            return $messages;
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function UpdateChatRoomDateLastMessage($chatRoomId, $time) {
        try {
            $query = 'UPDATE rooms SET lastmessage = ? WHERE id = ?';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($time, $chatRoomId));
            
            if($this->db->errorCode() != '00000') 
            {
                die('Error: database error.');
            }
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function AddMessage($chatRoomId, $message, $userMessage, $hash, $time) {
        try {
            $query = 'INSERT INTO messages (roomid, message, hash, user, date) VALUES (?, ?, ?, ?, ?)';
            
            $req = $this->db->prepare($query);
            
            $req->execute(array($chatRoomId, $message, $hash, $userMessage, $time));
            
            if($this->db->errorCode() != '00000') 
            {
                die('Error: database error.');
            }
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function GetNbChatRooms() {
        try {
            $query = 'SELECT COUNT(id) FROM rooms';
            
            $req = $this->db->prepare($query);
            
            $req->execute();
            
            $result = $req->fetchAll();
            
            if(is_null($result) || count($result) != 1) {
                return -1;
            }
            
            $resultRow = $result[0];
            
            return $resultRow[0];
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
    
    function GetNbMessages() {
        try {
            $query = 'SELECT COUNT(roomid) FROM messages';
            
            $req = $this->db->prepare($query);
            
            $req->execute();
            
            $result = $req->fetchAll();
            
            if(is_null($result) || count($result) != 1) {
                return -1;
            }
            
            $resultRow = $result[0];
            
            return $resultRow[0];
        }
        catch (Exception $e) 
        {
            logException($e);
            die('Error: database error.');
        }
    }
}
