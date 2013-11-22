<?php

require_once("utils/functions.php");

function process()
{
    global $VIEW;
    global $createTaskError;

    $seconds =  estimateToSeconds($_POST['estimate']);
    if ($seconds < 0)
    {
        $createTaskError = 'Estimate cannot be negative.';
        $VIEW = 'createtask';
		return;    	
    }
    $result = runPreparedQuery(
        "CALL createNewTask(?, ?, ?, ?, ?, ?) ",
        $_SESSION['userId'],
        $_POST['pid'],
        $_POST['parentid'],
        $_POST['name'],
        $_POST['description'],
        $seconds
    );
    
    $result = $result[0][0];
    
    if (isset($result['success']))
    {
        $VIEW = 'taskcreatesuccess';
        $_GET['id'] = $result['id'];
    }
    else if (isset($result['accessdenied']))
    {
        $createTaskError = 'You have no permission to this. (Maybe you need to login again.)';
        $VIEW = 'createtask';
    }
}

?>
