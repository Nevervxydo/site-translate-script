<?php

//This is probably the only parameter that NEEDS to be changed
$params['site_url'] = '/';


// Number of days to use cached translation
// MUST BE SET
$translator_cache_days = 0;


//Make (a HEAD) request to the local server to verify that the requested resource exists before
//submitting it to a translation service.
$do_check_url = false;


//Misc translator options
$params['user_agent'] = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
$params['validation_tag'] = ''; //an optional string embedded in your content used as a sign of successful translation :-)


//If true host names other than www will be interpreted as a language code
//e.g. es.domain.com, it.domain.com, ...
//this will not work if you use naked domains (e.g. http://domain.com )
$use_lang_hostnames = false;


//Map directories to translation engines
$engine_dirs = array(
//			'g'	=>	'mycode/translate/g',
//			'y'	=>	'mycode/translate/y'
//old style
			'g'	=> 'lang',		//google engine will rewrite urls to use the lang directory
			'y' => 'ylang'
);


//Message to be sent if anything goes wrong
$error_msg = <<<EOT
<center>
<b>
<font color=red>RU:</font> Страница еще не готова, пожалуйста подождите 10 сек. и нажмите "F5" на Вашей клавиатуре через несколько секунд для обновления страницы.<Br>
<font color=red>EN:</font> Page not yet ready, please waite for 10 sec. and press "F5" on your keyboard after a few seconds to refresh the page.<Br>
<font color=red>FR:</font> La page n'est pas encore pret, s'il vous plait appuyez sur la touche "F5" de votre clavier apres quelques secondes pour rafraichir la page.<Br>
<font color=red>DE:</font> Page noch nicht fertig, drucken Sie bitte "F5" auf Ihrer Tastatur nach ein paar Sekunden, um die Seite zu aktualisieren.<Br>
</b>
<Br><BR>
<img src=/img/skin/load/load_big.gif>
</center>
EOT;


//language mappings (all codes will be converted to lower case before matching)
$lang_map = array(
//	'zh'=>'zh-cn','zhcn'=>'zh-cn',
//	'zt'=>'zh-tw',
//	'ger'=>'de',
//	'in'=>'hi'
	'bg'=>'uk','bg'=>'en','bg'=>'de','bg'=>'fr'
);

$remove_params = array('add','usg');


//1 The host to which we will submit our validation (empty string is will use the site_url DNS address)
//$check_url_proxy = '120.202.249.230:80';
$check_url_proxy = '213.85.92.10:80';

//2 If a proxy is needed to access external URLs, specify it here
$translate_proxy = '';

//Debugging options
$log_file='/var/www/admin/data/www/shram.kiev.ua/mycode/translate2/tmp/translate.log';  //set the log_file name to use, the server error log is used if not set (default)
require_once('/var/www/admin/data/www/shram.kiev.ua/mycode/translate2/tr_log.php');
$log_level = 'LOG_DEBUG';  //logging level (LOG_NEVER,LOG_ERROR,LOG_WARN,LOG_INFO,LOG_DEBUG,LOG_TRACE)
$params['write_raw_content'] = false; // false writes data received from translator server for debuggging purposes
$print_error_text = true;	//Control the printing of error text (to the client)

?>