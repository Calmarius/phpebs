<?php

require_once('utils/functions.php');

function echo_title()
{
    echo_escaped('Prognosis - '.$_SESSION['projectName']);
}

$pid = $_SESSION['projectId'];
$nSamples = 1000;
$nDensitySamples = $nSamples;
$movingAverageRadius = $nDensitySamples / 20;
$recNSamples = 1 / $nSamples;

$nRemainingTasks = runPreparedQuery("CALL getRemainingTaskCount(?)", $pid);
$nRemainingTasks = $nRemainingTasks[0][0]['remainingTasks'];

$speeds = runPreparedQuery("CALL getSpeedsOfDoneTasks(?)", $pid);
$speeds = $speeds[0];
//var_dump($speeds);
$nSpeeds = count($speeds);
if ($nSpeeds == 0)
{
    $speeds[0]['speed'] = 1;
    $averageSpeed = 1;
}
else
{
    $averageSpeed = 0;
    foreach ($speeds as $speed)
    {
	    $averageSpeed += $speed['speed'];
    }
    $averageSpeed /= $nSpeeds;
}

//var_dump($nSpeeds);
$pendingTasks = runPreparedQuery("CALL getPendingTasks(?)", $pid);
$pendingTasks = $pendingTasks[0];
//var_dump($pendingTasks);
if (count($pendingTasks) == 0)
{
    function echo_content()
    {
        ?><p class="alert">There are no pending tasks, the project is considered to be complete.</p><?php
    }
    return;    
}
$loggedTime = runPreparedQuery("CALL getLoggedTime(?)", $pid);
$loggedTime = (int)$loggedTime[0][0]['totalLogged'];
//var_dump($loggedTime);
if ($loggedTime == 0)
{
    function echo_content()
    {
        ?><p class="alert">No time logged so far, time utilization (thus the finish date) cannot be determined.</p><?php
    }
    return;    
}

$totalTime = runPreparedQuery("CALL getTotalDevTime(?)", $pid);
$totalTime = (int)$totalTime[0][0]['devTime'];
//var_dump($totalTime);
$workRatio = $loggedTime / $totalTime;
//var_dump($workRatio);

$secondsToGoSamples = array();
$densityFunction = array();
for ($i = 0; $i < $nSamples; $i++)
{
    $secondsToGo = 0;
    foreach ($pendingTasks as $task)
    {
        $remainingEstimate = (int)$task['estimatedTime'] - (int)$task['timeLogged'];
        if ($remainingEstimate < 0) $remainingEstimate = 0;
        $chosenSpeed = (double)$speeds[rand(0, $nSpeeds - 1)]['speed'];
        $prognosedEstimate = $remainingEstimate / $chosenSpeed;
        $secondsToGo += $prognosedEstimate;
    }    
    $secondsToGoSamples[] = $secondsToGo;
}

$sum = 0;
for ($i = 0; $i < $nSamples; $i++)
{
    $sum += $secondsToGoSamples[$i];
}
$expectedValue = $sum / $nSamples;

$squareSum = 0;
for ($i = 0; $i < $nSamples; $i++)
{
    $squareSum += ($secondsToGoSamples[$i] - $expectedValue)*($secondsToGoSamples[$i] - $expectedValue);
}
$standardDeviation = sqrt($squareSum / $nSamples);

$insiderCount = 0;
for ($i = 0; $i < $nSamples; $i++)
{
    if (($expectedValue - $standardDeviation <= $secondsToGoSamples[$i]) && ($secondsToGoSamples[$i] <= $expectedValue + $standardDeviation)) $insiderCount++;
}
$insiderRate = $insiderCount / $nSamples;



sort($secondsToGoSamples);
$minimumTime = $secondsToGoSamples[0];
$maximumTime = $secondsToGoSamples[$nSamples - 1];

$timeToChance = array();
$timeStep = ($maximumTime - $minimumTime) / $nDensitySamples;
$currentIndex = 0;
$nSecondsToGoSamples = count($secondsToGoSamples);
for ($i = 0; $i < $nDensitySamples; $i++)
{
	$current = $minimumTime + $i * $timeStep;
	while (($current >= $secondsToGoSamples[$currentIndex]) && ($currentIndex < $nSecondsToGoSamples))
	{
		$currentIndex++;
	}
	$c = getInterpolationCoefficient($secondsToGoSamples[$currentIndex - 1], $secondsToGoSamples[$currentIndex], $current);
	$timeToChance[] = array('time' => $current, 'chance' => ($currentIndex - 1 + $c) * $recNSamples);
}
$densityFunction = array();
$maxDensity = 0;
$mostProbableTime = 0;
for ($i = 0; $i < $nDensitySamples - 1; $i++)
{
	/*Calculating moving average to smooth out the probability density curve*/
	$maxAvg = 0;
	$jn = 0;
	for ($j = $i + 1 - $movingAverageRadius; $j <= $i + 1 + $movingAverageRadius; $j++)
	{
		if ($j < 0) $maxAvg += 0;
		else if ($j >= $nDensitySamples - 1) $maxAvg += 1;
		else $maxAvg += $timeToChance[$j]['chance'];
		$jn++;		
	}
	$maxAvg /= $jn;

	$minAvg = 0;
	$jn = 0;
	for ($j = $i - $movingAverageRadius; $j <= $i + $movingAverageRadius; $j++)
	{
		if ($j < 0) $minAvg += 0;
		else if ($j >= $nDensitySamples - 1) $minAvg += 1;
		else $minAvg += $timeToChance[$j]['chance'];
		$jn++;		
	}
	$minAvg /= $jn;

	$tmp = ($maxAvg - $minAvg);
	$densityFunction[] = $tmp;
	if ($maxDensity < $tmp) 
	{
		$mostProbableTime = $timeToChance[$i]['time'];
		$maxDensity = $tmp;
	}
}

//var_dump($secondsToGoSamples);

function getInterpolationCoefficient($min, $max, $x)
{
    if ($max == $min) return 0;
    return ($x - $min)/($max - $min);
}

if (1)
{
    function echo_content()
    {
        global $densityFunction;
        global $secondsToGoSamples;
        global $nSamples;
        global $recNSamples;
        global $maxDensity;
        global $workRatio;
        global $nSpeeds;
        global $loggedTime;
        global $averageSpeed;
        global $nRemainingTasks;
        global $densityFunction;
        global $nDensitySamples;
        global $timeToChance;
        global $maxDensity;
        global $mostProbableTime;
        global $expectedValue;
        global $standardDeviation;
        global $insiderRate;
        
        $min = $secondsToGoSamples[0];
        $max = $secondsToGoSamples[$nSamples - 1];
        $median = $secondsToGoSamples[$nSamples / 2];
        $width = 300;
        $height = 300;
        $minTimeToGo = (int)($min / $workRatio);
        $minDate = date('Y-m-d', time() + $minTimeToGo);
        $maxTimeToGo = (int)($max / $workRatio);
        $maxDate = date('Y-m-d', time() + $maxTimeToGo." seconds");
        $expectedTimeToGo = (int)($expectedValue / $workRatio);
        $expectedDate = date('Y-m-d', time() + $expectedTimeToGo);
        $standardDeviationMinTime = (int)(($expectedValue - $standardDeviation)/ $workRatio);
        $standardDeviationMaxTime = (int)(($expectedValue + $standardDeviation)/ $workRatio);
        
        $completeness = $loggedTime / ($expectedValue + $loggedTime);
        
        $PROGRESS_BAR_SIZE = 300;
        $greenBarWidth = round($PROGRESS_BAR_SIZE * $completeness);

        ?>
            <p>Cumulative distribution function (red) and probability distribution function in (blue):</p>
            <div>
				<?php echo_escaped($minDate); ?>
		        <div style="display: inline-block; width: <?php echo_escaped($width); ?>px; height: <?php echo_escaped($height); ?>px; background-color: black; margin: 10px; position: relative; left: 0; top: 0; vertical-align: middle">
		            <?php
		                for ($i = 0; $i < $nSamples; $i++)
		                {
		                	$currentSample = $secondsToGoSamples[$i];
		                	$chance = $recNSamples * ($i + 1);
		                    $coeff = getInterpolationCoefficient($min, $max, $currentSample);
		                    $xPos = $coeff * $width;
		                    $yPos = $height - $chance * $height;
		                    ?>
		                        <div 
		                        	style="position: absolute; left: <?php echo_escaped($xPos - 2);?>px; top: <?php echo_escaped($yPos - 2);?>px; width:5px; height:5px; background-color: red"
		                        	onmouseover = "document.getElementById('tip').innerHTML = '<?php echo_escaped(date('Y-m-d', time() + $currentSample / $workRatio).' '.(round($chance * 100)).'%'); ?>'"
		                        	onmouseout = "document.getElementById('tip').innerHTML=''"
		                        ></div>
		                    <?php
		                }
		                for ($i = 0; $i < $nDensitySamples; $i++)
		                {
		                	$currentSample = $densityFunction[$i];
		                    $xPos = $i / $nDensitySamples * $width;
		                    $yPos = $height - $currentSample / $maxDensity * $height;
		                    ?>
		                        <div 
		                        	style="position: absolute; left: <?php echo_escaped($xPos - 2);?>px; top: <?php echo_escaped($yPos - 2);?>px; width:5px; height:5px; background-color: blue"
		                        	onmouseover = "document.getElementById('tip').innerHTML = '<?php echo_escaped(date('Y-m-d', time() + $timeToChance[$i]['time'] / $workRatio).' '.round($currentSample * $nDensitySamples, 2)); ?>'"
		                        	onmouseout = "document.getElementById('tip').innerHTML=''"
		                        ></div>
		                    <?php
		                }
		            ?>
		        	<div id="tip" style="color:white; z-index: 10; position: absolute; left: 0; top: 0;" ></div>
		        </div>
				<?php echo_escaped($maxDate); ?>
            </div>
            <table class="cellborders">
                <tr>
                    <td>Number of speed samples:</td>
                    <td><?php echo_escaped($nSpeeds); ?></td>
                </tr>
                <tr>
                    <td>Ramaining tasks:</td>
                    <td><?php echo_escaped($nRemainingTasks); ?></td>
                </tr>
                <tr>
                    <td>Total time logged: </td>
                    <td><?php echo_escaped(secondsToHms($loggedTime)); ?></td>
                </tr>
                <tr>
                    <td>Time utilization:</td>
                    <td><?php echo_escaped(round($workRatio * 100, 2)); ?>%</td>
                </tr>
                <tr>
                    <td>Average speed:</td>
                    <td><?php echo_escaped(round($averageSpeed, 2)); ?> estimated/logged</td>
                </tr>
            </table>
            <table class="cellborders">
                <tr>
                    <th></th>
                    <th>Man time</th>
                    <th>Actual time</th>
                    <th>Remaining time</th>
                </tr>
                <tr>
                    <td>Best estimate: </td>
                    <td><?php echo_escaped(secondsToHms($min));?></td>
                    <td><?php echo_escaped($minDate); ?></td>
                    <td><?php echo_escaped(secondsToDayStr($minTimeToGo)); ?></td>
                </tr>
                <tr>
                    <td>Worst estimate:</td>
                    <td><?php echo_escaped(secondsToHms($max));?></td>
                    <td><?php echo_escaped($maxDate); ?></td>
                    <td><?php echo_escaped(secondsToDayStr($maxTimeToGo)); ?></td>
                </tr>
                <tr>
                    <td>Statistical expected value:</td>
                    <td><?php echo_escaped(secondsToHms($expectedValue));?></td>
                    <td><?php echo_escaped($expectedDate); ?></td>
                    <td><?php echo_escaped(secondsToDayStr($expectedTimeToGo)); ?></td>
                </tr>
                <tr>
                    <td>Standard deviation:</td>
                    <td><?php echo_escaped("±" . secondsToHms($standardDeviation));?></td>
                    <td><?php echo_escaped(date('Y-m-d', time() + $standardDeviationMinTime) . ' - '.  date('Y-m-d', time() + $standardDeviationMaxTime)); ?></td>
                    <td><?php echo_escaped(secondsToDayStr($standardDeviationMinTime) . ' - ' . secondsToDayStr($standardDeviationMaxTime)); ?></td>
                </tr>
                <tr>
                    <td>Confidence of the interval:</td>
                    <td colspan="3"><?php echo_escaped(round($insiderRate * 100) . '% of samples are in the interval.'); ?></td>
                </tr>
            </table>
            <p>Progress: 
            	<span 
            		style="position: relative; display:inline-block; width: <?php echo_escaped($PROGRESS_BAR_SIZE); ?>px; height: 20px; border: 1px solid black;"
            		class="center"
            	><span 
            		style="position: absolute; left: 0; top: 0; display:block; width: <?php echo_escaped($greenBarWidth); ?>px; height: 100%; background-color: green; z-index: -1"
            	></span><?php echo_escaped((round($completeness * 100)) . '%')?></span>
            </p>
            
            <p>The results are based on a Monte Carlo simulation.</p>
        <?php
    }
}



?>
