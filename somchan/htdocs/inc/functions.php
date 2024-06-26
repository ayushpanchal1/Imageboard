<?php

function cleanString($string) {
	return str_replace(array("<", ">", '"'), array("&lt;", "&gt;", "&quot;"), $string);
}

function fancyDie($message, $depth=1) {
	die('<!DOCTYPE html>
<style>
body {color:white;background-color:black;text-align:center;}

span {
	background-color: rgb(40, 43, 45);
	font-size: 2.3em;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	padding:14px;
	border:2px solid #939393;
}
.button {
	background-color: rgb(32, 35, 36);
	font-size: 1.3em;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	padding:14px;
	border:2px solid #3a3a3a;
}
.somhom {
   background-color: black;
   border: none;
   text-align: center;
   font-size: 8em;
   color: #666666;
   position: absolute;
   bottom: 0px;
   left: 25%;
   right: 25%;
}
</style>
<body>
	<br/>
	<span>'.str_replace("\n", '</span><br><br><br><span>', $message).'</span>
	<br/><br/>
    <br><br><a href="javascript:history.go(-'.$depth.')"><span class="button">Return</span></a>
</body>
<br><br><br>
<body><a href="?"><span class="somhom">somchan</span></a></body>
	');
}

function newPost() {
	return array(
		'parent' => '0',
		'timestamp' => '0',
		'bumped' => '0',
		'ip' => '',
		'name' => '',
		'tripcode' => '',
		'email' => '',
		'nameblock' => '',
		'subject' => '',
		'message' => '',
		'password' => '',
		'file' => '',
		'file_hex' => '',
		'file_original' => '',
		'file_size' => '0',
		'file_size_formatted' => '',
		'image_width' => '0',
		'image_height' => '0',
		'thumb' => '',
		'thumb_width' => '0',
		'thumb_height' => '0'
	);
}

function convertBytes($number) {
	$len = strlen($number);
	if ($len <= 3) return sprintf("%dB",     $number);
	if ($len <= 6) return sprintf("%0.2fKB", $number/1024);
	if ($len <= 9) return sprintf("%0.2fMB", $number/1024/1024);
	return sprintf("%0.2fGB", $number/1024/1024/1024);						
}

function nameAndTripcode($name) {
	if (preg_match("/(#|!)(.*)/", $name, $regs)) {
		$cap = $regs[2];
		$cap_full = '#' . $regs[2];
		
		if (function_exists('mb_convert_encoding')) {
			$recoded_cap = mb_convert_encoding($cap, 'SJIS', 'UTF-8');
			if ($recoded_cap != '') {
				$cap = $recoded_cap;
			}
		}
		
		if (strpos($name, '#') === false) {
			$cap_delimiter = '!';
		} elseif (strpos($name, '!') === false) {
			$cap_delimiter = '#';
		} else {
			$cap_delimiter = (strpos($name, '#') < strpos($name, '!')) ? '#' : '!';
		}
		
		if (preg_match("/(.*)(" . $cap_delimiter . ")(.*)/", $cap, $regs_secure)) {
			$cap = $regs_secure[1];
			$cap_secure = $regs_secure[3];
			$is_secure_trip = true;
		} else {
			$is_secure_trip = false;
		}
		
		$tripcode = "";
		if ($cap != "") { // Copied from Futabally
			$cap = strtr($cap, "&amp;", "&");
			$cap = strtr($cap, "&#44;", ", ");
			$salt = substr($cap."H.", 1, 2);
			$salt = preg_replace("/[^\.-z]/", ".", $salt);
			$salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef"); 
			$tripcode = substr(crypt($cap, $salt), -10);
		}
		
		if ($is_secure_trip) {
			if ($cap != "") {
				$tripcode .= "!";
			}
			
			$tripcode .= "!" . substr(md5($cap_secure . TINYIB_TRIPSEED), 2, 10);
		}
		
		return array(preg_replace("/(" . $cap_delimiter . ")(.*)/", "", $name), $tripcode);
	}
	
	return array($name, "");
}

function nameBlock($name, $tripcode, $email, $timestamp, $modposttext) {
	$output = '<span class="postername">';
	$output .= ($name == "" && $tripcode == "") ? "Anonymous" : $name;
	
	if ($tripcode != "") {
		$output .= '</span><span class="postertrip">!' . $tripcode;
	}
	
	$output .= '</span>';
	
	if ($email != "") {
		$output = '<a href="mailto:' . $email . '">' . $output . '</a>';
	}

	return $output . $modposttext . ' ' . date(TINYIB_DATEFORMAT, $timestamp);
}

function _postLink($matches) {
	$post = postByID($matches[1]);
	if ($post) {
		return
			'<a href="?do=thread&id=' .
			($post['parent'] == 0 ? $post['id'] : $post['parent']) .
			'#' . $matches[1] . '">' . $matches[0] . '</a>'
		;
	}
	return $matches[0];
}

function postLink($message) {
	return preg_replace_callback('/&gt;&gt;([0-9]+)/', '_postLink', $message);
}

function colorQuote($message) {
	if (substr($message, -1, 1) != "\n") { $message .= "\n"; }
	return preg_replace('/^(&gt;[^\>](.*))\n/m', '<span class="unkfunc">\\1</span>' . "\n", $message);
}

function deletePostImages($post) {
	if ($post['file'] != '') { @unlink('src/' . $post['file']); }
	if ($post['thumb'] != '') { @unlink('thumb/' . $post['thumb']); }
}

function checkBanned() {
	$ban = banByIP($_SERVER['REMOTE_ADDR']);
	if ($ban) {
		if ($ban['expire'] == 0 || $ban['expire'] > time()) {
			$expire = ($ban['expire'] > 0) ?
				('Your ban will expire ' . date(TINYIB_DATEFORMAT, $ban['expire'])) :
				'The ban on your IP address is permanent and will not expire.'
			;
			$reason = ($ban['reason'] == '') ? 
				'' :
				('<br>The reason provided was: ' . $ban['reason'])
			;
			fancyDie('Sorry, it appears that you have been banned from posting on this image board.  ' . $expire . $reason);
		} else {
			clearExpiredBans();
		}
	}
}

function checkFlood() {
	$lastpost = lastPostByIP();
	if ($lastpost) {
		if ((time() - $lastpost['timestamp']) < TINYIB_RATELIMIT) {
			fancyDie(
				'Please wait a moment before posting again. '.
				' You will be able to make another post in ' .
				(TINYIB_RATELIMIT - (time() - $lastpost['timestamp'])) .
				" second(s)."
			);
		}
	}
}

function checkMessageSize() {
	if (strlen($_POST["message"]) > TINYIB_MAXPOSTSIZE) {
		fancyDie(
			'Your message is ' . strlen($_POST["message"]) . 
			' characters long, but the maximum allowed is '.TINYIB_MAXPOSTSIZE.
			'.<br>Please shorten your message, or post it in multiple parts.'
		);
	}
}

function manageCheckLogIn() {
	$loggedin = false; $isadmin = false;
	if (isset($_POST['password'])) {
		if ($_POST['password'] == TINYIB_ADMINPASS) {
			$_SESSION['tinyib'] = TINYIB_ADMINPASS;
		} elseif (TINYIB_MODPASS != '' && $_POST['password'] == TINYIB_MODPASS) {
			$_SESSION['tinyib'] = TINYIB_MODPASS;
		}
	}
	
	if (isset($_SESSION['tinyib'])) {
		if ($_SESSION['tinyib'] == TINYIB_ADMINPASS) {
			$loggedin = true;
			$isadmin = true;
		} elseif (TINYIB_MODPASS != '' && $_SESSION['tinyib'] == TINYIB_MODPASS) {
			$loggedin = true;
		}
	}
	
	return array($loggedin, $isadmin);
}

function setParent() {
	if (isset($_POST["parent"])) {
		if ($_POST["parent"] != "0") {
			if (!threadExistsByID($_POST['parent'])) {
				fancyDie("Invalid parent thread ID - unable to create post.");
			}			
			return $_POST["parent"];
		}
	}	
	return "0";
}

function validateFileUpload() {
	switch ($_FILES['file']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_FORM_SIZE:
			fancyDie("That file is larger than 2 MB.");
			break;
		case UPLOAD_ERR_INI_SIZE:
			fancyDie("The uploaded file exceeds the upload_max_filesize directive (" . ini_get('upload_max_filesize') . ") in php.ini.");
			break;
		case UPLOAD_ERR_PARTIAL:
			fancyDie("The uploaded file was only partially uploaded.");
			break;
		case UPLOAD_ERR_NO_FILE:
			fancyDie("No file was uploaded.");
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			fancyDie("Missing a temporary folder.");
			break;
		case UPLOAD_ERR_CANT_WRITE:
			fancyDie("Failed to write file to disk");
			break;
		default:
			fancyDie("Unable to save the uploaded file.");
	}
}

function checkDuplicateImage($hex) {
	$hexmatches = postsByHex($hex);
	if (count($hexmatches) > 0) {
		foreach ($hexmatches as $hexmatch) {
			$location = ($hexmatch['parent']=='0') ? $hexmatch['id'] : $hexmatch['parent'];
			fancyDie(
				'Duplicate file uploaded. That file has already been posted '.
				'<a href="?do=thread&id='.$location.'#'.$hexmatch['id'].'">here</a>.'
			);
		}
	}
}

function thumbnailDimensions($width, $height, $is_reply) {
	if ($is_reply) {
		$max_h = TINYIB_REPLYHEIGHT;
		$max_w = TINYIB_REPLYWIDTH;
	} else {
		$max_h = TINYIB_THUMBHEIGHT;
		$max_w = TINYIB_THUMBWIDTH;
	}
	return ($width > $max_w || $height > $max_h) ? array($max_w, $max_h) : array($width, $height);
}

function createThumbnail($name, $filename, $new_w, $new_h) {
	$system = explode(".", $filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/", $system[0])) {
		$src_img = imagecreatefromjpeg($name);
	} else if (preg_match("/png/", $system[0])) {
		$src_img = imagecreatefrompng($name);
	} else if (preg_match("/gif/", $system[0])) {
		$src_img = imagecreatefromgif($name);
	} else {
		return false;
	}
	
	if (!$src_img) {
		fancyDie(
			'Unable to read uploaded file during thumbnailing. '.
			'A common cause for this is an incorrect extension when the '.
			'file is actually of a different type.
		');
	}
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	$percent = ($old_x > $old_y) ? ($new_w / $old_x) : ($new_h / $old_y);
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);
	
	$dst_img = imagecreatetruecolor($thumb_w, $thumb_h);
	imagecopyresampled($dst_img, $src_img, 0,0,0,0, $thumb_w, $thumb_h, $old_x, $old_y);
	
	if (preg_match("/png/", $system[0])) {
		if (!imagepng($dst_img, $filename)) {
			return false;
		}
	} else if (preg_match("/jpg|jpeg/", $system[0])) {
		if (!imagejpeg($dst_img, $filename, 70)) {
			return false;
		}
	} else if (preg_match("/gif/", $system[0])) {
		if (!imagegif($dst_img, $filename)) { 
			return false;
		}
	}
	
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
	
	return true;
}

function redirect($url='?do=page&p=0') {
	header('Location: '.$url);
	die();
}