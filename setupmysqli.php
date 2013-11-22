<?php

require_once('shadow.php');

$DB_LINK = null;
$VAR_COUNTER = 0;

function ensureConnection()
{
	global $DB_LINK;

	if ($DB_LINK) return;

	$DB_LINK = mysqli_connect("127.0.0.1", __MYSQL_USER_NAME, __MYSQL_PASSWORD);
	if (!$DB_LINK) 
	{
		die('Failed to connect database server.');
	}
	if (!mysqli_select_db($DB_LINK, __MYSQL_DB_NAME))
	{
		die('Failed to select database.');    
	}

	mysqli_set_charset($DB_LINK, 'utf8') or die('Failed to set charset');

}

function runQueryEx($query, $errp, $queryp = 'mysqli_real_query', $additonalInfo = '')
{
	global $DB_LINK;
	global $MYSQL_TRACE;
	$results = array();
	$index = 0;
	
	ensureConnection();
	
	if ($MYSQL_TRACE === true)
	{
		echo $query;
	}
	
	$r = $queryp($DB_LINK, $query);
	if ($r === FALSE)
	{
        $errp(mysqli_error($DB_LINK) . "\n Your query was: " . $query . "Additional Info: " . $additonalInfo);
        return;
	}
	for ($result = mysqli_use_result($DB_LINK); $result; mysqli_next_result($DB_LINK), $result = mysqli_use_result($DB_LINK))
	{
		$results[$index] = array();
		while ($row = mysqli_fetch_assoc($result))
		{
			$results[$index][] = $row;
		}
		$index++;
	}
	
	return $results;
}

function doDie($str)
{
    die($str);
}

function runQuery($query, $additonalInfo = '')
{
    return runQueryEx($query, 'doDie', 'mysqli_real_query', $additonalInfo);
}

function runPreparedQuery($preparedQuery /*, ...*/)
{
	global $DB_LINK;
	global $VAR_COUNTER;

	$params = array_slice(func_get_args(), 1);
	$preparedVars = array();
	runQuery("PREPARE pq FROM '$preparedQuery'");
	foreach ($params as $value)
	{
		if (is_string($value))
		{
			runQuery("SET @v$VAR_COUNTER = " . "'" . mysqli_real_escape_string($DB_LINK, $value) . "'");
		}
		else if (is_null($value))
		{
			runQuery("SET @v$VAR_COUNTER = NULL");
		}
		else
		{
			runQuery("SET @v$VAR_COUNTER = $value");
		}
		$preparedVars[] = "@v$VAR_COUNTER";
		$VAR_COUNTER++;
	}
	$execCmd = 'EXECUTE pq USING ' . implode(', ', $preparedVars);
	$results = runQuery($execCmd, $preparedQuery);
	runQuery("DEALLOCATE PREPARE pq");
	
	return $results;
	
}

?>
