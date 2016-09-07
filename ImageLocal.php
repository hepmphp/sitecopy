<?php 

class ImageLocal{
     public $remote_dir = '/data/www/images';
     public $local_dir = './images/';
     public $image_base_url = 'http://image.91x1.com';
     public static $http;
     /**
     * 图片本地化
     * @param $userid 用户id
     * @param $content 文章内容的html
     * @param string $pregImgRule 启用了自定义图片规则
     * @return mixed
     */
    public function localization($user_id, $content, $preg_img_rule = '')
    {
        preg_match_all('/(<img[\s\S]*?(><\/img>|>))/i', $content, $matchImg);
        if (!empty($matchImg[1])) {
            foreach ($matchImg[1] as $key => $img) {
                /*匹配图片 src width height*/
                $src = $width = $height = array();
                $s = $w = $h = '';
                if ($preg_img_rule) {
                    preg_match($preg_img_rule, $img, $src); //自定义规则
                } else {
                    preg_match('/src=["\']?(.+?)("|\'| |>|\/>){1}/i', $img, $src); //默认规则
                }
                preg_match('/width=["\']?(.*?)("|\'| |>|\/>){1}/i', $img, $width);
                preg_match('/height=["\']?(.*?)("|\'| |>|\/>){1}/i', $img, $height);
                $remoteFile = $src[1];
                if (isset($width[1])) {
                    $w = ' width=' . $width[1];
                }
                if (isset($height[1])) {
                    $h = ' height=' . $height[1] . ' ';
                }
                $imgExt = $this->get_file_ext($remoteFile);
                if (!preg_match('/^(jpg|gif|png|jpeg)$/i', $imgExt)) {
                    $imgExt = 'jpg';
                }
                $imgFileName = date('YmdHis').substr(md5($remoteFile),0,6). '.' . $imgExt;
                /*获取上传路径*/
                $path = $this->get_upload_path($user_id,'');
                /*本地临时存放的文件*/
                $newfilename = $this->get_img_name($imgFileName);
                $localFile = $this->local_dir . $newfilename;
                /*图片本地化*/
                $imgcontent = $this->http()->get($remoteFile);
                file_put_contents($localFile, $imgcontent);
                $ftpremoteFile =  $path.$newfilename;
                //同步到服务器
                $this->ftp_images($localFile,$ftpremoteFile);
                //替换图片链接
                $matchImg[3][$key] = '<img src=' . $this->image_base_url.$ftpremoteFile . $w . $h . ' />';
                // sleep(1);
            }
            $content = str_replace($matchImg[1], $matchImg[3], $content);
        }
        return $content;
    }

    public function http(){
        if(empty(self::$http)){
            include_once './http.php';
            self::$http = new Http();        
        }
        return self::$http;
    }

    public function ftp_images($localFile,$remoteFile){
	   include_once './Ftp.php';
       $rometeFileDir = dirname($remoteFile);
       $mkdirStatus =  Ftp::getInstance()->mk_subdirs('',$rometeFileDir);
       if($mkdirStatus){
           Ftp::getInstance()->upload($localFile,$remoteFile);
       }
    }

    /**
     * 获取文件后缀
     * @param $filePath 文件路径
     * @return string
     */
    public function get_file_ext($filePath)
    {
        return (trim(strtolower(substr(strrchr($filePath, '.'), 1))));
    }

    //获取图片的名字
    function get_img_name($imgname) {
        $begin = strlen($imgname) - strrpos($imgname, '.');
        $ext = substr($imgname, -$begin);
        srand(time());
        return date('Ymdhis') . rand(1000, 9000) . $ext;
    }
    /*
    根据根据用户ID获取相册图片上传目录
    */
    function get_upload_path($userid, $basepath = '') {
        if ($basepath) {
            $path = $basepath;
        } else {
            $path = '';
        }
        return $path . $this->get_id_folder($userid);
    }
    /**
    * 功能：获取用户上传文件的目录
    * @id 用户ID
    * @return array
    */
    function get_id_folder($id = 0) {
        $id_encode = md5($id);
        $folder_1 = substr($id_encode, 0, 2);
        $folder_2 = substr($id_encode, 2, 2);
        return '/' . $folder_1 . '/' . $folder_2 . '/' . $id . '/';
    }

}