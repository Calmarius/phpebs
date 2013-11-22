<?php 
    header('Content-type: text/html; charset=utf-8');
    if (function_exists('init_view')) init_view();
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo_title(); ?> - phpEBS</title> 
        <link rel="stylesheet" href="main.css" type="text/css">
        <meta http-equiv="Content-type" content="text/html; charset=utf-8">
        <?php
            echo_scripts();
        ?>
    </head>
    <body>
    	<div id="heading">
	    	<h1><?php echo_title(); ?> - <a href="index.php">phpEBS</a></h1>
    	</div>
    	<div id="menu">
    		<?php echo_menu(); ?>
    	</div>
    	<?php echo_error(); ?>
        <?php echo_content(); ?>
        <div id="footer">by Calmarius</div>
    </body>
</html>
