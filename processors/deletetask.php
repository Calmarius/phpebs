<?php

function process()
{
	global $VIEW;
	global $deleteTaskError;

	$result = runPreparedQuery("CALL deleteTask(?, ?)", $_SESSION['userId'], $_POST['id']);
	$result = $result[0][0];
	
	if (isset($result['success']))
	{
		$VIEW = 'project';
	}
	else if (isset($result['accessdenied']))
	{
		$VIEW = 'deletetask';
		$_GET['tid'] = $_POST['id'];
		$deleteTaskError = 'No permissions to delete this task.';
	}
}

?>
