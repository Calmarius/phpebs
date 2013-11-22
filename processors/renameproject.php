<?php

function process()
{
	global $ERROR;
	
	$result = runPreparedQuery(
		"CALL setProjectName(?, ?, ?)",
		 $_SESSION['userId'], 
		 $_POST['pid'], 
		 $_POST['newname']
	);
	$result = $result[0][0];
	
	if (isset($result['accessdenied']))
	{
		$ERROR = 'Sorry, access denied!';
		$VIEW = 'renameproject';
		$_GET['id'] = $_POST['pid'];
	}
	else if (isset($result['success']))
	{
		$VIEW= 'projects';
	}
	else
	{
		$ERROR = 'Something unknown happened here.';
		$VIEW = 'renameproject';
		$_GET['id'] = $_POST['pid'];		
	}
	
}

?>
