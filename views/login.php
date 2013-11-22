<?php

function echo_title()
{
	echo_escaped('Login');
}

function echo_content()
{
	global $loginError;

	?>
		<form method="post" action="."  class="centerblock">	
			<div class="center">
				<div class="inlinebox">
					<p class="alert"><?php echo_escaped(@$loginError); ?></p>
					<table>
						<tr> 
							<td>Username: </td>
							<td><input type="text" name="username" value="<?php echo_escaped(@$_POST['username']); ?>"></td>
						</tr>
						<tr>
							<td>Password: </td>
							<td><input type="password" name="password" value="<?php echo_escaped(@$_POST['password']); ?>"></td>
						</tr>
					</table>
					<p><input type="submit" value="Login"><input type="hidden" name="cmd" value="login"></p>
				</div>
			</div>
		</form>
	<?php
}

?>
