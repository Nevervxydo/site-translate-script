<?php


require_once("tr_log.php");
require_once("tr_fixup.php");
require_once("tr_google.php");

//require_once("tr_yahoo.php");

//TODO: save redirect contents to rval map instead of writing directly to file (no file I/O from Translate)
function Translate($params)
{
	global $engines, $translate_proxy, $customTagFixup;

	$rval['code'] = 0;
	$rval['redirects'] = array();
	$rval['content'] = '';

	if( ! function_exists('curl_init') )
	{
		$rval['code'] = -1;
		$rval['error'] = "CURL not available";
		error_log(TRLOG_ERROR,$rval['error']);
		return $rval;
	}

	//extract some basic paramters
	$engine = $params['engine'];
	$lang = $params['language'];

	//$inlang='';










	///////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////
	// ÑÎÁÑÒÂÅÍÍÛÅ ÍÀÑÒÐÎÉÊÈ
	///////////////////////////////////////////////////////////////////
	//Èñïðàâëÿåì íàïðàâëåíèå ïåðåâîäà äëÿ óêð -> óêð. äëÿ ðàçäåëà lib ³åí	 (èñïîëüçóþòñÿ â $engines['g'] =)
		// $URL = $_SERVER['REQUEST_URI'];
		// $inlangarray = array(0 => "'" . '^\/ (?:lib|mob[\/]imei|work[\/]civil|work[\/]crime|work[\/]month[\-]spelling|univer[\/]univer|zip) (?:\/|$)' . "'x",);
		// if (preg_match($inlangarray[0], $URL)) {$inlang='uk';}
		// else $inlang='ru';
	///////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////
	// ÑÎÁÑÒÂÅÍÍÛÅ ÍÀÑÒÐÎÉÊÈ
	///////////////////////////////////////////////////////////////////








	
	// $DOMAINE = $_SERVER['SERVER_NAME'];
	// $domainearray = array(0 => "'" . '^\/ (?:uk) (?:\/|$)' . "'x",);
	// if (preg_match($domainearray[0], $DOMAINE)) {$lang='ru';}
	// else $lang='';


	$page_id = $params['page_id'];

	$url = $params['site_url'] . '/' . preg_replace(array("'^\/+'", "'\/+'"), array('', '/'), $params['page']);

	//get the translation engine options
	$translator_url = $engines[$engine]['trans_url'];
	$check_func = $engines[$engine]['check_func'];
	$fixup_func = $engines[$engine]['fixup_func'];
	$timeout = $engines[$engine]['timeout'];
	$customTagFixup = $engines[$engine]['tag_fixup_func'];

	$redir_count = 0;
	$max_redir = $engines[$engine]['max_redir'];

	@set_time_limit($max_redir * $timeout + 5);  //limit script exec time

	//do variable substitution on translator URL
	eval("\$resource = \"$translator_url\";");

	//$resource='return '.$translator_url;
	//$title = $$resource;
	//$resource = $translator_url;


	//Loop to handle redirections
	$get_url = 1;
	while ($get_url) {
		$get_url = 0;	//don't loop unless we are processing a redirect

		logger(TRLOG_DEBUG,"Translator url: $resource");

		//submit the translation request
		$ch = curl_init();
		logger(TRLOG_TRACE,"after curl_init");
		curl_setopt($ch, CURLOPT_URL, $resource);
		curl_setopt($ch, CURLOPT_PROXY, $translate_proxy);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // Return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $params['user_agent']);

		global $content;
		$content = curl_exec($ch);

		logger(TRLOG_TRACE,"after curl_exec");

		$req_status = curl_getinfo($ch,CURLINFO_HTTP_CODE);

		if ($content === false)
		{
			$rval['code'] = -10;
			$rval['error'] = "curl_exec failed: stat: $req_status error: " . curl_error($ch);

			logger(TRLOG_WARN,$rval['error']);

			curl_close($ch);
			return $rval;
		}

		curl_close($ch);

		if ($params['write_raw_content']) {
			$raw_file = "$engine/$page_id-$redir_count.raw";
			logger(TRLOG_DEBUG,"Writing raw content: " . $raw_file);
			$fh = fopen($raw_file, "w+");
			fwrite($fh, $content);
			fclose($fh);
		}

		//Return on error
		if ( $req_status != '200' )
		{
			$rval['code'] = -2;
			$rval['error'] = "Translator returned status '$req_status' (req=$resource)";
			logger(TRLOG_WARN,$rval['error']);
			return $rval;
		}
		elseif ($check_func)
		{
			$chk_status = $check_func($content,$params);
			$chk_result = $chk_status['result'];

			if ($chk_result == "OK") {
				logger(TRLOG_DEBUG,"Content Check OK");
				break;
			}
			elseif ($chk_result == "REDIRECT") {
				$redir_count++;
				$resource = $chk_status['redirect'];
				$message = $chk_status['message'];
				array_push($rval['redirects'],"$message: $resource");

				logger(TRLOG_DEBUG,"REDIRECT message: $message, resource: $resource");

				if ($redir_count > $max_redir) {
					$rval['code'] = -4;
					$rval['error'] = "max_redir exceeded (max=$max_redir,req=$resource)";
					logger(TRLOG_WARN,$rval['error']);
					return $rval;
				}
				$get_url=1;
			}
			else {
				$rval['code'] = -5;
				$rval['error'] = "Failed check_func (result=$chk_result,req=$resource)";
				logger(TRLOG_WARN,$rval['error']);
				return $rval;
			}
		}
	}

 	//fixup translated page content
	if ($fixup_func)
		$content = $fixup_func($content);
		
	else
		$content = DefaultFixup($content); 


	// Check that we got a result and return it
	if($content)
	{
		$rval['content'] = $content;
		return $rval;
	}
	else
	{
		$rval['code'] = -3;
		$rval['error'] = "No content returned after fixup from '$resource' for '$page_id'";
		logger(TRLOG_WARN,$rval['error']);
		return $rval;
	}

}

?>
