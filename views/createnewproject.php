<?php

function echo_title()
{
	echo_escaped('Create new project');
}

function echo_content()
{
    global $createProjectError;

	?>
		<form method="post" action=".">
			<p class="alert"><?php echo_escaped(@$createProjectError);?></p>
			<table>
				<tr>
					<td>Project name: </td>
					<td><input type="text" name="projectname" value="<?php echo_escaped(@$_POST['projectname'])?>"></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="Create"><input type="hidden" name="cmd" value="createproject"></td>
				</tr>
			</table>
		</form>
	<?php
}

?>
