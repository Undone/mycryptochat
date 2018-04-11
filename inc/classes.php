<?php
	class ChatRoom
	{
		public $id;
		public $dateCreation;
		public $users = array();
		public $messages = array();
		public $dateEnd;
		public $noMoreThanOneVisitor;
		public $isRemovable;
		public $removePassword;
		
		public function __construct($id)
		{
			$this->id = $id;
		}
		
		public function addUser($user)
		{
			if (!in_array($user, $this->users))
			{
				array_push($this->users, $user);
			}
		}
		
		public function removeUser($user)
		{
			foreach($this->users as $key => $value)
			{
				if ($user->session == $value->session)
				{
					unset($this->users[$key]);
				}
			}
		}
		
		public function getUsernames()
		{
			$usernames = array();
			
			foreach($this->users as $user)
			{
				array_push($usernames, $user->username);
			}
			
			return $usernames;
		}
	}
	
	class ChatMessage
	{
		public $id;
		public $roomid;
		public $message;
		public $user;
		public $isEvent = false;
		public $date;
	}
	
	class ChatUser
	{
		public $id;
		public $username;
		public $roomid;
		public $lastSeen;
		
		public function __construct($id = null)
		{
			if ($id)
			{
				$this->id 		= $id;
				$this->lastSeen = time();
			}
		}
		
		public function setUsername($username)
		{
			$this->username = $username;
		}
		
		public static function Create($roomid)
		{
			$session = generateRandomHash();
			setcookie($roomid, $session);
		
			$user = new ChatUser($session);
			$user->roomid = $roomid;
			
			return $user;
		}
		
		public static function GetSession($roomid)
		{
			$session = filter_input(INPUT_COOKIE, $roomid);
			
			return $session;
		}
	}