<?php

$INSTALLED = file_exists('installed');

if ($INSTALLED)
{
    require_once('shadow.php');
    require_once('setupmysqli.php');
}

session_name(__SESSION_NAME);
session_start();

if (get_magic_quotes_gpc()) 
{
    function magicQuotes_awStripslashes(&$value, $key) {$value = stripslashes($value);}
    $gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    array_walk_recursive($gpc, 'magicQuotes_awStripslashes');
}


$COMMANDS = array
(
    'createproject' => true,
    'createtask' => true,
	'deleteproject' => true,
	'deletetask' => true,
	'edittask' => true,
    'finishtask' => true,
	'install' => true,
    'login' => true,
    'logout' => true,
    'register' => true,
	'renameproject' => true,
    'restarttask' => true,
    'starttask' => true,
    'stoptask' => true,
);

$PAGES = array
(
    'createnewproject' => true,
    'createtask' => true,
    'deleteproject' => true,
    'deletetask' => true,
    'edittask' => true,
    'install' => true,
    'login' => true,
    'logout' => true,
    'opsuccess' => true,
    'prognosis' => true,
    'projects' => true,
    'project' => true,
    'register' => true,
    'renameproject' => true,
    'taskcreatesuccess' => true
);

$SCRIPTS = array();

function echo_escaped($str)
{
    echo htmlspecialchars($str);
}

function echo_scripts()
{
    global $SCRIPTS;
    
    foreach ($SCRIPTS as $value)
    {
        ?>
            <script type="text/javascript" src="<?php echo_escaped($value);?>"></script>
        <?php
    }
}

function echo_error()
{
	global $ERROR;
	
	if ($ERROR != '')
	{
		?>
			<p class="alert"><?php echo_escaped($ERROR); ?></p>
		<?php
		$ERROR = '';
	}
}

if (!$INSTALLED)
{
    $VIEW = 'install';
}
else
{
    if (!isset($_GET['view']))
    {
	    $VIEW = 'login';
	    if (!isset($_SESSION['userId']))
	    {
		    $VIEW = 'login';
	    }
	    else
	    {
		    $VIEW = 'projects';
	    }
    }
    else
    {
	    $VIEW = $_GET['view'];
    }
}

if (isset($_POST['cmd']) && isset($COMMANDS[$_POST['cmd']])) 
{
    require('processors/'.$_POST['cmd'].'.php');
    process();
}

if (!$INSTALLED)
{
    require('menus/emptymenu.php');
}
else
{
    if (!isset($_SESSION['userId']))
    {
	    require('menus/guestmenu.php');
    }
    else
    {
	    require('menus/usermenu.php');
    }
}


if (!isset($PAGES[$VIEW]))
{
	header('Location: index.php');
	die();
}

require('views/'.$VIEW.'.php');

/********************************/

require('templates/main.php');

?>
