<?php

$regerror = '';

function process()
{
    global $DB_LINK;
    global $VIEW;
  	global $regerror;
  	

	if ($_POST['password'] != $_POST['password2'])
	{
		$VIEW = 'register';
		$regerror = 'Two passwords does not match';
		return;
	}
	
	$result = runPreparedQuery('CALL registerNewUser(?, ?)', $_POST['username'], $_POST['password']);
	$result = $result[0][0];
	
    if (isset($result['success']))
    {
    	$VIEW = 'opsuccess';
    	function echo_op()
    	{
    		echo_escaped('Registration was successful. Now you can login.');
    	}
    }
    else if (isset($result['alreadyexist']))
    {
    	$VIEW = 'register';
		$regerror = 'This user is already registered';
		return;    	
    }
    else
    {
    	$VIEW = 'register';
		$regerror = 'Unknown error';
		return;    	
    }
  	
}

?>
