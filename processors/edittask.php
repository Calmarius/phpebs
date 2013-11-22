<?php

require_once('utils/functions.php');

function process()
{
	global $ERROR;
	global $VIEW;
	
	$ERROR = '';

    $seconds =  estimateToSeconds($_POST['estimate']);
    if ($seconds < 0)
    {
        $ERROR = 'Estimate cannot be negative.';
        $VIEW = 'edittask';
        $_GET['tid'] = $_POST['id'];
		return;    	
    }
	$result = runPreparedQuery(
		"CALL editTask(?, ?, ?, ?, ?)", 
		$_SESSION['userId'], 
		$_POST['id'], 
		$_POST['name'],
		$_POST['description'], 
		$seconds
	);
	$result = $result[0];
	if (isset($result['accessdenied']))
	{
		$ERROR = 'Access denied';
        $VIEW = 'edittask';
        $_GET['tid'] = $_POST['id'];
		return;    	
	}
	$result = runPreparedQuery(
        "CALL moveTask(?, ?, ?)",
        $_SESSION['userId'],
        $_POST['parentid'],
        $_POST['id']
	);
	$result = $result[0];
	if (isset($result['accessdenied']))
	{
		$ERROR = 'Access denied (this should not happen.)';
        $VIEW = 'edittask';
        $_GET['tid'] = $_POST['id'];
		return;
	}
	else if (isset($result['failed']))
	{
		$ERROR = 'Failed to set new parent.';
        $VIEW = 'edittask';
        $_GET['tid'] = $_POST['id'];
		return;
	}
	if (trim($_POST['worktolog']) != '')
	{
	    $result = runPreparedQuery(
	        "CALL logWork(?, ?, ?)",
	        $_SESSION['userId'],
	        $_POST['id'],
	        estimateToSeconds($_POST['worktolog'])
	    );
	    $result = $result[0];
	    if (isset($result['accessdenied']))
	    {
		    $ERROR = 'Access denied (this should not happen.)';
            $VIEW = 'edittask';
            $_GET['tid'] = $_POST['id'];
		    return;	        
	    }
	}
	$VIEW = 'project';	
}

?>
