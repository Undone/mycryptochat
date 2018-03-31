<?php

// modify it: replace 'f-rjng24!1r5TRHHgnjrt' by some random characters
const SEED = 'f-rjng24!1r5TRHHgnjrt';

// number of messages to keep
const NB_MESSAGES_TO_KEEP = 100;

// number of days to delete an idle chat room
const DAYS_TO_DELETE_IDLE_CHATROOM = 60;

const NB_SECONDS_USER_TO_BE_DISCONNECTED = 35;

// choose the type of database you want to use
// MySQL 	= DATABASE_MYSQL
// SQLite 	= DATABASE_SQLITE
const DB_TYPE		= DATABASE_SQLITE;

// mysql database name
const DB_NAME 		= "mycryptochat";

// mysql server address
const DB_HOST 		= "localhost";

// mysql credentials
const DB_USER 		= "mycryptochat";
const DB_PASSWORD 	= "mycryptochat";

$allowedTimes = array(
    5 => '5 minutes',
    30 => '30 minutes',
    60 => '1 hour',
    240 => '4 hours',
    1440 => '1 day',
    10080 => '7 days',
    40320 => '30 days',
    525960 => '1 year',
    0 => 'Unlimited'
);