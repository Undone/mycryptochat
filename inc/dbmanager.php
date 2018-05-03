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
	
	public function deleteUser(ChatUser $user)
	{
		$query = "DELETE FROM sessions WHERE id = ?";
		
		$req = $this->db->prepare($query);
		$req->bindParam(1, $user->id, PDO::PARAM_STR);
		$req->execute();
	}
	
	public function saveUser(ChatUser $user)
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
	
	public function getUser(string $session = null)
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
	
	public function createChatroom(ChatRoom $chatRoom)
	{
		$query = 'INSERT INTO rooms (id, created, expire, singleuser, removable, removepassword) VALUES (?, ?, ?, ?, ?, ?)';
		
		$req = $this->db->prepare($query);

		$req->bindParam(1, $chatRoom->id, PDO::PARAM_STR);
		$req->bindParam(2, $chatRoom->dateCreation, PDO::PARAM_INT);
		$req->bindParam(3, $chatRoom->dateEnd, PDO::PARAM_INT);
		$req->bindParam(4, $chatRoom->noMoreThanOneVisitor, PDO::PARAM_BOOL);
		$req->bindParam(5, $chatRoom->isRemovable, PDO::PARAM_BOOL);
		$req->bindParam(6, $chatRoom->removePassword, PDO::PARAM_STR);
		$req->execute();

		return $req->rowCount();
	}
	
	public function cleanChatrooms()
	{
		$time 		= time();
		$idleTime 	= $time - (DAYS_TO_DELETE_IDLE_CHATROOM * 24 * 60 * 60);
		
		// Delete expired rooms
		$query = "DELETE FROM rooms WHERE rooms.expire > 0 AND rooms.expire < :time";
		$req = $this->db->prepare($query);
		$req->bindValue(":time", $time, PDO::PARAM_INT);
		$req->execute();
		
		// Delete inactive rooms
		$query = "DELETE FROM rooms WHERE id IN (SELECT roomid FROM messages WHERE date < :idletime ORDER BY date DESC)";
		$req = $this->db->prepare($query);
		$req->bindValue(":idletime", $idleTime, PDO::PARAM_INT);
		$req->execute();
		
		// Delete messages which don't have a room anymore
		$query = "DELETE FROM messages WHERE roomid NOT IN (SELECT id FROM rooms)";
		$req = $this->db->prepare($query);
		$req->execute();
		
		// Delete sessions which don't have a room anymore
		$query = "DELETE FROM sessions WHERE roomid NOT IN (SELECT id FROM rooms)";
		$req = $this->db->prepare($query);
		$req->execute();
	}
	
	public function deleteChatroom(string $roomid)
	{
		$query = "DELETE FROM sessions WHERE roomid = :roomid";
		$req = $this->db->prepare($query);
		$req->bindValue(":roomid", $roomid, PDO::PARAM_STR);
		$req->execute();
		
		$query = "DELETE FROM messages WHERE roomid = :roomid";
		$req = $this->db->prepare($query);
		$req->bindValue(":roomid", $roomid, PDO::PARAM_STR);
		$req->execute();
		
		$query = "DELETE FROM rooms WHERE id = :roomid";
		$req = $this->db->prepare($query);
		$req->bindValue(":roomid", $roomid, PDO::PARAM_STR);
		$req->execute();
	}
	
	public function getChatroom(string $id)
	{
		$query = 'SELECT created, expire, singleuser, removable, removepassword FROM rooms WHERE id = ?';
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
	
	public function updateLastSeen(ChatUser $user)
	{
		$query = "UPDATE sessions SET lastSeen = ? WHERE id = ?";
		$req = $this->db->prepare($query);
		
		$req->execute(array(
			time(),
			$user->id
		));
	}
	
	public function getLastMessages(string $roomid, int $maxMessages, int $maxId = 0)
	{
		$query = 'SELECT * FROM messages WHERE roomid = :id AND id > :maxid ORDER BY date DESC LIMIT :limit';

		$req = $this->db->prepare($query);
		$req->bindValue(":id", $roomid, PDO::PARAM_STR);
		$req->bindValue(":maxid", $maxId, PDO::PARAM_INT);
		$req->bindValue(":limit", $maxMessages, PDO::PARAM_INT);
		$req->execute();
		
		$messages = $req->fetchAll(PDO::FETCH_CLASS, "ChatMessage");
		
		// We want to get the latest messages but we also need to display them in the order they've been sent
		$messages = array_reverse($messages);

		return $messages;
	}
	
	public function addMessage(ChatMessage $chatMessage)
	{
		$query = 'INSERT INTO messages (roomid, message, user, isEvent, date) VALUES (?, ?, ?, ?, ?)';

		$req = $this->db->prepare($query);
		
		$req->bindParam(1, $chatMessage->roomid, PDO::PARAM_STR);
		$req->bindParam(2, $chatMessage->message, PDO::PARAM_STR);
		$req->bindParam(3, $chatMessage->user, PDO::PARAM_STR);
		$req->bindParam(4, $chatMessage->isEvent, PDO::PARAM_BOOL);
		$req->bindParam(5, $chatMessage->date, PDO::PARAM_INT);
		$req->execute();

		return $req->rowCount();
	}
	
	public function addEventMessage(ChatUser $user, string $message)
	{
		$eventMessage 			= new ChatMessage();
		$eventMessage->roomid	= $user->roomid;
		$eventMessage->message	= $message;
		$eventMessage->user 	= $user->username;
		$eventMessage->date		= time();
		$eventMessage->isEvent	= true;
		
		return $this->addMessage($eventMessage);
	}
	
	public function countChatrooms()
	{
		$query = "SELECT COUNT(id) FROM rooms";
		$req = $this->db->prepare($query);
		$req->execute();
		
		return $req->fetchColumn();
	}
	
	public function countMessages()
	{
		$query = "SELECT COUNT(roomid) FROM messages";
		$req = $this->db->prepare($query);
		$req->execute();
		
		return $req->fetchColumn();
	}
}