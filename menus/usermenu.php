<?php

function echo_menu()
{
	?>
		Logged in as <?php echo_escaped($_SESSION['userName']); ?>
		<a href="?view=logout">Logout</a>
		<a href="?view=projects">Projects</a>
		<?php
		    if (isset($_SESSION['projectId']))
		    {
		        ?>
            		<a href="?view=project">Tasks</a>
            		<a href="?view=prognosis">Prognosis</a>
		        <?php
		    }
		?>
	<?php
}

?>
