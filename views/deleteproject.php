<?php

$access = runPreparedQuery("SELECT doesUserHaveTheProject(?, ?) AS allowed", $_SESSION['userId'], $_GET['id']);
$access = $access[0][0]['allowed'];

if (!(int)$access)
{
	$ERROR = 'Access denied';
	return;
}

function echo_title()
{
	echo_escaped('Deleting project');
}

function echo_content()
{
	$project = runPreparedQuery("CALL getProjectById(?)", $_GET['id']);
	$project = $project[0][0];
	
	?>
		<div class="centerbox">
			<p>Do you really want to delete <b><?php echo_escaped($project['projectName']);?>?</b></p>
			<form method="post" action=".">
				<table>
					<tr>
						<td>Confirm with password: </td>
						<td><input type="password" name="password"></td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="submit" value="Delete">
							<input type="hidden" name="cmd" value="deleteproject">
							<input type="hidden" name="id" value="<?php echo_escaped($_GET['id']); ?>">
						</td>
					</tr>
				</table>
			</form>
		</div>
	<?php
	

	return;
}

?>
