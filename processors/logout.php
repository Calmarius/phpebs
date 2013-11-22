<?php

function process()
{
	global $VIEW;

    session_destroy();
    $_SESSION = array();
	$VIEW = 'login';
}


?>
