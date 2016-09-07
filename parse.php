<?php
set_time_limit(0);
error_reporting(E_ALL);
require 'http.php';
include_once './ImageLocal.php';

$image_localization = new ImageLocal();

if (is_file('cookie.txt')) {
	unlink('cookie.txt');
}
$http = new Http();
 
$loginParams = array (
		'fastloginfield'=>'xxx',
		'username'=>'xxx',
		'password'=>'xxx',
		'quickforward'=>'xxx',
		'handlekey'=>'xxxx',

	);
$html = $http->post('http://www.91x1.com/member.php?mod=logging&action=login&loginsubmit=yes&infloat=yes&lssubmit=yes&inajax=1', $loginParams);

// var_dump($html);exit();

define('HTML','./jb51/javascript/');
$all_files = glob(HTML.'*.htm');
 natsort($all_files);
// var_dump($all_files);exit();
$save_tags =  '<div><p><img><style><table><tbody><thead><th><tr><td><wbr>';
foreach($all_files as $file){
	$data = file_get_contents($file);
	preg_match('/<h1 class="YaHei">(.*)<\/h1>/',$data,$match_title);
	preg_match('/<div id="content">(.*?)<\/div><!--endmain-->/is',$data,$match_content);
	$content = $match_content[1];
    // $content = strip_tags($content, $save_tags);
	/**************************程序自动对文章进行排版****************************/
	// /* P标签换成BR */
	// $content = preg_replace('/<\/?p[^>]*>/i','<br>',$content);
	// /* 换成2个BR */
	// $content = preg_replace('/<br[\s\/br><&nbsp;]*>(\s*|&nbsp;)*/i','<br><br>&nbsp;&nbsp;&nbsp;&nbsp;',$content);
	/**************************排版结束**************************************/
	$content = preg_replace('/<div class="codetitle">(.*?)<\/div>/','',$content);
	$content = preg_replace_callback('/<div class="codebody" id="code\d+">(.*?)<\/div>/is',function ($matches) {
            return $matches[1];
        },$content);
	$post_data = array(
		'posttime'=>time(),
		'wysiwyg'=>1,
		'subject'=>iconv('gbk','utf-8',$match_title[1]),
		'message'=>$image_localization->localization(1,iconv('gbk','utf-8',$content)),
		'allownoticeauthor'=>1,
		'usesig'=>1,
		'htmlon'=>1,
		'smileyoff'=>1,
	);
	$post_url = 'http://www.91x1.com/forum.php?mod=post&action=newthread&fid=37&extra=&topicsubmit=yes';
	$result = $http->post($post_url,$post_data);
	unlink($file);
}
 
 

