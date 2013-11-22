<?php

function echo_title()
{
	echo_escaped('Project renaming');
}

function echo_content()
{
	$authentication = runPreparedQuery("SELECT doesUserHaveTheProject(?, ?) AS allowed", $_SESSION['userId'], $_GET['id']);
	if (!(int)$authentication[0][0]['allowed'])
	{
		?>
			<p class="alert">Access denied.</p>
		<?php
		return;
	}
	

	$project = runPreparedQuery("CALL getProjectById(?)", $_GET['id']);
	$project = $project[0];

	if (count($project))
	{
		$project = $project[0];
		?>
			<form class="centerbox" method="post" action=".">
				<table>
					<tr>
						<td>New project name: </td>
						<td><input type="text" name="newname" value="<?php echo_escaped($project['projectName']); ?>"></td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="submit" value="Rename">
							<input type="hidden" name="cmd" value="renameproject">
							<input type="hidden" name="pid" value="<?php echo_escaped($_GET['id']); ?>">
						</td>
					</tr>
				</table>
			</form>
		<?php
	}
	else
	{
		?>
			<p class="alert">Project does not exist.</p>
		<?php
	}
}

?>
