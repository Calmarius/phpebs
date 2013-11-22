<?php

function process()
{
	global $VIEW;
	global $loginError;
	
	$result = runPreparedQuery("CALL authenticateUser(?, ?)", $_POST['username'], $_POST['password']);
	$result = $result[0][0];
	
	if (!isset($result['success']))
	{
		$VIEW = 'login';
		$loginError = "You probably mistyped something... Try again.";
	}
	else
	{
		$VIEW = 'opsuccess';
		$_SESSION['userId'] = $result['userId'];
		$_SESSION['userName'] = $result['uName'];
		function echo_op()
		{
			echo_escaped('Successful login, now you can manage your projects.');
		}
	}
	
}


?>
