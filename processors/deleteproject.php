<?php

function process()
{
	global $ERROR;
	global $VIEW;

	$result = runPreparedQuery("CALL deleteProject(?, ?, ?)", $_SESSION['userId'], $_POST['id'], $_POST['password']);
	$result = $result[0][0];
	if (isset($result['accessdenied']))
	{
		$VIEW = 'deleteproject';
		$_GET['id'] = $_POST['id'];
		$ERROR = 'Access denied';
		return;
	}
	$VIEW = 'projects';
}

?>
