<?php

function echo_title()
{
	echo_escaped('Successful operation');
}

function echo_content()
{
	?>
		<p class="success"><?php echo_op(); ?></p>
	<?php
}

?>
