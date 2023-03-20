<?php

define('TRLOG_NEVER',-1);
define('TRLOG_ERROR',0);
define('TRLOG_WARN',1);
define('TRLOG_INFO',2);
define('TRLOG_DEBUG',3);
define('TRLOG_TRACE',4);

//$log_fh = NULL;

//Simple method to restrict logging by levels
//The $log_level global is set in translate_config.php.  Lower values are more urgent.
function logger($level,$msg)
{
	global $log_level,$log_file,$log_fh;
	
	if ($log_level >= $level)
	{
		if ($log_file)
		{
			if (!$log_fh)
				$log_fh = fopen($log_file,'a');  //What to do about failures?  Display error?
			
			if ($log_fh)
				fwrite($log_fh,date('r') . ': ' . $msg . ' IP: ' . $_SERVER['REMOTE_ADDR'] . "\n");
		}
		else		
			error_log("LANG2: $msg");	//log to server error log
	}
}

function close_logger()
{
	global $log_fh;
	
	if ($log_fh)
		fclose($log_fh);
}

?>
