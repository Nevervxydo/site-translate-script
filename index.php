<?php
//================ Nothing to change in this file =========================

ob_start(); //we may set response header data later, so make sure we are buffered

//settings now in a separate file
require_once('tr_config.php');
require_once('tr_log.php');

logger(TRLOG_DEBUG,"Enter index.php: log_level=$log_level");
logger(TRLOG_DEBUG,"site_url=" . $params['site_url']);

function CheckUrl($url)
{
	global $check_url_proxy;
	
	$rval = array('result'=>500,'mtime'=>0);

	if(function_exists('curl_init'))
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PROXY, $check_url_proxy);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);  //HEAD REQUEST
		//curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Translate');
		$content = curl_exec($ch);
		if (curl_errno($ch))
			logger(TRLOG_WARN,"ERROR: CheckUrl($url): " . curl_error($ch));
		else
		{
			$rval['result'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$rval['mtime'] = curl_getinfo($ch,CURLINFO_FILETIME);
			$rval['eff_url'] = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
		}
		curl_close($ch);
	}
	return $rval;
}

function RemoveUrlParams($url,$remove_params)
{
	global $log_errors;

	logger(TRLOG_DEBUG,"  RemoveUrlParams($url)");

	if ( preg_match('|\?|',$url) )
	{
		$base = preg_replace('|\?*.*|','',$url);
		$params = preg_replace('|\?(.*)|','',$url);
	}
	else
	{
		$base = $url;
		$params = '';
	}

	logger(TRLOG_TRACE,"  base=$base");
	logger(TRLOG_TRACE,"  params=$params");


	foreach ($remove_params as $p)
	{
		$params = preg_replace("|(.*?)$p=[^&]*(.*?)|",'$1$2',$params);
	}

	$params = preg_replace('|&+|','&',$params);
	$params = preg_replace('|^&|','',$params);
	$params = preg_replace('|&$|','',$params);

	logger(TRLOG_TRACE,"  new_params=$params");

	if ($params)
		$url = "$base?$params";
	else
		$url = $base;
	
	logger(TRLOG_DEBUG,"  new_url=$url");

	return $url;
}


//Get some pertinent request parameters
$page =  $params['page'] = $_GET['page'];
$uri = $params['uri'] = $_SERVER['REQUEST_URI'];
$hostname = $params['hostname'] = $_SERVER['SERVER_NAME'];

$host = preg_replace('|([^.]*)\..*|','$1',$hostname);
logger(TRLOG_DEBUG,"hostname: " . $hostname . " host:" . $host);

if (!$host)
	$host = "www";

if (isset($_GET['engine']))
	$engine = $_GET['engine'];
else
	$engine = 'g';

if (!$use_lang_hostnames || $host == "www")
{
	$lang = $_GET['language'];
	$params['trans_dir'] = "{$engine_dirs[$engine]}/$lang";
	$params['translated_base_href'] = $params['site_url'] . '/' . $params['trans_dir'];
}
else //assume translated subdomain
{
	$lang = $host; 
	$params['trans_dir'] = "";
	$params['translated_base_href'] = "http://$hostname";
}

$lang = strtolower($lang);
if (isset($lang_map[$lang])) 
	$lang = $lang_map[$lang];

$params['language'] = $lang; 

if (!$engine)
{
	if ($host == "el" || $host == "nl")
		$engine = 'y';
	else
		$engine = 'g';
}
$params['engine'] = $engine;

#$page = RemoveUrlParams($page,$remove_params);
RemoveUrlParams($page,$remove_params);

//old page_id
$page_id = $lang.'_'.str_replace('__','',str_replace('.','',str_replace('/','_',$page.'_')));

//new page_id
//$page_id = preg_replace('|/+|','-',$page);	//optionally make / into -
//$page_id = $lang . '-' . preg_replace('|[^a-z0-9\-]+|i','_',$page_id) . '_';

$params['page_id'] = $page_id;
$cache_file = $params['cache_file'] = "$engine/$page_id";

//test mod_rewrite saneness
//echo $page_id;
//echo $cache_file;
//print_r ($params);
//print phpinfo(INFO_VARIABLES);
//exit;

logger(TRLOG_INFO,"page_id:" . $page_id . " engine:" . $engine . " lang:" . $lang);

//check for a cached file and if missing or old, translate
$expired=0;

//check the url
$url_mtime = 0;
if ($do_check_url)
{
	$url = $params['site_url'] . '/' . $params['page'];
	$cu = CheckUrl($url);
	
	$cu_result = $cu['result'];
	
	logger(TRLOG_DEBUG,"CheckUrl($url): result = $cu_result");
	
	//return errors
	if ($cu_result >= 400)
	{
		header("X-Translate: $cu_result",TRUE,$cu_result);
		echo "Error: " . $cu_result;
		exit(0);
	}
	else if ($cu_result >= 300) //handle redirects
	{
		$redir_url =  $cu['eff_url'];
		$new_url = preg_replace('|://www\.|','://' . $lang . '.' ,$redir_url);
		header("Location: $new_url");
		logger(TRLOG_INFO,"CheckUrl($url): fixup redirect: $cu_result $redir_url -> $new_url");
	}
	
	$url_mtime = $cu['mtime'];
}

$cache_mtime = 0;
if (file_exists($cache_file))
	$cache_mtime = filemtime($cache_file);

logger(TRLOG_DEBUG,"url_mtime: $url_mtime cache_mtime: $cache_mtime");

if ($cache_mtime < $url_mtime)
	$expired = 1;
//force cache refresh on old files
else if( time() > ( $cache_mtime + ($translator_cache_days * 86400)) )
	$expired = 1;
	

	
//$expired = 0;
if ( $expired )
{
	//do the translation
	logger(TRLOG_INFO,"Updating cache for: $cache_file");
	
	require_once("translate.php");
	$rval = Translate($params);
	$code = $rval['code'];

	if ( 0 <= $code ) 
	{
		$content = $rval['content'];	
		
		//send the output
		echo $content;
		
		//save to cache
		$cache_file = $params['engine'] . '/' . $params['page_id'];
		logger(TRLOG_INFO,"Writing cache: " . $cache_file);
		$fh = fopen($cache_file, "w+");
		fwrite($fh, $content);
		fclose($fh);

	}
	else
	{
		if (file_exists($cache_file)) {
			logger(TRLOG_WARN,"Translation error on cache refresh.  Sending old cache content: " . $cache_file);
			$content = file_get_contents($cache_file);
			echo $content;
		} else {
			echo $error_msg;
			echo "Code: " . $code;
			if ($print_error_text) { 
				echo " (" . $rval['error'] . ")<br/>\n<br/>\n";
				echo "Redirects:<br/>\n";
				$rarray = $rval['redirects'];
				foreach ($rarray as $redir) {
					echo $redir . "<br/>\n";
				}
			}
		}
	}
	

} 
else 
{
	//Page cache is up to date, send it
	logger(TRLOG_DEBUG,"Sending cache content: " . $cache_file);
	$content = file_get_contents($cache_file);
	echo $content;
}

close_logger();

exit;
?>
