<?php

require_once('utils/functions.php');

$task = runPreparedQuery("CALL getTaskById(?, ?)", $_SESSION['userId'], $_GET['tid']);
$task = $task[0];
if (!isset($task[0]))
{
	$ERROR = 'Access denied';
	$VIEW = 'project';
	return; 
}
$task = $task[0];

$tasks = runPreparedQuery("CALL getProjectTasks(?, ?)", $_SESSION['userId'], $_SESSION['projectId']);
$tasks = $tasks[0];

function echo_title()
{
	global $task;

	echo_escaped("Editing T${task['id']} ${task['summary']}");
}

function echo_content()
{
	global $task;
	global $tasks;

	?>
		<form class="centerbox" method="post" action="./#t<?php echo_escaped($task['id']);?>">
            <table class="cellborders">
                <tr>
                    <td>Task name: </td>
                    <td><input type="text" name="name" value="<?php echo_escaped($task['summary']);?>"></td>
                </tr>
                <tr>
                    <td>Task description: </td>
                    <td><textarea name="description" rows="5" cols="80"><?php echo_escaped($task['description']);?></textarea></td>
                </tr>
                <tr>
                    <td>Estimate: </td>
                    <td>
                        <input type="text" name="estimate" value="<?php echo_escaped(secondsToStr($task['estimatedTime'])); ?>">
                        <small style="display:inline-block">The default unit is hour. You may use the following units: 'h' (for hour) 'm' (for minutes), eg. "5h 34m"</small>
                    </td>
                </tr>
                <tr>
                	<td>Parent task:</td>
                	<td>
                		<select name="parentid">
                			<option value="0" <?php echo_escaped($task['parentTaskId'] == 0 ? 'selected="selected"' : '');?>>(no parent)</option>
                			<?php
                				foreach ($tasks as $value)
                				{
                					?>
                						<option value="<?php echo_escaped($value['id']); ?>"  <?php echo_escaped($task['parentTaskId'] == $value['id'] ? 'selected="selected"' : '');?>>
                						    T<?php echo_escaped($value['id']);?> <?php echo_escaped($value['summary']); ?>
                						</option>
                					<?php
                				}
							?>                
                		</select>
                	</td>
                </tr>
                <tr>
                	<td>Log work:</td>
                    <td>
                        <input type="text" name="worktolog" value="">
                        <small style="display:inline-block">The default unit is hour. You may use the following units: 'h' (for hour) 'm' (for minutes), eg. "5h 34m". Use negative values to revert work.</small>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" value="Update">
                <input type="hidden" name="cmd" value="edittask">
                <input type="hidden" name="id" value="<?php echo_escaped($task['id']); ?>">
            </p>			
		</form>
	<?php
}

?>
