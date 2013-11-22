<?php

function echo_title()
{
	echo_escaped('Projects');
}

function echo_content()
{
	$result = runPreparedQuery("CALL listUserProjects(?)", $_SESSION['userId']);
	$result = $result[0];
	
	?>
		<p>Your projects: </p>
		<table>
		<?php
		foreach ($result as $project)
		{
			?>
				<tr>
					<td><a href="?view=project&amp;id=<?php echo_escaped($project['id']); ?>"><?php echo_escaped($project['projectName']); ?></a></td>
					<?php
						if ($project['ownerId'] == $_SESSION['userId'])
						{
							?>
								<td><a class="button" href="?view=deleteproject&amp;id=<?php echo_escaped($project['id']); ?>">Delete</a></td>
								<td><a class="button" href="?view=renameproject&amp;id=<?php echo_escaped($project['id']); ?>">Rename</a></td>
							<?php
						}
					?>
				<tr>
			<?php
		}
		?>
		</table>
		<p>Options:</p>
		<ul>
			<li><a class="button" href="?view=createnewproject">Create new project</a></li>
		</ul>
	<?php
}

?>
