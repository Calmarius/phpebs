<?php

function echo_title()
{
	echo_escaped('Register');
}

function echo_content()
{
	global $regerror;

	?>
		<form method="post" action=".">
			<div class="center">
				<div class="inlinebox">
					<p class="alert"><?php echo_escaped(@$regerror); ?></p>
					<table>
						<tr>
							<td>Name: </td>
							<td><input type="text" name="username" value="<?php echo_escaped(@$_POST['username']); ?>"></td>
						</tr>
						<tr>
							<td>Password: </td>
							<td><input type="password" name="password" value="<?php echo_escaped(@$_POST['password']); ?>"></td>
						</tr>
						<tr>
							<td>Password (again): </td>
							<td><input type="password" name="password2" value="<?php echo_escaped(@$_POST['password2']); ?>"></td>
						</tr>
					</table>
					<p><input type="submit" value="Register"><input type="hidden" name="cmd" value="register"></p>
				</div>
			</div>
		</form>
	<?php
}

?>
