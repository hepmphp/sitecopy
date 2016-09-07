<?php
/*思路 下载列表页 在下载详情页 保存到指定文件夹*/
set_time_limit(0);
$base_url = 'http://www.jb51.net/list/list_%s_%s.htm';
$urls = array();
foreach(range(1,20) as $num){
	$urls[$num] = sprintf($base_url,5,$num);
}
 
$config['pregList'] = '/<div class="w690 fl">(.*)<\/div>/is';
 
// print_r();
$all_content_urls = array();
foreach(async_get_url($urls) as $url_data){
	preg_match($config['pregList'], $url_data, $match_list);
	$temp_urls =_striplinks($match_list[1],'http://www.jb51.net','/<a href="(\/article\/\d+\.htm)"/');
	$all_content_urls = array_merge($all_content_urls,$temp_urls);
}

$all_content_urls = array_unique($all_content_urls);
$reform_content_urls = array();
foreach($all_content_urls as $url){
	$url_id = preg_replace('/.*\/article\//','',$url);
	$reform_content_urls[$url_id] = $url;
}
 
$path = './jb51/ajax/';
if(!is_dir($path)){
	mkdir($path,0777,true);
}
foreach(array_chunk($reform_content_urls,100,TRUE) as $key=>$content_ulrs){
	$contents = async_get_url($content_ulrs);
	foreach($contents  as $id=>$content){
		file_put_contents($path.$id,$content);
	}
}


/**
 * 获取列表页所有url
 * @param $document 缩减后的html列表页
 * @param $baseUrl  页面链接
 * @param string $linkRule 启用特殊规则的链接
 * @return array
 */
function _striplinks($document, $baseUrl, $linkRule = '')
{
	if ($linkRule) {
		preg_match_all(
			$linkRule,
			$document,
			$links
		);
		while (list($key, $val) = each($links[1])) {
			if (!empty($val) && strpos($val, $baseUrl) != FALSE) {
				$match[] = $val;
			} else if (!empty($val)) {
				if (substr($val, 0, 7) == 'http://') {
					continue;
				} elseif (substr($val, 0, 1) == '/') {
					$match[] = $baseUrl . $val;
				} elseif (substr($val, 0, 2) == './') {
					$match[] = $baseUrl . substr($val, 1);
				} elseif (substr($val, 0, 3) == '../') {
					$match[] = $baseUrl . substr($val, 2);
				} else {
					$match[] = $baseUrl . '/' . $val;
				}
			}
		}
	} else {
		preg_match_all("'<\s*a\s.*?href\s*=\s*		# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx", $document, $links);
	    var_dump($links);
		// catenate the non-empty matches from the conditional subpattern
		while (list($key, $val) = each($links[2])) {
			if (!empty($val) && strpos($val, $baseUrl) != FALSE)
				$match[] = $val;
		}
		while (list($key, $val) = each($links[3])) {
			if (!empty($val) && strpos($val, $baseUrl) != FALSE)
				$match[] = $val;
		}
	}
	// return the links
	return array_unique($match);
}

function async_get_url($url_array, $wait_usec = 0)
    {
        if (!is_array($url_array))
            return false;
        $wait_usec = intval($wait_usec);
        $data = array();
        $handle = array();
        $running = 0;
        try {
            $mh = curl_multi_init(); // multi curl handler
           // $i = 0;
            foreach ($url_array as $key=>$url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return don't print
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip, deflate'));
                curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
                curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
                curl_multi_add_handle($mh, $ch); // 把 curl resource 放進 multi curl handler 裡
                $handle[$key] = $ch;
            }
            /* 執行 */
            /* 此種做法會造成 CPU loading 過重 (CPU 100%)
            do {
                curl_multi_exec($mh, $running);
                if ($wait_usec > 0) // 每個 connect 要間隔多久
                    usleep($wait_usec); // 250000 = 0.25 sec
            } while ($running > 0);
            */
            /* 此做法就可以避免掉 CPU loading 100% 的問題 */
            // 參考自: http://www.hengss.com/xueyuan/sort0362/php/info-36963.html
            /* 此作法可能會發生無窮迴圈 */
            /*
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            while ($active and $mrc == CURLM_OK) {
               if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
               }
            }
            */
            /*
            // 感謝 Ren 指點的作法. (需要在測試一下)
            // curl_multi_exec的返回值是用來返回多線程處裡時的錯誤，正常來說返回值是0，也就是說只用$mrc捕捉返回值當成判斷式的迴圈只會運行一次，而真的發生錯誤時，有拿$mrc判斷的都會變死迴圈。
            // 而curl_multi_select的功能是curl發送請求後，在有回應前會一直處於等待狀態，所以不需要把它導入空迴圈，它就像是會自己做判斷&自己決定等待時間的sleep()。
            */
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);
            /* 讀取資料 */
            foreach ($handle as $i => $ch) {
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (($status == 200 || $status == 302 || $status == 301)) {
                    //echo $url_array[$i] . PHP_EOL;
                    $content = curl_multi_getcontent($ch);
                    $data[$i] = (curl_errno($ch) == 0) ? $content : false;
                }
                curl_close($ch);
                curl_multi_remove_handle($mh, $ch); /* 移除 handle*/
            }
            curl_multi_close($mh);
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        return $data;
    }


