<?php

function echo_title()
{
    echo_escaped('Task deletion');
}

function echo_content()
{
	global $deleteTaskError;

    $task = runPreparedQuery('CALL getTaskById(?, ?)', $_SESSION['userId'], $_GET['tid']);    
    $taskError = '';
    if (!isset($task[0][0]))
    {
    	?>
    		<p class="alert">Task not exists.</p>
    	<?php
    	return;
    }
    $task = $task[0][0];

    
    ?>
        <div class="centerbox">
        	<p class="error"><?php echo_escaped($deleteTaskError); ?></p>
            <p>
            	Do you really want to delete this task?
            </p>
            <table>
            	<tr>
            		<td>Title: </td>
            		<td><h1><?php echo_escaped($task['summary']); ?></h1></td>
            	</tr>
            	<tr>
            		<td>Description: </td>
            		<td><?php echo_escaped($task['description']); ?></td>
            	</tr>
            </table>
            <form method="post" action=".">
            	<p>
	            	<button type="submit" name="cmd" value="deletetask">Delete</button>
	            	<input type="hidden" name="id" value="<?php echo_escaped($_GET['tid']); ?>">
            	</p>
            </form>
        </div>
    <?php
}

?>
