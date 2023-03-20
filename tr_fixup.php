<?php

require_once("tr_log.php");

//$customTagFixup = '';

function LogPregLastError()
{
	$err = preg_last_error();

	switch ($err) {
	case PREG_NO_ERROR:
		$err_str = "PREG_NO_ERROR";
		break;
	case PREG_INTERNAL_ERROR:
		$err_str = "PREG_INTERNAL_ERROR";
		break;
	case PREG_BACKTRACK_LIMIT_ERROR:
		$err_str = "PREG_BACKTRACK_LIMIT_ERROR";
		break;
	case PREG_RECURSION_LIMIT_ERROR:
		$err_str = "PREG_RECURSION_LIMIT_ERROR";
		break;
	case PREG_BAD_UTF8_ERROR:
		$err_str = "PREG_BAD_UTF8_ERROR";
		break;
	case PREG_BAD_UTF8_OFFSET_ERROR:
		$err_str = "PREG_BAD_UTF8_OFFSET_ERROR";
		break;
	default:
		$err_str = "UNKNOWN";
		break;
	}

	if ($err != PREG_NO_ERROR )
	{
		logger(TRLOG_WARN,"preg_last_error: " . preg_last_error() . "(" . $err_str . ")" );
	}
	else
	{
		logger(TRLOG_DEBUG,"preg_last_error: " . preg_last_error() . "(" . $err_str . ")" );
	}
}

function PurtyNewlines($content)
{
	$content = preg_replace('#(<!doctype.*?' . '>)([^\n])#i',	"\\1\n\\2", $content);
	$content = preg_replace('#(</?html>)([^\n])#i',				"\\1\n\\2", $content);
	$content = preg_replace('#(</?head>)([^\n])#i',				"\\1\n\\2", $content);
	$content = preg_replace('#(</?meta.*?>)([^\n])#i',				"\n\\1\n\\2", $content);
	$content = preg_replace('#(<link.*?>)([^\n])#i',			"\n\\1\n\\2", $content);
	$content = preg_replace('#(</?body>)([^\n])#i',				"\\1\n\\2", $content);
	$content = preg_replace('#(</h.>)([^\n])#i',				"\\1\n\\2", $content);
	$content = preg_replace('#(-->)([^\n])#i',					"\\1\n\\2", $content);
	$content = preg_replace('#(</script>)([^\n])#i',			"\\1\n\\2", $content);
	$content = preg_replace('#(<script.*?>)([^\n])#i',			"\n\\1\n\\2", $content);
	$content = preg_replace('#(</script>)([^\n])#i',			"\\1\n\\2", $content);
	$content = preg_replace('#(</?noindex.*?>)([^\n])#i',			"\\1\n\\2", $content);

	//$content = preg_replace('#(</?ul>)([^\n])#i',				"\\1\n\\2", $content);
	//$content = preg_replace('#(</?div.*?' . '>)([^\n])#i',		"\\1\n\\2", $content);
	//$content = preg_replace('#(</li>)([^\n])#i',				"\\1\n\\2", $content);
	//$content = preg_replace('#(</p>)([^\n])#i',					"\\1\n\\2", $content);
	//$content = preg_replace('#(<br.*?' . '>)([^\n])#i',			"\\1\n\\2", $content);
	//$content = preg_replace('#(</a>\s+\<a)([^\n])#i',				"\\1\n\\2", $content);
	//$content = preg_replace('#(</?table>)([^\n])#i',				"\\1\n\\2", $content);
	//$content = preg_replace('#(</?tr>)([^\n])#i',				"\\1\n\\2", $content);
	//$content = preg_replace('#(</?td>)([^\n])#i',				"\\1\n\\2", $content);

	return $content;
}


//FIXME: this will break attrs with embedded spaces, rework.  split on =
/*function QuoteTagAttrs($content)
{
	$mstr = '#=\s*"?([^ >"]*)"?#is';
	$rstr = '="$1"';
	$content = preg_replace($mstr,$rstr,$content);
	return $content;
}*/
function QuoteTagAttrs($content)
{
	//$content = preg_replace('/[\s]{2,}/', ' ', $content);
	$content = preg_replace ('/\s+/', '',  $content) ;
	$content = trim($content);
	return $content;
}


//Rewrite our host URLs to the proper translated namespace (subdomain or subdirectory)
function FixupHostUrls($content)	//TODO: rename FixupSiteUrl
{
	global $params, $url_rewrite_style;
	$site_url = preg_quote($params['site_url'], "'");
	$pattern = "'" . '(' . $site_url . '[\/]?|\#)([^">\r\n]*)' . "'imx";   // добавил |\# чтобы обрезать ="#" корневые ссылки
	$replace = (($url_rewrite_style == 'ABSOLUTE') ? $params['translated_base_href'] . '/' : '') . '$2';
	$content = preg_replace($pattern, $replace, $content);
	return $content;

	////$rstr = '|(' . $params['site_url'] . '\//?)([^">\r\n]*)|i';
	//$rstr = '|(' . $params['site_url'] . '/?)([^">\r\n]*)|i';
	////$rstr = '|href\s*=\s*"?(' . $params['site_url'] . '/?)([^">\r\n]*)"|i';
	//if ($url_rewrite_style == 'ABSOLUTE')
	//{
	//$rplstr = $params['translated_base_href'] . '/$2';
	////absolute urls from original href
	////$rplstr = '$1/' . $params['trans_dir'] . '/$2"';
	//}
	////elseif ($url_rewrite_style == 'RELATIVE')
	//else //default
	//{
	//$rplstr = '$2';
	//}
	////logger(TRLOG_TRACE,"regex: rstr=" . $rstr . " rplstr=" . $rplstr);
	//$content = preg_replace($rstr, $rplstr, $content);
	//return $content;
}

function TagFixup($content)
{
	global $params, $customTagFixup;
	//TODO: should we remove any embedded new lines first?
	if ($customTagFixup)
		$content = $customTagFixup($content);
	$content = FixupHostUrls($content);
	return $content;
}

function TagCallback($m)
{
	//echo "tag : " . $m[1] . "\n";
	$content = TagFixup($m[1]);
	return $content;
}

function ProcessTags($input)
{
	$mstr = '#(<.*?' . '>)#s';
	$output = preg_replace_callback($mstr,'TagCallback',$input);
	return $output;
}

function ProcessComment($input)
{
	//echo "comment: " . $input . "\n";
	//TODO: allow per engine comment processing
	//return "";  //remove all comments
	return $input;
}

function CommentCallback($m)
{
	//echo "before : " . $m[1] . "\n";
	//echo "comment: " . $m[2] . "\n";
	//echo "rest   : " . $m[3] . "\n";

	$r1 = ProcessTags($m[1]);
	$r2 = ProcessComment($m[2]);
	$r3 = ProcessCommentGroups($m[3]);	//Recursive

	if (! $r3)
		$r3 = ProcessTags($m[3]);

	return $r1 . $r2 . $r3;
}

function ProcessCommentGroups($input)  //TODO: rename
{
	$mstr = '#(.*?)(<!--.*?-->)(.*)#s';
	$output = preg_replace_callback($mstr,'CommentCallback',$input);
	return $output;
}




function DefaultFixup($content)
{
	global $params;

 	if (!$content) { logger(TRLOG_WARN, 'No content at beginning of DefaultFixup'); return ''; }

 	// Get rid of <base href>
	$content = preg_replace("'" . '[\<] base [\s]+ [^\>]* [\>]' . "'ix", '', $content, 1);
	if (!$content) { logger(TRLOG_WARN, 'No content after DefaultFixup base'); LogPregLastError(); return ''; }

	// Add our own <base href> after <head>
 	$rplstr = '<head><base href="http://' . $_SERVER['HTTP_HOST'] . '/"/>';
	$content = preg_replace('|<\s*head\s*>|i',$rplstr,$content);
	if (! $content) { logger(TRLOG_WARN,"No content after DefaultFixup add base href"); LogPregLastError(); return ''; }


		///////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////
		// СОБСТВЕННЫЕ НАСТРОЙКИ
		///////////////////////////////////////////////////////////////////

		// Подключаем style.css для перевода
		//$rplstr = '<link rel="stylesheet" type="text/css" href="/mycode/translate/style-translate.css"></head>';
		//$content = preg_replace('|<\s*\/head\s*>|i',$rplstr,$content);

		//$rplstr = '<div id=content-text><p align="justify" class="warning-translate">This page has been robot translated, sorry for typos if any.</p>';
		//$content = preg_replace("/<div id=content-text>/is",$rplstr,$content);

		// Вырезаем ПРИВАТ
		//$content=preg_replace("/<!-- PPRIVAT FLY -->(.*)<!-- PPRIVAT FLY END -->/is", "", $content);
		// Вставляем ПРИВАТ
		//ob_start();
		//	eval('@include("/var/www/admin/data/www/shram.kiev.ua/mycode/privatbank/fly2/fly.php");');
		//	print '</body>';
		//	$rplstr = ob_get_contents();
		//ob_end_clean();
		//$content = preg_replace('|<\/body>|i',$rplstr,$content);
		// Подключаем скрипт кукисов и закрытия ПРИВАТ TRANSLATE
		//$rplstr ='<script src="http://www.shram.kiev.ua/mycode/privatbank/translate/script.php"></script></body>';
		//$content = preg_replace('|<\/body>|i',$rplstr,$content);
		// Подключаем стиль ПРИВАТ TRANSLATE
		//$rplstr ='<style>.close_button{background: url(/templates2/images/close_small2.png) 0px 0px no-repeat;position:absolute;right:0px;top:-20px;text-decoration: none !important;width:20px;height:20px;z-index:9000}.close_button span {cursor:pointer}</style></body>';
		//$content = preg_replace('|<\/body>|i',$rplstr,$content);
		// Подключаем ифрейм FLY ПРИВАТ TRANSLATE
		//$rplstr ='<div  id="pbtranslate2" style="position:fixed;top:50%;left:30%;height:350px;width:530px;"><div class="close_button"><span onclick="confirm()" title="Закрыть">&nbsp;&nbsp;&nbsp;</span></div><iframe scrolling="no" id="pbtranslate" height="350" width="530" frameborder="0" style="padding:0px;margin:0px;" src="http://www.shram.kiev.ua/mycode/privatbank/translate/fly.php"></iframe></div></body>';
		//$content = preg_replace('|<\/body>|i',$rplstr,$content);


	//Process all the tags
	//$content = ProcessCommentGroups($content);

	//Beautify output
	//$content = PurtyNewlines($content);

	return $content;
}


?>
