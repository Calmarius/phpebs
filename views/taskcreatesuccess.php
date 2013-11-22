<?php

function echo_title()
{
	echo_escaped('Task creation successful.');
}

function echo_content()
{
	?>
		<p class="success">Click <a href="?view=project#t<?php echo_escaped($_GET['id']); ?>">here</a> to highlight it.</p>
	<?php
}

?>
