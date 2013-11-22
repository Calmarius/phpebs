<?php

require_once('utils/functions.php');

if (isset($_GET['id']))
{
    $_SESSION['projectId'] = $_GET['id'];
}

$project = runPreparedQuery("CALL getProjectById(?)", $_SESSION['projectId']);
$project = $project[0][0];
$_SESSION['projectName'] = @$project['projectName'];
$result = runPreparedQuery("CALL getProjectTasks(?, ?)", $_SESSION['userId'], $_SESSION['projectId']);
$result = $result[0];
$taskTree = array();
if (!isset($_GET['hid'])) $_GET['hid'] = 0;

// First build the flat tree.
foreach ($result as $task)
{
	$taskTree[$task['id']] = $task;
	$taskTree[$task['id']]['childTasks'] = array();
}

// Then assign childtasks

foreach ($taskTree as $key => $task)
{
	if (isset($taskTree[$task['parentTaskId']]))
	{
		$taskTree[$task['parentTaskId']]['childTasks'][] = $task['id'];
	}
}

// Collect parentless tasks.

$rootTasks = array();
foreach ($taskTree as $task)
{
	if ($task['parentTaskId'] == 0)
	{
		$rootTasks[] = $task;
	}
}

function isAllChildTasksDone($task)
{
    global $taskTree;

    $allDone = true;
    foreach ($task['childTasks'] as $childTaskId)
    {
        assignMetaInfo($taskTree[$childTaskId]);
        $childTask = $taskTree[$childTaskId];
        if (
            (
                (!$childTask['allChildTasksDone'] || !$childTask['hasChildTasks']) && 
                $childTask['status'] != 'done'
            ) || 
            ($childTask['id'] == $_GET['hid'])
        )
        {
            $allDone = false;
        }
    }
    return $allDone;
}

function getChildTasksHeight($task)
{
    global $taskTree;

    $heightNumber = 0;
    foreach ($task['childTasks'] as $childTaskId)
    {
        assignMetaInfo($taskTree[$childTaskId]);
        $childTask = $taskTree[$childTaskId];
        $heightNumber += $childTask['heightNumber'];
    }
    return $heightNumber;    
}

function assignMetaInfo(&$task)
{
    if (isset($task['allChildTasksDone'])) return;
    $task['allChildTasksDone'] = isAllChildTasksDone($task);
    $task['hasChildTasks'] = count($task['childTasks']) > 0;
    $heightNumber = 0;
    if (($task['status'] == done) || (($task['allChildTasksDone']) && ($task['hasChildTasks'])))
    {
        // Done or all subtasks are done.
        $heightNumber = 1;
    }
    else if (!$task['hasChildTasks'])
    {
        // Not done, but don't have subtasks. 
        $heightNumber = 2;
    }
    else
    {
        // have subtasks
        $heightNumber= 2 + getChildTasksHeight($task);
    }
    
    
    $task['heightNumber'] = $heightNumber; // height number is used to show the bigger tasks first (so floating them will use up the screen space more effectively).
}

// Compute meta information about the tasks. (if the have child tasks and if they are all done.)
foreach ($rootTasks as $key => $task)
{
    assignMetaInfo($rootTasks[$key]);
}

// Sort the tasks by their heights.

function heightNumberComparer($a, $b)
{
    return $b['heightNumber'] - $a['heightNumber'];
}

usort($rootTasks, "heightNumberComparer");


$taskStyleMap = array
(
    'stopped' => 'stoppedtask',
    'done' => 'donetask',
    'in progress' => 'inprogresstask',
    'splitted' => 'splittedtask',
    'padding' => 'paddingtask'
);

$statusTextMap = array
(
    'stopped' => 'Stopped',
    'done' => 'Done',
    'in progress' => 'In progress',
    'splitted' => 'Splitted',
    'padding' => 'Padding'
);

$projectStatusMap = array
(
    'planning' => 'Planning',
    'in progress' => 'In progress',
    'done' => 'Done',
);

function echo_title()
{
	$project = runPreparedQuery("CALL getProjectById(?)", $_SESSION['projectId']);
	$project = $project[0];
	echo_escaped($_SESSION['projectName']);
}

function echo_tasks($tasks, $level)
{
    global $taskStyleMap;
    global $taskError;
    global $statusTextMap;
    global $taskTree;
    
    $outdivclass = $level == 0 ? "outertaskdiv" : "";

    foreach ($tasks as $task)
    {
        $allDone = true;
        $hasChildTasks = false;
        $childTasks = array();
       
        foreach ($task['childTasks'] as $taskId)
        {
            $childTask = $taskTree[$taskId];
	        $childTasks[] = $childTask;
        }
        
        $hasChildTasks = $task['hasChildTasks'];
        $allDone = $task['allChildTasksDone'];
        
        $effectiveStatus = $task['status'];
        if ($effectiveStatus == 'splitted' && $hasChildTasks && $allDone) $effectiveStatus = 'done';
        
        ?>
            <div class="taskdiv <?php echo $outdivclass;?> <?php echo_escaped($taskStyleMap[$effectiveStatus]); ?> ">
            	<a id="t<?php echo_escaped($task['id']); ?>"></a>
                <h3>T<?php echo_escaped($task['id']); ?> - <?php echo_escaped($task['summary']); ?> [<?php echo_escaped($statusTextMap[$task['status']]);?>] <a href="javascript:toggle('tsk<?php echo_escaped($task['id']); ?>')"><small>Toggle</small></a></h3>
                <div id="tsk<?php echo_escaped($task['id']); ?>" <?php echo $effectiveStatus == 'done' ? 'style="display: none"' : ''?>>
                    <p>
                        <?php
                            if ($task['status'] != 'splitted')
                            {
                                ?>
                                    Estimate: <?php echo_escaped(secondsToStr((int)$task['estimatedTime'])); ?>, logged: <?php echo_escaped(secondsToStr((int)$task['timeLogged'])); ?>, 
                                <?php
                            }
                        ?>
                    </p>
                    <p><a href="javascript:toggle('ops<?php echo_escaped($task['id']); ?>')">Toggle operations</a> 
                    <?php
                        if ($hasChildTasks)
                        {
                            ?>
                                | <a href="javascript:toggle('subt<?php echo_escaped($task['id']); ?>')">Toggle subtasks</a></p>
                            <?php
                        }
                    ?>
                    <div id="ops<?php echo_escaped($task['id']); ?>" style="display: none">
                        <p>
                            <a class="button" href="?view=createtask&amp;parentid=<?php echo_escaped($task['id']); ?>&amp;pid=<?php echo_escaped($_SESSION['projectId']); ?>">Create subtask</a>
                            <a class="button" href="?view=deletetask&amp;tid=<?php echo_escaped($task['id']); ?>">Delete task</a>
                            <a class="button" href="?view=edittask&amp;tid=<?php echo_escaped($task['id']); ?>">Edit task</a>
                        </p>
                        <form method="post" action="./?hid=<?php echo_escaped($task['id']);?>#t<?php echo_escaped($task['id']);?>">
                            <input type="hidden" name="tid" value="<?php echo_escaped($task['id']); ?>">
                            <?php
                                if ($task['status'] == 'stopped')
                                {
                                    ?>
                                        <button type="submit" name="cmd" value="starttask">Start</button>
                                    <?php
                                }
                                else if ($task['status'] == 'in progress')
                                {
                                    ?>
                                        <button type="submit" name="cmd" value="stoptask">Stop</button>
                                    <?php
                                }
                                else if ($task['status'] == 'done')
                                {
                                    ?>
                                        <button type="submit" name="cmd" value="restarttask">Restart</button>
                                    <?php
                                }
                                if (($task['status'] == 'stopped') || ($task['status'] == 'in progress'))
                                {
                                    ?>
                                        <button type="submit" name="cmd" value="finishtask">Done!</button>
                                    <?php
                                }
                            ?>
                        </form>
                        <p>
                            <small><?php echo_escaped($task['description']); ?></small>
                        </p>
		            </div>
                	<?php
                	    if ($hasChildTasks && $allDone) 
                	    {
                	        ?>
                	            <p>(All subtasks are done)</p>
                	        <?php
                	    }
                	?>		        
	                <div id="subt<?php echo_escaped($task['id']); ?>" <?php echo $hasChildTasks && $allDone ? 'style="display: none"' : ''?>>
	                	<?php
	                		echo_tasks($childTasks, $level + 1);
	                	?>
	                	<div style="clear:both"></div>
	                </div>                
	            </div>
            </div>
        <?php
    }
}

function init_view()
{
    global $SCRIPTS;
    
    $SCRIPTS[] = 'js/actions.js';
}

function echo_content()
{
    global $taskTree;
    global $taskStyleMap;
    global $taskError;
    global $statusTextMap;
    global $project;
    global $projectStatusMap;
    global $rootTasks;
    
	?>	    
	    <p>
	        Project status: <?php echo_escaped($projectStatusMap[$project['status']]); ?>, 
	        <?php
	            if ($project['status'] != 'planning')
	            {
	                ?>
	                    Started at: <?php echo_escaped($project['startedAt']); ?>
	                <?php
	            }
	        ?>
	    </p>
        <p class="alert"><?php echo_escaped(@$taskError); ?></p>
	<?php

	echo_tasks($rootTasks, 0);

	?>
		<p style="clear:both">Options:</p>
		<ul>
			<li><a href="?view=createtask&amp;pid=<?php echo_escaped($_SESSION['projectId']);?>&amp;parentid=0">New task</a></li>
		</ul>
	<?php
}


?>
