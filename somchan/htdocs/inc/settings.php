<?php

// Board Settings
// ``````````````

// Display
define('TINYIB_PAGETITLE', 'somchan');
define('TINYIB_LOGO', @file_get_contents("inc/header.html"));

// Thread management
define('TINYIB_THREADSPERPAGE', 7);
define('TINYIB_REPLIESTOSHOW',  3);
define('TINYIB_MAXTHREADS',     35);    // 0 disables deleting old threads
define('TINYIB_DELETE_TIMEOUT', 300);  // Seconds for deleting own posts
define('TINYIB_MAXPOSTSIZE',    8000); // Characters
define('TINYIB_RATELIMIT',      10);   // Delay between posts from same IP

// Passwords
define('TINYIB_ADMINPASS',  "2022somchanadminpass1"); 
define('TINYIB_MODPASS',    ""); // Leave blank to disable
define('TINYIB_TRIPSEED',   "secudretsfarips"); 

// Captcha settings
define('TINYIB_USECAPTCHA',   false);
define('TINYIB_CAPTCHASALT',  'captchasalt');

// Thumbnail dimensions
define('TINYIB_THUMBWIDTH',  200);
define('TINYIB_THUMBHEIGHT', 300);
define('TINYIB_REPLYWIDTH',  85);
define('TINYIB_REPLYHEIGHT', 85);

// Date style
define('TINYIB_TIMEZONE',   'Asia/Kolkata'); // Leave blank to use server default timezone
define('TINYIB_DATEFORMAT', 'D d-M-y g:ia');

// RSS options
define('RSS_POSTS_PER_REQUEST',  5);
define('RSS_LANGUAGE',           'en-GB');
define('RSS_AUTHOR_EMAIL',       'rss@example.com');
