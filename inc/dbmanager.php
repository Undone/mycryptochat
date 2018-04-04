<?php
class DbManager
{
    private $db;
    
    public function __construct()
	{
        try
		{
			if (DB_TYPE == DATABASE_SQLITE)
			{
				$this->db = new PDO('sqlite:' . DB_FILE_NAME);
				//$this->db->setAttribute(PDO::ATTR_PERSISTENT, true);
				//$this->db->exec('PRAGMA temp_store = MEMORY; PRAGMA synchronous=OFF;');
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
	
	public function deleteUser($user)
	{
		$query = "DELETE FROM sessions WHERE id = ?";
		
		$req = $this->db->prepare($query);
		$req->bindParam(1, $user->id, PDO::PARAM_STR);
		$req->execute();
	}
	
	public function saveUser($user)
	{
		$query 	= "INSERT INTO sessions (id, roomid, username, lastSeen) VALUES (?, ?, ?, ?)";
		$req 	= $this->db->prepare($query);
		
		$req->execute(array(
			$user->id,
			$user->roomid,
			$user->username,
			$user->lastSeen
		));
	}
	
	public function getUser($session)
	{
		if (!$session)
		{
			return null;
		}
		
		$query 	= "SELECT * FROM sessions WHERE id = ?";
		$req 	= $this->db->prepare($query);
		$req->bindParam(1, $session, PDO::PARAM_STR);
		$req->execute();
		
		$result = $req->fetchAll(PDO::FETCH_CLASS, "ChatUser");
		
		if (!$result)
		{
			return null;
		}
		
		return $result[0];
	}
    
    public function createChatroom($chatRoom)
	{
		$query = 'INSERT INTO rooms (id, created, lastmessage, expire, singleuser, removable, removepassword) VALUES (?, ?, ?, ?, ?, ?, ?)';
		
		$req = $this->db->prepare($query);

		$req->execute(array(
			$chatRoom->id,
			$chatRoom->dateCreation,
			$chatRoom->dateLastNewMessage,
			$chatRoom->dateEnd,
			$chatRoom->noMoreThanOneVisitor ? 1 : 0,
			$chatRoom->isRemovable ? 1 : 0,
			$chatRoom->removePassword
		));
    }
    
    public function cleanChatrooms()
	{
		$time 		= time();
		$idleTime 	= $time - (DAYS_TO_DELETE_IDLE_CHATROOM * 24 * 60 * 60);
        
		// Delete messages
		$query = 'DELETE FROM messages WHERE roomid IN (SELECT id FROM rooms WHERE ( expire <> 0 AND expire < ? ) OR lastmessage < ?)';
		
		$req = $this->db->prepare($query);
		$req->execute(array($time, $idleTime));
		
		// Delete sessions
		$query = 'DELETE FROM sessions WHERE roomid IN (SELECT id FROM rooms WHERE ( expire <> 0 AND expire < ? ) OR lastmessage < ?)';
		
		$req = $this->db->prepare($query);
		$req->execute(array($time, $idleTime));
		
        // Delete rooms
		$query = 'DELETE FROM rooms WHERE ( expire <> 0 AND expire < ? ) OR lastmessage < ?';
        
		$req = $this->db->prepare($query);
		$req->execute(array($time, $idleTime));
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
    
    public function GetChatroom($id)
	{
		$query = 'SELECT created, lastmessage, expire, singleuser, removable, removepassword FROM rooms WHERE id = ?';
		$req = $this->db->prepare($query);
		$req->bindParam(1, $id, PDO::PARAM_STR);
		$req->execute();
		$result = $req->fetchAll();
		
		// Room doesn't exist, or couldn't be retrieved
		if (!$result || count($result) == 0)
		{
			return;
		}
        
		$resultRow = $result[0];
        
		$chatRoom 						= new ChatRoom($id);
		$chatRoom->dateCreation 		= $resultRow['created'];
		$chatRoom->dateLastNewMessage 	= $resultRow['lastmessage'];
		$chatRoom->dateEnd 				= $resultRow['expire'];
		$chatRoom->noMoreThanOneVisitor = $resultRow['singleuser'] == 1;
		$chatRoom->isRemovable 			= $resultRow['removable'] == 1;
		$chatRoom->removePassword 		= $resultRow['removepassword'];
		
		// Get chatroom users
		$query = "SELECT id, roomid, username, lastSeen FROM sessions WHERE roomid = ?";
		
		$req = $this->db->prepare($query);
		$req->bindParam(1, $id, PDO::PARAM_STR);
		$req->execute();
		$users = $req->fetchAll(PDO::FETCH_CLASS, "ChatUser");
		
		$chatRoom->users = $users;
        
		return $chatRoom;
    }
	
	public function updateLastSeen($user)
	{
		$query = "UPDATE sessions SET lastSeen = ? WHERE id = ?";
		$req = $this->db->prepare($query);
		
		$req->execute(array(
			time(),
			$user->id
		));
	}
    
    function GetLastMessages($chatRoomId, $nbMessages)
	{
		$query = 'SELECT message, user, isEvent, date FROM messages WHERE roomid = :id ORDER BY date DESC LIMIT :limit';

		$req = $this->db->prepare($query);
		$req->bindValue(":id", $chatRoomId, PDO::PARAM_STR);
		$req->bindValue(":limit", $nbMessages, PDO::PARAM_INT);
		$req->execute();
		
		$messages = $req->fetchAll(PDO::FETCH_CLASS, "ChatMessage");
		
		// We want to get the latest messages but we also need to display them in the order they've been sent
		$messages = array_reverse($messages);

		return $messages;
    }
    
    public function addMessage($roomid, $chatMessage)
	{
		$query = 'INSERT INTO messages (roomid, message, user, isEvent, date) VALUES (?, ?, ?, ?, ?)';

		$req = $this->db->prepare($query);
		$req->execute(array(
			$roomid,
			$chatMessage->message,
			$chatMessage->user,
			$chatMessage->isEvent,
			$chatMessage->date
		));
		
		// Update timestamp on last sent message
		$query = 'UPDATE rooms SET lastmessage = ? WHERE id = ?';
		
		$req = $this->db->prepare($query);
		$req->execute(array(
			time(),
			$roomid
		));
    }
	
	public function addEventMessage($user, $message)
	{
		$eventMessage 			= new ChatMessage();
		$eventMessage->message	= $message;
		$eventMessage->user 	= $user->username;
		$eventMessage->date		= time();
		$eventMessage->isEvent	= true;
		
		$this->addMessage($user->roomid, $eventMessage);
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
