<?php

require_once('utils/functions.php');

$project = runPreparedQuery("CALL getProjectById(?)", $_GET['pid']);
$project = @$project[0][0];

$parentTask = runPreparedQuery("CALL getTaskById(?, ?)", $_SESSION['userId'], $_GET['parentid']);
$parentTask = @$parentTask[0][0];

function echo_title()
{
	global $project;

	echo_escaped("New task on project ${project['projectName']}");
}

function echo_content()
{
    global $project;
    global $createTaskError;
    global $parentTask;

	?>
	    <form method="post" action=".">
	        <div class="centerbox">
	            <p class="alert"><?php echo_escaped(@$createTaskError); ?></p>
                <table class="cellborders">
                    <tr>
                        <td>Task name: </td>
                        <td><input type="text" name="name" value="<?php echo_escaped(@$_POST['name']);?>"></td>
                    </tr>
                    <tr>
                        <td>Task description: </td>
                        <td><textarea name="description" rows="5" cols="80"><?php echo_escaped(@$_POST['description']);?></textarea></td>
                    </tr>
                    <tr>
                        <td>Estimate: </td>
                        <td>
                            <input type="text" name="estimate" value="<?php echo_escaped(@$_POST['estimate']); ?>">
                            <small style="display:inline-block">The default unit is hour. You may use the following units: 'h' (for hour) 'm' (for minutes), eg. "5h 34m"</small>
                        </td>
                    </tr>
                </table>
                <p>
                    <input type="submit" value="Create">
                    <input type="hidden" name="cmd" value="createtask">
                    <input type="hidden" name="pid" value="<?php echo_escaped($project['id']); ?>">
                    <input type="hidden" name="parentid" value="<?php echo_escaped($_GET['parentid']); ?>">
                </p>
	        </div>
	    </form>
	<?php
}

?>
