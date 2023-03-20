<?php
// IMPORTANT! This Yahoo! code has not been used or tested in a long time.
//    In fact, it is very likely that it doesn't work!
//    If you require Yahoo! support, please seek assistance in the support forums. 

require_once("tr_fixup.php");

//TODO: Yahoo translation requires an IP hostname, should code a workaround for this
$engines['y'] = array(		//'trans_url'		=> 'http://74.6.146.244/babelfish/translate_url_content?lp=en_$lang&trurl=$url',
						'trans_url'			=> 'http://72.30.186.56/babelfish/translate_url_content?lp=en_$lang&trurl=$url',
						'timeout'			=> 15,
						'max_redir'			=> 3,
						'check_func'		=> 'YahooCheck',
						'fixup_func'		=> 'YahooFixup'
);		

function YahooCheck($content,$params)
{
 	$rval = array('result'	=>	'OK');

	//Check for redirects
	if (preg_match('/<frame name="BabelFishBody" SRC="(.*?)"/',$content,$matches)) {
            $rval['result']='REDIRECT';
            $rval['redirect']=$matches[1];
            $rval['message']='FrameBody';
			return $rval;
	}
	if (preg_match('/<meta http-equiv="refresh" content="0;URL=(.*?)"/',$content,$matches)) {
            $rval['result']='REDIRECT';
            $rval['redirect']=$matches[1];
			$rval['message']='meta refresh';
			return $rval;
	}
	if (preg_match('/<script>document.location=\'(.*?)\';/',$content,$matches)) {
            $rval['result']='REDIRECT';
            $rval['redirect']=$matches[1];
			$rval['message']='script location';
			return $rval;
	}

	//Check for indications of errors
	if (strpos($content,'<title>Yahoo! Babel Fish'))
	{
        $ret['result']='ERROR';
        $ret['error']='Yahoo content indicates error. (Title: Yahoo)';
		return $rval;
	}

	if ($params['validation_tag'] and !strpos($content,$params['validation_tag']))
	{
        $ret['result']='ERROR';
        $ret['error']="YahooCheck failed to match validation_tag '{$params['validation_tag']}'";
		return $rval;
	}

	return $rval;
}

function YahooFixup($content,$params)
{
	// Clean up translator links
	function y1($m) { return '<a href="' . rawurldecode($m[1]) . $m[2]; } 
		
	// Clean up translator links
	$rstr = '|<a.*?href="http://[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/babelfish/.*?trurl=(.*?)(".*?</a>)|i';
	$content = preg_replace_callback($rstr,'y1',$content);

	//Remove yahoo script
	$rstr = '|<!-- SpaceID=.*?<!--.*?.yahoo.com.*?-->|i';
	$content = preg_replace($rstr,'',$content);
	
	return DefaultFixup($content,$params);
}




?>
