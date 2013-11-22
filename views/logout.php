<?php

function echo_title()
{
	echo_escaped('Logout');
}

function echo_content()
{
	?>
		<p>Click here, to log out:</p>
		<form method="post" action=".">
			<input type="submit" value="Logout"><input type="hidden" name="cmd" value="logout">
		</form>
	<?php
}

?>
