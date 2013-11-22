<?php

function setTaskStatus($procedureName)
{
	global $taskError;
	global $VIEW;

	$result = runPreparedQuery("CALL $procedureName(?, ?)", $_SESSION['userId'], $_POST['tid']);
	$result = $result[0][0];
	
	if (!isset($result['success']))
	{
		$taskError = 'Failed to set task status access denied.';
	}
	$VIEW = 'project';
}

?>
