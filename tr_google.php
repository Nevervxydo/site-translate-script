<?php
require_once("tr_fixup.php");


///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
// Define the Google engine settings for translate to use.
//
// ÄÎÁÀÂÈË ÑÎÁÑÒÂÅÍÍÛÅ ÍÀÑÒĞÎÉÊÈ â translate.php ïî ïàğàìåòğó $inlang + ÏÅĞÅÍÅÑ â htaccess
//
$engines['g'] = array(
	'trans_url'			=> 'http://translate.google.com/translate?hl=ru&sl=ru&tl=$lang&u=$url&prev=hp',
	// ÍÀÑÒĞÎÉÊÈ ÄËß translate.php  'trans_url'			=> 'http://translate.google.com/translate?hl=$inlang&sl=$inlang&tl=$lang&u=$url&prev=hp',
 	'timeout'			=> 120,
	'max_redir'			=> 3,
	'check_func'		=> 'GoogleCheck',	//This function should determine if the translation was successful (and detect redirects)
	'fixup_func'		=> 'GoogleFixup',
	'tag_fixup_func'	=> 'GoogleTagFixup'
);


// print($inlang);
// echo"<br>";
// print($URL);
// echo"<br>";
// var_dump($inlangarray);
// echo"<br>";
// print($inlangarray[0]);
// echo"<br>";
// var_dump($engines['g']);
//http://translate.google.com/translate?hl=ru&sl=ru&tl=en&u=http://www.shram.kiev.ua%2Fiphone%2F&prev=h
//http://translate.googleusercontent.com/translate_c?depth=2&prev=hp&rurl=translate.google.com&tl=uk&u=http://www.shram.kiev.ua/&usg=ALkJrhghhHeHcNF5EQd94fr3yFTXw-sHwA




///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
//	GoogleCheck is called by translate to validate the returned
//  contents, and detect redirect conditions.
//
function GoogleCheck ($content, $params)
{
 	$rval = array('result' => 'OK');

	//Check for indications of errors
	if (strpos($content, '<title>403 Forbidden'))
	{
		$rval['result'] = 'ERROR';
		$rval['error'] = 'Google content indicates error. (Title: 403)';
		return $rval;
	}

	//Check for redirects
	if (preg_match('|<iframe sandbox="allow-same-origin allow-forms allow-scripts" src="(/translate_p\?[^"]+)|', $content, $matches)) { //if (preg_match('|<iframe src="(/translate_p\?[^"]+)|', $content, $matches)) {
		$rval['result']='REDIRECT';
		$match = $matches[1];
		$match = preg_replace('|&amp;|','&',$match);
		$match = 'http://translate.google.com' . $match;
		$rval['redirect']=$match;
		$rval['message']='translate_p_Frame';
		return $rval;
	}

//var_dump($content);exit;
//<iframe sandbox="allow-same-origin allow-forms allow-scripts" src="/translate_p?prev=hp&amp;rurl=translate.google.com&amp;tl=uk&amp;u=http://www.shram.kiev.ua/&amp;depth=2&amp;usg=ALkJrhh6rVf7W3Tedn0naw3Ezf6yd4DTzA" name=c frameborder="0" style="height:100%;width:100%;position:absolute;top:0px;bottom:0px;"></iframe>
//<iframe src="/translate_p?hl=ru&amp;ie=UTF8&amp;prev=_t&amp;sl=ru&amp;tl=en&amp;u=http://shram.kiev.ua/&amp;depth=1&amp;usg=ALkJrhifkq987wswYXLCeAWAgz282WheiA" name=c frameborder="0" style="height:100%;width:100%;position:absolute;top:0px;bottom:0px;"></iframe>
//<iframe src="/translate_p?hl=ru&amp;sl=auto&amp;tl=en&amp;twu=1&amp;u=http://www.shram.kiev.ua/&amp;usg=ALkJrhidCIvyTa4mP4h7gaqek0vhwAzWmw" name=c frameborder="0" style="height:100%;width:100%;position:absolute;top:0px;bottom:0px;">
// <iframe src="/translate_p?hl=ru&amp;prev=hp&amp;rurl=translate.google.com&amp;sl=ru&amp;tl=en&amp;twu=1&amp;u=http://www.shram.kiev.ua/maps/&amp;depth=1&amp;usg=ALkJrhieaaGSzukyJpOskursbQ32xRFpiw" name="c" frameborder="0" style="height:100%;width:100%;position:absolute;top:0px;bottom:0px;">&lt;/div&gt;</iframe>
// NEW http://translate.googleusercontent.com/translate_c?depth=1&hl=ru&prev=hp&rurl=translate.google.com&sl=ru&tl=en&twu=1&u=http://www.shram.kiev.ua/maps/&usg=ALkJrhhF9O4auDU_dhrJNmjn_SCf2NtL4Q
// OLD http://translate.googleusercontent.com/translate_c?hl=ru&prev=hp&rurl=translate.google.com&sl=ru&tl=en&twu=1&u=http://www.shram.kiev.ua/tests/&usg=ALkJrhjq6GM3MrS1UDUXyDQhIdCZludmQw
//<meta http-equiv="refresh" content="0;URL=http://74.125.67.132/translate_c?hl=en&amp;langpair=en%7Ces&amp;u=http://www.netbuilders.org/domaining/&amp;usg=ALkJrhhi9fkZqUuKk20-wQjgounHOH45PQ">
//http://translate.googleusercontent.com/translate_c?hl=ru&prev=hp&rurl=translate.google.com&sl=ru&tl=en&twu=1&u=http://www.shram.kiev.ua/tests/&usg=ALkJrhhMx_o26a5voRquSHlobU9Fo8_C0Q
//àäğåñ ïåğåâîä÷èêà
//http://translate.google.com.ua/translate?hl=ru&sl=auto&tl=en&u=http%3A%2F%2Fwww.shram.kiev.ua%2F
//àäğåñ ôğåéìà
//<iframe src="/translate_p?hl=ru&amp;sl=auto&amp;tl=en&amp;twu=1&amp;u=http://www.shram.kiev.ua/&amp;usg=ALkJrhidCIvyTa4mP4h7gaqek0vhwAzWmw" name=c frameborder="0" style="height:100%;width:100%;position:absolute;top:0px;bottom:0px;"></div></iframe>
//àäğåñ ïåğåâåäåííîãî ôğåéìà
// http://translate.googleusercontent.com/translate_c?hl=ru&prev=hp&rurl=translate.google.com&sl=ru&tl=en&twu=1&u=http://www.shram.kiev.ua/tests/&usg=ALkJrhjq6GM3MrS1UDUXyDQhIdCZludmQw
//IP àäğåñ translate.googleusercontent.com
//173.194.39.171


	//get the content redirect
	if (preg_match('|<meta http-equiv="refresh" content="0;URL=([^"]+)|',$content,$matches)) {
		$rval['result']='REDIRECT';
		$match = $matches[1];
		$match = preg_replace('|&amp;|','&',$match);
		$rval['redirect']=$match;
		$rval['message']='MetaRefresh';
		return $rval;
	}

	if ($params['validation_tag'] and !strpos($content,$params['validation_tag']))
	{
		$rval['result']='ERROR';
		$rval['error']="GoogleCheck failed to match validation_tag '{$params['validation_tag']}'";
		return $rval;
	}

	return $rval;
}




///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
//	Tag operations: work on each tag individually
//  (e.g. content between each < >)
//
function GoogleRemoveOnLoad($content)
{
	$rstr = '|onload="[^"]*?"|i';
	$content = preg_replace($rstr,'',$content);

	if (! $content) { logger(TRLOG_WARN,"No content after GoogleFixup onload"); LogPregLastError(); return ''; }

	return $content;
}

// Fixup the url parameters returned by Google
// This is the part after the "translate_c?"
// We assume the "&amp;prev" always follows the "u" paramater.  This could change at any time...
function GoogleExtractOrigUrl($u)
{

	//which form of translate link
	if (strpos($u,'translate_c'))
	{
		$u = preg_replace('|.*u=|i','',$u);	//remove preceeding parameters
		preg_match('|#[^ ">]*|',$u,$anchor_match);	//get page anchor
		$u = preg_replace('|\&amp;.*|i','',$u); //remove any following parameters
	}
	elseif (strpos($u,'translate_un'))
	{
		$u = preg_replace('|.*u=|i','',$u);	//remove preceeding parameters
		preg_match('|#[^ ">]*|',$u,$anchor_match);	//get page anchor
		$u = preg_replace('|\&prev.*|i','',$u); //remove any following parameters  (TODO: this is not test as of v9.9.11)

	}

	//append page anchor, if any
	if ($anchor_match)
		$u = $u . $anchor_match[0];

	$u = rawurldecode($u);
	return $u;
}

function fix_link_cb($m) { return GoogleExtractOrigUrl($m[1]) . $m[2]; }

function GoogleFixupTagLinks($content)
{
	//echo "GoogleFixupTagLinks called with: $content\n";
	//$rstr = '|(http://.+?/translate_.*?)([ ">])|i';		//FIXME: matching spaces may break things, but google is sending unquoted tag attributes...
	$rstr = '|(https?://.+?/translate_.*?)([ ">])|i';
	$content = preg_replace_callback($rstr,'fix_link_cb',$content);
	return $content;
}





///////////////////////////////////////////////////////////////////
//	GoogleTagFixup is called by translate for every tag
//
function GoogleTagFixup($content)
{
	$content = GoogleRemoveOnLoad($content);
	$content = GoogleFixupTagLinks($content);

	return $content;
}





///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
//	Bulk operations (work on the entire page content
//
function GoogleRemoveIFrame($content)
{

	$mstr = '#<iframe.*?translate\.google\.com.*?</iframe>#is';
	$rstr = '';
	$content = preg_replace($mstr,$rstr,$content);

	if (! $content) { logger(TRLOG_WARN,"No content after GoogleRemoveIFrame"); LogPregLastError(); return ''; }

	return $content;
}

function GoogleRemoveSpanWrappers($content)
{

	//$mstr = '#<span class="google-src-text notranslate".*?' . '>(.*?)</span>#is';
	$mstr = '#<span class="google-src-text".*?' . '>(.*?)</span>#is';
	$rstr = '';
	$content = preg_replace($mstr,$rstr,$content);

	$mstr = '#<span class="notranslate" onmouseover="_tipon\(this\)" onmouseout="_tipoff\(\)">(.*?)</span>#is';
	$rstr = '$1';
	$content = preg_replace($mstr,$rstr,$content);

	if (! $content) { logger(TRLOG_WARN,"No content after GoogleRemoveSpanWrappers"); LogPregLastError(); return ''; }

	return $content;
}

function GoogleRemoveScripts($content)
{
	// remove google translate scripts

	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	// !!!!!!!!!!!!  ÂÎÒ İÒÎ ËÎÌÀÅÒ ÍÎÂÛÉ ÏÅĞÅÂÎÄ

	//$rstr = '|<script src="http://(\S.*?)translate_c[^<]*?</script>|is';
	//$content = preg_replace($rstr,'',$content);
	$rstr = '|<script src="http://(\S.*?)translate_c[^<]*?</script>|';
	$content = preg_replace($rstr,'',$content);

	//$rstr = '|<script>.*?_intlStrings[^<]*?</script>|is';
	//$content = preg_replace($rstr,'',$content);
	$rstr = '|<script>.*?_intlStrings[^<]*?</script>|';
	$content = preg_replace($rstr,'',$content);

	//$rstr = '|<script>.*?function ti_\(\)[^<]*?</script>|is';
	//$content = preg_replace($rstr,'',$content);
	$rstr = '|<script>.*?function ti_\(\)[^<]*?</script>|';
	$content = preg_replace($rstr,'',$content);

	//$rstr = '|<script>.*?_setupIW\(\)[^<]*?</script>|is';
	//$content = preg_replace($rstr,'',$content);
	$rstr = '|<script>.*?_setupIW\(\)[^<]*?</script>|';
	$content = preg_replace($rstr,'',$content);

	$rstr = '|<script>(.*)timing(.*)</script><meta|';
	$content = preg_replace($rstr,'<meta',$content);

	if (! $content) { logger(TRLOG_WARN,"No content after GoogleRemoveScripts"); LogPregLastError(); return ''; }

	return $content;
}

function GoogleRemoveCss($content)
{
	// remove google css

	// $rstr = '|<style.*?type="text/css">\.google-src-text.*?</style>|is';

	$rstr = '|<style type="text/css">\.google-src-text[^<]*?</style>|is';
	$content = preg_replace($rstr,'',$content);

	if (! $content) { logger(TRLOG_WARN,"No content after GoogleRemoveCss"); LogPregLastError(); return ''; }

	return $content;
}







///////////////////////////////////////////////////////////////////
//	Google Fixup is called by translate for bulk fixup
//
function GoogleFixup($content)
{
	global $params;

	if (! $content) { logger(TRLOG_WARN,"No content at beginning of GoogleFixup"); return ''; }

	//Work on entire content (required for fixups that work on content between tags)
	//header('Content-Type: text/plain;');var_dump($content);exit;

	$content = GoogleRemoveIFrame($content);
	$content = GoogleRemoveOnLoad($content);
	$content = GoogleRemoveCss($content);
	$content = GoogleRemoveScripts($content);
	$content = GoogleRemoveSpanWrappers($content);

	//default fixups (tags, translated url mapping, etc)
	$content = DefaultFixup($content);

	return $content;

}
?>