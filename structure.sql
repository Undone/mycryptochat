
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `roomid` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `user` text NOT NULL,
  `isEvent` tinyint(1) NOT NULL,
  `date` int(11) NOT NULL
) ;

CREATE TABLE `rooms` (
  `id` varchar(20) NOT NULL,
  `created` int(11) NOT NULL,
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
  ADD PRIMARY KEY (`id`),
  ADD KEY `roomid` (`roomid`);

ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `roomid` (`roomid`);


ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
