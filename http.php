<?php
// 是否是 windows 操作系统
defined('IS_WINDOWS') || define('IS_WINDOWS', strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

class Http
{
    /**
     * 发送http请求时使用的浏览器用户代理
     */
    const USERAGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0';
    
    /**
     * 各个媒体站点的 HTTP 响应头设置的 cookie
     * 
     * @var array
     */
    public $domainCookies = array();

    /**
     * curl 默认选项
     *
     * @var array
     */
    public function defaultOpts()
    {
        return array(
            CURLOPT_USERAGENT      => self::USERAGENT, // 浏览器用户代理
            CURLOPT_MAXREDIRS      => 5, // 最大重定向次数
            CURLOPT_TIMEOUT        => 0, // 接口请求的超时时间
            CURLOPT_FOLLOWLOCATION => true, // 是否继续请求 Location header 指向的 URL 地址
            CURLOPT_HEADER         => false, // 在输出中包含 HTTP头
            CURLOPT_RETURNTRANSFER => true, // 以字符串形式返回 HTTP 响应，而不是在页面直接输出内容
            CURLOPT_FAILONERROR    => true, // 在发生错误时，不返回错误页面（例如 404页面）
            CURLOPT_CONNECTTIMEOUT => 10, // 连接超时时间
            CURLOPT_SSL_VERIFYHOST => 2,  // 2 - 检查公用名是否存在，并且是否与提供的主机名匹配
            CURLOPT_SSL_VERIFYPEER => 0,  // 网站SSL证书验证，不推荐设为0或false，设为0或false不能抵挡中间人攻击
                // ref. http://us2.php.net/manual/en/function.curl-setopt.php#110457
                // Turning off CURLOPT_SSL_VERIFYPEER allows man in the middle (MITM) attacks, which you don't want!
            // CURLOPT_CAINFO         => __DIR__ . '/cacert.pem',  // CA证书
            // 如需更新，从 http://curl.haxx.se/ca/cacert.pem 获取
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            // ref. http://www.cnblogs.com/cfinder010/p/3192380.html
            // 如果开启了IPv6，curl默认会优先解析 IPv6，在对应域名没有 IPv6 的情况下，
            // 会等待 IPv6 dns解析失败 timeout 之后才按以前的正常流程去找 IPv4。
            // curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4) 
            // 只有在php版本5.3及以上版本，curl版本7.10.8及以上版本时，以上设置才生效。
            // CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_0,  // 采用 HTTP 1.0 协议
        );
    }

    /**
     * 当前请求所使用的 cookie 文件的存储路径
     * 
     * @var string
     */
    public $cookiePath = './cookie.txt';

    /**
     * 当前的 curl 句柄
     * 
     * @var resource
     */
    protected $ch = null;

   /**
     * 发送 http get 请求
     * 
     * @param  string $url url 请求地址
     * @param  array $options 自定义 curl 参数
     * @return string 返回 http 请求的 body
     */
    public function get($url, $options = array(),$ssl=true)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $opts = $options + $this->defaultOpts();
        if(!$ssl){
            unset($ssl['CURLOPT_SSL_VERIFYPEER']);
            unset($ssl['CURLOPT_SSL_VERIFYHOST']);
        }
        curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);
        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    /**
     * 发送 http post 请求 (Content-Type 为 application/x-www-form-urlencoded)
     * 
     * @param  string $url url 请求地址
     * @param  array $params post 参数数组
     * @param  array $options 自定义 curl 参数
     * @param  string $charset 字符集
     * @return string 返回 http 请求的 body
     */
    public function post($url, $params, $options = array(), $charset = 'UTF-8')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $opts = $options + $this->defaultOpts();
        curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, $charset));
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);

		// CURLINFO_HEADER_OUT 和 CURLOPT_VERBOSE 有冲突，只能采用一种
		// ref. https://bugs.php.net/bug.php?id=65348
		// curl_setopt($ch, CURLOPT_VERBOSE, true);
		// $fp = fopen('php://temp', 'r+');
		// curl_setopt($ch, CURLOPT_STDERR, $fp);
        
        $html = curl_exec($ch);
        
		//~ rewind($fp);
		//~ $debug_info = stream_get_contents($fp);
		//~ var_dump($debug_info);
		//~ fclose($fp);
        
        curl_close($ch);

        return $html;
    }
}
