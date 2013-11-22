<?php

function process()
{
    global $DB_LINK;
    global $VIEW;
    global $createProjectError;
    
    $result = runPreparedQuery("CALL createNewProject(?, ?)", $_SESSION['userId'], $_POST['projectname']);
    $result = $result[0][0];
    
    if (isset($result['success']))
    {
        $VIEW = 'projects';
    }
    else
    {
        $VIEW = 'createproject';
        $createProjectError = 'Failed to create the project, this should not happen.';
    }
}

?>
