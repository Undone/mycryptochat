<?php
	class ChatRoom {
		public $id;
		public $dateCreation;
		public $dateLastNewMessage;
		public $users = array();
		public $messages = array();
		public $dateEnd;
		public $noMoreThanOneVisitor;
		public $isRemovable;
		public $removePassword;
		public $userId;
		
		public function addUser($user)
		{
			array_push($this->users, $user);
		}
	}
	class ChatMessage {
		public $message;
		public $hash;
		public $userId;
		public $date;
	}
