CREATE TABLE `messages` (
  `roomid` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `user` text NOT NULL,
  `isEvent` tinyint(1) NOT NULL,
  `date` int(11) NOT NULL
) ;

CREATE TABLE `rooms` (
  `id` varchar(20) NOT NULL,
  `created` int(11) NOT NULL,
  `lastmessage` int(11) NOT NULL,
  `expire` int(11) NOT NULL,
  `singleuser` tinyint(1) NOT NULL,
  `removable` tinyint(1) NOT NULL,
  `removepassword` text NOT NULL
) ;

CREATE TABLE `sessions` (
  `id` varchar(64) NOT NULL,
  `roomid` varchar(20) NOT NULL,
  `username` text NOT NULL,
  `lastSeen` int(11) NOT NULL
) ;


ALTER TABLE `messages`
  ADD KEY `roomid` (`roomid`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);