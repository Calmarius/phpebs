<?php

function installErrorProc($str)
{
    global $installerror;
  
    echo $str;
  
    $installerror = $str;  
}

function process()
{
    global $VIEW;
    global $INSTALLED;
    global $installerror;
    
    $installerror = '';

    if ($INSTALLED)
    {
        /* Guard against hacking attempts */
        $VIEW = 'login';
        return;
    }

    /* Now we assume that all data is trusted. */

    $f = fopen("shadow.php", "w");
    if ($f === FALSE)
    {
        $VIEW = 'install';
        $installerror = 'Failed to create shadow.php';
        return;
    }

    $res = fwrite($f, <<<X
<?php
    define('__MYSQL_USER_NAME','${_POST['mysql_user']}');
    define('__MYSQL_PASSWORD','${_POST['mysql_password']}');
    define('__MYSQL_DB_NAME','${_POST['mysql_dbname']}');
    define('__ADMIN_MAIL','${_POST['admin_mail']}');
    define('__SESSION_NAME','${_POST['session_name']}');
X
    );

    require('shadow.php');
    require('setupmysqli.php');

    if ($res === FALSE)
    {
        $VIEW = 'install';
        $installerror = 'Failed to write shadow.php';
        return;
    }

    if (fclose($f) === FALSE)
    {
        $VIEW = 'install';
        $installerror = 'Failed to write shadow.php';
        return;
    }

    $sqlDump = <<<X
    SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
    SET AUTOCOMMIT = 0;
    START TRANSACTION;
    SET time_zone = "+00:00";

    DELIMITER $$
    CREATE  PROCEDURE `authenticateUser`(IN `_userName` VARCHAR(20), IN `_password` VARCHAR(255))
        READS SQL DATA
    now: BEGIN
	    DECLARE userId INT DEFAULT 0;
            DECLARE uName VARCHAR(20) DEFAULT '';
            SELECT id, userName
            INTO userId, uName 
            FROM users 
            WHERE (userName = _userName) AND (passwordHash = SHA1(_password));
      	IF userId <> 0 THEN
            	SELECT userId, uName, 'success';
                    LEAVE now;
            ELSE
            	SELECT 0, '', 'fail';
                    LEAVE now;
            END IF;
    END$$

    CREATE  PROCEDURE `createNewProject`(IN `_ownerId` INT, IN `name` VARCHAR(255))
        MODIFIES SQL DATA
    now: BEGIN
	    DECLARE userId INT DEFAULT 0;
            SELECT id INTO userId FROM users WHERE (id = _ownerId);
            IF userId = 0 THEN
            	SELECT 'nosuchuser';
                    LEAVE now;
            END IF;
	    INSERT INTO projects 
            (ownerId, projectName) 
            VALUES 
            (_ownerId, name);
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `createNewTask`(IN `userId` INT, IN `projectId` INT, IN `parentId` INT, IN `name` VARCHAR(255), IN `description` TEXT, IN `estimate` BIGINT)
        MODIFIES SQL DATA
    now: BEGIN
	    DECLARE pid INT DEFAULT 0;
            
            SELECT id 
            INTO pid
            FROM projects
            WHERE id = projectId AND ownerId = userId;
	    IF pid = 0 THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            INSERT INTO tasks 
            (projectId, summary, description, estimatedTime) 
            VALUES 
            (projectId, name, description, estimate);
            IF parentId <> 0 THEN
            	IF NOT addChildOnTaskUnchecked(parentId, LAST_INSERT_ID()) THEN
                    	SELECT 'failedtoaddchild';
                            LEAVE now;
                    END IF;
            END IF;
            SELECT LAST_INSERT_ID() AS id, 'success';
	        CALL maintainPaddingTask(parentId, estimate);
            UPDATE tasks SET estimatedTime = 0 WHERE id = parentId;
    END$$

    CREATE  PROCEDURE `deleteProject`(IN `uid` INT, IN `pid` INT, IN `password` VARCHAR(255))
        MODIFIES SQL DATA
    now: BEGIN
	    DECLARE authenticated INT;

	    IF NOT doesUserHaveTheProject(uid, pid) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            SELECT COUNT(*) 
            INTO authenticated
            FROM users 
            WHERE (id = uid) AND (passwordHash = SHA1(password));
            IF authenticated < 1 THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            
            DELETE FROM projects WHERE id = pid;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `deleteTask`(IN `_userId` INT, IN `_taskId` INT)
        MODIFIES SQL DATA
    now: BEGIN
            DECLARE _parentTaskId INT;
            DECLARE _siblingCount INT;
            DECLARE estimate INT DEFAULT 0;
            
	    IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
            	LEAVE now;
            END IF;
            SELECT estimatedTime INTO estimate
            	FROM tasks
            	WHERE id = _taskId;
            CALL logWorkToPadding(_taskId, -estimate);
            CALL removeTaskUnchecked(_taskId);
            DELETE FROM tasks WHERE id = _taskId;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `editTask`(uid INT, tid INT, newSummary VARCHAR(255), newDescription TEXT, newEstimate INT)
    now: BEGIN
	    IF NOT doesUserHaveTheTask(uid, tid) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            UPDATE tasks SET summary = newSummary, description = newDescription, estimatedTime = newEstimate WHERE id = tid;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `finishTask`(IN `_userId` INT, IN `_taskId` INT)
        MODIFIES SQL DATA
    now: BEGIN
	    DECLARE taskStatus VARCHAR(60) DEFAULT '';
          	IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            SELECT status INTO taskStatus FROM tasks WHERE id = _taskId;
            IF taskStatus = 'in progress' THEN
            	CALL stopTask(_userId, _taskId);
            END IF;
            UPDATE tasks SET status = 'done', doneAt = NOW() WHERE (id = _taskId);
	    SELECT 'success';
    END$$

    CREATE  PROCEDURE `getLoggedTime`(IN `pid` INT)
        NO SQL
    BEGIN
	    SELECT SUM(timeLogged) AS totalLogged FROM tasks WHERE (projectId = pid) AND (status <> 'splitted');
    END$$

    CREATE  PROCEDURE `getPendingTasks`(IN `pid` INT)
        READS SQL DATA
    SELECT * FROM tasks WHERE (projectId = pid) AND (status <> 'done') AND (status <> 'splitted')$$

    CREATE  PROCEDURE `getProjectById`(IN `pid` INT)
    BEGIN
	    SELECT * FROM projects WHERE id = pid;
    END$$

    CREATE  PROCEDURE `getProjectTasks`(IN `uid` INT, IN `pid` INT)
        NO SQL
    BEGIN
	    SELECT tasks.* 
            FROM tasks INNER JOIN projects ON projects.id = tasks.projectId
            WHERE projectId = pid AND ownerId = uid ORDER BY tasks.id;
    END$$

    CREATE  PROCEDURE `getRemainingTaskCount`(IN `pid` INT)
        READS SQL DATA
    SELECT COUNT(*) AS remainingTasks FROM tasks WHERE (projectId = pid) AND (status <> 'done') AND (status <> 'splitted')$$

    CREATE  PROCEDURE `getSpeedsOfDoneTasks`(IN `pid` INT)
    BEGIN
        SELECT estimatedTime / timeLogged AS speed
        FROM tasks
        WHERE 
        	(status = 'done') AND 
        	(projectId = pid) AND 
            (estimatedTime <> 0) AND 
            (timeLogged <> 0) AND
            (TIMESTAMPDIFF(MONTH, doneAt, NOW()) < 6);
    END$$

    CREATE  PROCEDURE `getTaskById`(IN `_userId` INT, IN `_taskId` INT)
        READS SQL DATA
    BEGIN
	    SELECT tasks.* 
            FROM tasks JOIN projects ON tasks.projectId = projects.id
            WHERE (tasks.id = _taskId) AND (projects.ownerId = _userId);
    END$$

    CREATE  PROCEDURE `getTotalDevTime`(IN `pid` INT)
        READS SQL DATA
    SELECT TIMESTAMPDIFF(SECOND, startedAt, NOW()) AS devTime FROM projects WHERE (id = pid)$$

    CREATE  PROCEDURE `listUserProjects`(IN `uid` INT)
    BEGIN
	    SELECT * FROM projects WHERE (ownerId = uid);
    END$$

    CREATE  PROCEDURE `logWork`(IN `_userId` INT, IN `_taskId` INT, IN `seconds` INT)
        MODIFIES SQL DATA
    now: BEGIN
	    IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            UPDATE tasks SET timeLogged = timeLogged + seconds WHERE id = _taskId;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `logWorkToPadding`(IN `_taskId` INT, IN `timeToLog` INT)
    now: BEGIN
	    DECLARE _parentId INT;
            DECLARE _paddingId INT;
            SELECT parentTaskId INTO _parentId 
            	FROM tasks 
                    WHERE (id = _taskId);
            SET _paddingId = getPaddingTaskIdOfTask(_parentId);
            IF _paddingId = _taskId THEN
            	LEAVE now;
            END IF;
            UPDATE tasks SET estimatedTime = estimatedTime - timeToLog WHERE (id = _paddingId);
    END$$

    CREATE  PROCEDURE `maintainPaddingTask`(IN `taskId` INT, IN `newTaskEstimate` INT)
    now: BEGIN
	    DECLARE paddingId INT;
            DECLARE remainingEstimate INT;
            DECLARE _projectId INT;
            SET paddingId =  getPaddingTaskIdOfTask(taskId);
            IF (paddingId <> 0) THEN
	            -- Already have a padding task so update its estimate, then leave.
                    UPDATE tasks 
                    SET estimatedTime = estimatedTime - newTaskEstimate
                    WHERE id = paddingId;
            	LEAVE now;
            END IF;
            SELECT estimatedTime - timeLogged - newTaskEstimate, projectId INTO remainingEstimate, _projectId FROM tasks WHERE id = taskId;
            IF remainingEstimate <= 0 THEN
	            -- No padding, task is already overdue.
            	LEAVE now;
            END IF;
            -- Let's insert a padding task
            INSERT INTO tasks
            (projectId, parentTaskId, summary, estimatedTime, status)
            VALUES
            (_projectId, taskid, "Padding", remainingEstimate, "padding");
    END$$

    CREATE  PROCEDURE `moveTask`(IN `userId` INT, IN `newParent` INT, IN `taskId` INT)
    now: BEGIN
	    IF NOT doesUserHaveTheTask(userId, taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            CALL removeTaskTreeUnchecked(taskId);
            IF NOT addChildOnTaskUnchecked(newParent, taskId) THEN
            	SELECT 'failed';
                    LEAVE now;
            END IF;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `multiply`(IN `x` INT, IN `y` INT)
    BEGIN
	    SELECT x*y;
    END$$

    CREATE  PROCEDURE `registerNewUser`(IN `name` VARCHAR(20), IN `password` VARCHAR(255))
    now: BEGIN
	    DECLARE existing_id INT DEFAULT 0;
	    SELECT id INTO existing_id FROM users WHERE userName=name;
            IF existing_id <> 0 THEN
            	SELECT 'alreadyexist';
                    LEAVE now;
            END IF;
            INSERT INTO users (userName, passwordHash) VALUES (name, SHA1(password));
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `removeTaskTreeUnchecked`(_taskId INT)
        MODIFIES SQL DATA
    BEGIN
            DECLARE _parentTaskId INT;
            DECLARE _siblingCount INT;
            
            SELECT parentTaskId INTO _parentTaskId FROM tasks WHERE id = _taskId;
            -- Extract the task from the tree
            UPDATE tasks SET parentTaskId = 0 WHERE id = _taskId;
            -- Remove the splitted status for the parent task if it remained without child nodes.
            SELECT COUNT(*) INTO _siblingCount FROM tasks WHERE parentTaskId = _parentTaskId;
            IF _siblingCount = 0 THEN
            	UPDATE tasks SET status = 'stopped' WHERE id = _parentTaskId;
            END IF;
    END$$

    CREATE  PROCEDURE `removeTaskUnchecked`(_taskId INT)
        MODIFIES SQL DATA
    BEGIN
            DECLARE _parentTaskId INT;
            DECLARE _siblingCount INT;
            
            SELECT parentTaskId INTO _parentTaskId FROM tasks WHERE id = _taskId;
            -- Extract the task from the tree
            UPDATE tasks SET parentTaskId = 0 WHERE id = _taskId;
            -- Link child tasks of the current task to the parent.
            UPDATE tasks SET parentTaskId = _parentTaskId WHERE parentTaskId = _taskId;
            -- Remove the splitted status for the parent task if it remained without child nodes.
            SELECT COUNT(*) INTO _siblingCount FROM tasks WHERE parentTaskId = _parentTaskId;
            IF _siblingCount = 0 THEN
            	UPDATE tasks SET status = 'stopped' WHERE id = _parentTaskId;
            END IF;
    END$$

    CREATE  PROCEDURE `restartTask`(IN `_userId` INT, IN `_taskId` INT)
        MODIFIES SQL DATA
    now: BEGIN
          	IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            UPDATE tasks SET status = 'stopped' WHERE (id = _taskId) AND (status = 'done');
	    SELECT 'success';
    END$$

    CREATE  PROCEDURE `setProjectName`(IN `uid` INT, IN `pid` INT, IN `name` VARCHAR(255))
        MODIFIES SQL DATA
    now: BEGIN
	    IF NOT doesUserHaveTheProject(uid, pid) THEN
            	SELECT 'accessdenied';
            	LEAVE now;
            END IF;
            UPDATE projects SET projectName = name WHERE id = pid;
            SELECT 'success';
    END$$

    CREATE  PROCEDURE `startTask`(IN `_userId` INT, IN `_taskId` INT)
        MODIFIES SQL DATA
    now: BEGIN
          	IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            -- Kick off the project if not already started
            UPDATE projects SET status = 'in progress', startedAt = NOW() WHERE (id = getProjectIdOfTask(_taskId)) AND (status = 'planning');
            -- Set status here
            UPDATE tasks SET status = 'in progress', startedAt = NOW() WHERE (id = _taskId) AND (status = 'stopped');
	    SELECT 'success';
    END$$

    CREATE  PROCEDURE `stopTask`(IN `_userId` INT, IN `_taskId` INT)
    now: BEGIN
          	IF NOT doesUserHaveTheTask(_userId, _taskId) THEN
            	SELECT 'accessdenied';
                    LEAVE now;
            END IF;
            CALL stopTaskUnchecked(_taskId);
	    SELECT 'success';
    END$$

    CREATE  PROCEDURE `stopTaskUnchecked`(IN `_taskId` INT)
        MODIFIES SQL DATA
    now: BEGIN
	    DECLARE timeToLog INT;
            SELECT TIMESTAMPDIFF(SECOND, startedAt, NOW()) INTO timeToLog 
            	FROM tasks
                    WHERE (id = _taskId) AND (status = 'in progress');

            UPDATE tasks 
            	SET status = 'stopped', 
            		timeLogged = timeLogged + timeToLog
            	WHERE (id = _taskId) AND (status = 'in progress');
    END$$

    CREATE  PROCEDURE `test`()
    BEGIN
	    SELECT 'A';
            SELECT 'B';
            SELECT 'C';
    END$$

    CREATE  FUNCTION `addChildOnTaskUnchecked`(`parentId` INT, `taskId` INT) RETURNS tinyint(1)
    BEGIN
	    DECLARE affectedRows INT;
        DECLARE prevState VARCHAR(20); 
        DECLARE toParentRemainingEstimate INT DEFAULT 0;
        DECLARE taskEstimate INT DEFAULT 0;
        DECLARE remainingEstimate INT DEFAULT 0;
        DECLARE parentProjectId INT DEFAULT 0;
	    IF parentId = taskId THEN
            RETURN FALSE;
        END IF;
	    SELECT estimatedTime - timeLogged, projectId INTO toParentRemainingEstimate, parentProjectId FROM tasks WHERE id = parentId;
	    SELECT estimatedTime INTO taskEstimate FROM tasks WHERE id = taskId;
        UPDATE tasks SET parentTaskId=parentId WHERE id = taskId;
        SELECT ROW_COUNT() INTO affectedRows;
        IF affectedRows = 0 THEN
         	RETURN FALSE;
        END IF;
        -- CALL stopTaskUnchecked(parentId);
        SELECT status INTO prevState FROM tasks WHERE id = parentId;
        IF prevState <> 'splitted' THEN
            INSERT INTO tasks
            (projectId, parentTaskId, summary, estimatedTime, status)
            VALUES
            (parentProjectId, parentId, "Padding", toParentRemainingEstimate, "padding");
        END IF;
        UPDATE tasks SET status='splitted' WHERE id = parentId;
        RETURN TRUE;
    END$$

    CREATE  FUNCTION `doesUserHaveTheProject`(`uid` INT, `pid` INT) RETURNS tinyint(1)
        READS SQL DATA
    BEGIN
	    DECLARE ret BOOLEAN;
            SELECT COUNT(*) INTO ret FROM projects WHERE (ownerId = uid) AND (id = pid);
            RETURN ret;
    END$$

    CREATE  FUNCTION `doesUserHaveTheTask`(`_userId` INT, `_taskId` INT) RETURNS tinyint(1)
        NO SQL
    BEGIN
	    DECLARE projectId INT DEFAULT 0;
	    SELECT projects.id INTO projectId
            	FROM projects 
            	JOIN tasks 
            	ON projects.id = tasks.projectId
            	WHERE (projects.ownerId = _userId) AND (tasks.id = _taskId);
          	RETURN projectId <> 0;
    END$$

    CREATE  FUNCTION `getPaddingTaskIdOfTask`(`taskId` INT) RETURNS int(11)
        READS SQL DATA
    BEGIN
	    DECLARE _id INT DEFAULT 0;
            DECLARE _tmpTaskId INT DEFAULT 0;
            
            WHILE (taskId <> 0) AND (_id = 0) DO
                SELECT id INTO _id
                    FROM tasks 
                    WHERE (status = 'padding') AND (parentTaskId = taskId)
                    LIMIT 1;
                SELECT parentTaskId INTO taskId FROM tasks WHERE id = taskId;
            END WHILE;
            RETURN _id;
    END$$

    CREATE  FUNCTION `getProjectIdOfTask`(`_taskId` INT) RETURNS int(11)
        NO SQL
    BEGIN
	    DECLARE pid INT DEFAULT 0;
            SELECT projectId
            INTO pid
            FROM tasks
            WHERE (id = _taskId);
            RETURN pid;
    END$$

    DELIMITER ;

    CREATE TABLE IF NOT EXISTS `debug` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `txt` text CHARACTER SET utf8,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin ;

    CREATE TABLE IF NOT EXISTS `projects` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `ownerId` int(11) NOT NULL,
      `projectName` varchar(255) CHARACTER SET utf8 NOT NULL,
      `status` enum('planning','in progress','done') NOT NULL DEFAULT 'planning',
      `startedAt` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY `ownerId` (`ownerId`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

    CREATE TABLE IF NOT EXISTS `tasks` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `projectId` int(11) NOT NULL,
      `parentTaskId` int(11) NOT NULL DEFAULT '0',
      `summary` varchar(255) NOT NULL,
      `description` text NOT NULL,
      `estimatedTime` bigint(20) NOT NULL COMMENT 'in seconds',
      `timeLogged` bigint(20) NOT NULL DEFAULT '0' COMMENT 'in seconds',
      `status` enum('stopped','in progress','done','splitted','padding') NOT NULL DEFAULT 'stopped',
      `startedAt` datetime NOT NULL,
      `doneAt` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY `projectId` (`projectId`),
      KEY `parentTask` (`parentTaskId`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

    CREATE TABLE IF NOT EXISTS `tmpSpeeds` (
      `speed` decimal(23,4) DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `userName` varchar(20) NOT NULL,
      `passwordHash` varchar(255) NOT NULL COMMENT 'SHA1',
      PRIMARY KEY (`id`),
      UNIQUE KEY `userName_2` (`userName`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
    COMMIT;

X;

    $VIEW = 'install';
    
    {
        $delimiter = ';';
        
        while ($sqlDump != '')
        {
            $sqlDump = ltrim($sqlDump);
            
            if (strncmp($sqlDump, "DELIMITER", strlen("DELIMITER")) == 0)
            {
                $sqlDump = substr($sqlDump, strlen("DELIMITER"));
                $sqlDump = ltrim($sqlDump);
                $brkPos = strpos($sqlDump, "\n");
                $delimiter = substr($sqlDump, 0, $brkPos);
                $sqlDump = substr($sqlDump, $brkPos);
            }
        
            $pos = strpos($sqlDump, $delimiter);
            if ($pos == FALSE) $pos = strlen($sqlDump);
            
            $query = substr($sqlDump, 0, $pos);
            
            runQueryEx($query, 'installErrorProc');            
    
            if ($installerror != '')
            {
                return;
            }
            
            $sqlDump = substr($sqlDump, $pos + strlen($delimiter));
        }
    }
    
    
    $f = fopen("installed", "w");
    if ($f === FALSE)
    {
        $installerror = 'Failed to create the "installed" file. ';
        return;
    }
    if (fclose($f) === FALSE)
    {
        $installerror = 'Failed to close the "installed" file. ';
        return;
    }
    
    $VIEW = 'login';
    $INSTALLED = true;
}


