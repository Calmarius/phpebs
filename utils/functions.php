<?php

function estimateToSeconds($estimateStr)
{
    $estimateSeconds = 0;
    $expectUnit = false;

    preg_match_all('/(\-?\d+)(\w*)/', $estimateStr, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match)
    {
        if (($match[2] == '') || ($match[2] == 'h'))
        {
            $estimateSeconds += 3600*(int)$match[1];
        }
        else if ($match[2] == 'm')
        {
            $estimateSeconds += 60*(int)$match[1];
        }
    }
    
    return $estimateSeconds;
}

function secondsToHms($x)
{
    $hours = floor($x / 3600);
    $minutes = floor($x / 60) % 60;
    $seconds = $x % 60;
    
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

function secondsToIntervalStr($x)
{
    if ($x > 86400 * 365) return floor($x / (86400 * 365))." years";
    if ($x > 86400 * 30) return floor($x / (86400 * 30))." months";
    if ($x > 86400) return floor($x / 86400)." days";
    if ($x > 3600) return floor($x / 3600)." hours";
    if ($x > 60) return floor($x / 60)." minutes";
    return floor($x)." seconds";
}

function secondsToDayStr($x)
{
    if ($x > 86400) return floor($x / 86400)." days";
    if ($x > 3600) return floor($x / 3600)." hours";
    if ($x > 60) return floor($x / 60)." minutes";
    return floor($x)." seconds";
}

function secondsToStr($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor($seconds / 60) % 60;

    $result = '';
    if ($hours > 0) $result .= "${hours}h";
    if ($minutes > 0) $result .= " ${minutes}m";
    
    if (strlen($result) == 0) $result = '0m';
    
    return $result;
    
}

?>
