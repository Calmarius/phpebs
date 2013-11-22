<?php

$_POST['session_name'] = 'phpebs';

function echo_title()
{
	echo_escaped('Install');
}

function echo_content()
{
    if ($INSTALLED)
    {
        ?>
            <p>This is already installed.</p>
        <?php
    }
    else
    {
        ?>
            <form method="post" action=".">
			    <div class="center">
				    <div class="inlinebox">
				        <p>It seems phpEBS is not installed yet. To install it, please populate the form below:</p>
					    <p class="alert"><?php echo_escaped(@$installerror); ?></p>
					    <table>
						    <tr>
							    <td>MySQL account name: </td>
							    <td><input type="text" name="mysql_user" value="<?php echo_escaped(@$_POST['mysql_user']); ?>"></td>
						    </tr>
						    <tr>
							    <td>MySQL password: </td>
							    <td><input type="password" name="mysql_password" value="<?php echo_escaped(@$_POST['mysql_password']); ?>"></td>
						    </tr>
						    <tr>
							    <td>MySQL database name: </td>
							    <td><input type="text" name="mysql_dbname" value="<?php echo_escaped(@$_POST['mysql_dbname']); ?>"></td>
						    </tr>
						    <tr>
							    <td>Admin e-mail (optional): </td>
							    <td><input type="text" name="admin_mail" value="<?php echo_escaped(@$_POST['admin_mail']); ?>"></td>
						    </tr>
						    <tr>
							    <td>PHP session name to use: </td>
							    <td><input type="text" name="session_name" value="<?php echo_escaped(@$_POST['session_name']); ?>"></td>
						    </tr>
					    </table>
					    <p><input type="submit" value="Install"><input type="hidden" name="cmd" value="install"></p>				        
				    </div>
				</div>
                
            </form>
        <?php
    }
}

?>
