<?php
namespace barley\functions;

/**
 * 常用公共方法
 */

class Functions
{
    /**
     * CURL请求
     * @param $url 请求url地址
     * @param $method 请求方法 get post
     * @param null $postfields post数据数组
     * @param array $headers 请求header信息
     * @param bool|false $debug 调试开启 默认false
     * @return mixed
     */
    public static function httpRequest($url, $method = 'get', $postfields = null, $headers = array(), $debug = false)
    {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? true : false;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);
        $arr = json_decode($response, 1);
        return $arr;
    }

    /**
     * 去除html标签
     * @param $str
     * @param string $tags 标签
     * @return string 去除后的html
     */
    public static function cleanhtml($str, $tags='<p><br><img>')
    {
        $search = array(
            '@<script[^>]*?>.*?</script>@si',// 除去JavaScript
            '@<[\/\!]*?[^<>]*?>@si',//除去html标记*/
            '@<style[^>]*?>.*?</style>@siU',// Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'// 带多行注释包括CDATA
        );
        $str = preg_replace($search, '', $str);
        $str = strip_tags($str, $tags);
        return $str;
    }

    /**
     * 把数组按指定的个数分隔
     * @param array $array 要分割的数组
     * @param int $groupNum 分的组数
     */
    public static function splitArray($array, $groupNum)
    {
        if (empty($array)) {
            return array();
        }

        //数组的总长度
        $allLength = count($array);

        //个数
        $groupNum = intval($groupNum);

        //开始位置
        $start = 0;

        //分成的数组中元素的个数
        $enum = (int)($allLength/$groupNum);

        //结果集
        $result = array();

        if ($enum > 0) {
            //被分数组中 能整除 分成数组中元素个数 的部分
            $firstLength = $enum * $groupNum;
            $firstArray = array();
            for ($i=0; $i<$firstLength; $i++) {
                array_push($firstArray, $array[$i]);
                unset($array[$i]);
            }
            for ($i=0; $i<$groupNum; $i++) {

                //从原数组中的指定开始位置和长度 截取元素放到新的数组中
                $result[] = array_slice($firstArray, $start, $enum);

                //开始位置加上累加元素的个数
                $start += $enum;
            }
            //数组剩余部分分别加到结果集的前几项中
            $secondLength = $allLength - $firstLength;
            for ($i=0; $i<$secondLength; $i++) {
                array_push($result[$i], $array[$i + $firstLength]);
            }
        } else {
            for ($i=0; $i<$allLength; $i++) {
                $result[] = array_slice($array, $i, 1);
            }
        }
        return $result;
    }

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest 目标文件夹
     */
    public static function copydirs($source, $dest)
    {
        define('DS', DIRECTORY_SEPARATOR);
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

    /**
     * 删除文件夹
     * @param string $dirname 目录
     * @param bool $withself 是否删除自身
     * @return boolean
     */
    public static function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }

    /**
     * 判断文件或文件夹是否可写
     * @param    string $file 文件或目录
     * @return    bool
     */
    public static function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

    /**
     * 将时间戳转换为日期时间
     * @param int $time 时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    public static function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }

    /**
     * 保留键值,二维数组去重
     * @param $array2D
     * @param bool $stkeep
     * @param bool $ndformat
     * @return mixed
     */
    public static function unique_arr($array2D, $stkeep=false, $ndformat=true)
    {
        $joinstr='+++++';
        // 判断是否保留一级数组键 (一级数组键可以为非数字)
        if ($stkeep) {
            $stArr = array_keys($array2D);
        }
        // 判断是否保留二级数组键 (所有二级数组键必须相同)
        if ($ndformat) {
            $ndArr = array_keys(end($array2D));
        }
        //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
        foreach ($array2D as $v) {
            $v = join($joinstr, $v);
            $temp[] = $v;
        }
        //去掉重复的字符串,也就是重复的一维数组
        $temp = array_unique($temp);
        //再将拆开的数组重新组装
        foreach ($temp as $k => $v) {
            if ($stkeep) {
                $k = $stArr[$k];
            }
            if ($ndformat) {
                $tempArr = explode($joinstr, $v);
                foreach ($tempArr as $ndkey => $ndval) {
                    $output[$k][$ndArr[$ndkey]] = $ndval;
                }
            } else {
                $output[$k] = explode($joinstr, $v);
            }
        }
        return $output;
    }


    /**
     * XML转Array
     * @param $xml
     * @return array
     */
    public static function toArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     * 删除文件和文件夹
     * @param $path
     * @param bool $delDir
     * @return bool
     */
    public static function delDirAndFile($path, $delDir = false)
    {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
                }
            }
            closedir($handle);
            if ($delDir) {
                return rmdir($path);
            }
        } else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return false;
            }
        }
    }


    /**
     *  将数组转换为xml
     *  @param array $data  要转换的数组
     *  @param bool $root   是否要根节点
     *  @return string     xml字符串
     */
    public static function arr2xml($data, $root = true)
    {
        $str="";
        if ($root) {
            $str .= "<xml>";
        }
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $child = arr2xml($val, false);
                $str .= "<$key>$child</$key>";
            } else {
                $str.= "<$key><![CDATA[$val]]></$key>";
            }
        }
        if ($root) {
            $str .= "</xml>";
        }
        return $str;
    }

    /**
     * arrToStr 数组转为字符串
     * @param $array
     * @return 字符串
     */
    public static function arrToStr($array)
    {
        // 定义存储所有字符串的数组
        static $r_arr = array();
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    // 递归遍历
                    arrToStr($value);
                } else {
                    $r_arr[] = $value;
                }
            }
        } elseif (is_string($array)) {
            $r_arr[] = $array;
        }
        //数组去重
        $r_arr = array_unique($r_arr);
        $string = implode(",", $r_arr);
        return $string;
    }


    /**
 * 检查手机格式，中国手机不带国家代码，国际手机号格式为：国家代码-手机号
 * @param $mobile
 * @return bool
 */
    public static function check_mobile($mobile)
    {
        if (preg_match('/(^(13\d|14\d|15\d|16\d|17\d|18\d|19\d)\d{8})$/', $mobile)) {
            return true;
        } else {
            if (preg_match('/^\d{1,4}-\d{5,11}$/', $mobile)) {
                if (preg_match('/^\d{1,4}-0+/', $mobile)) {
                    //不能以0开头
                    return false;
                }

                return true;
            }

            return false;
        }
    }


    //计算两个经纬度之间的距离
    public static function getDistance($lat1, $lng1, $lat2, $lng2)
    {

        //将角度转为狐度

        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度

        $radLat2 = deg2rad($lat2);

        $radLng1 = deg2rad($lng1);

        $radLng2 = deg2rad($lng2);

        $a = $radLat1 - $radLat2;

        $b = $radLng1 - $radLng2;

        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137;

        return $s;
    }

    /**
     * 二维数组根据指定键的值进行升序或是降序排序
     *
     * @param [type] $array  要排序的数组
     * @param [type] $field  根据数组中的那个字段
     * @param integer $type  默认1是升序,2是降序
     *
     * @return void
     */
    public static function mutiarray_sort($muti_array, $field, $type=1)
    {
        //根据字段$field对数组$array进行降序排列
        $sort_fields = array_column($muti_array, $field);
        switch ($type) {
            case 1:
                array_multisort($sort_fields, SORT_ASC, $muti_array);
                break;
            case 2:
                array_multisort($sort_fields, SORT_DESC, $muti_array);
                break;
        }
        
        return $muti_array;
    }

    /**
     * 对象转数组
     */
    public static function object2array($obj)
    {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }
    
        if (is_array($obj)) {
            return array_map(__FUNCTION__, $obj);
        } else {
            return $obj;
        }
    }

    /**
     * 文件上传,tp5使用
     */
    public static function file2project($keyword = 'file', $keep_name = false)
    {
        $file = request()->file($keyword);
        $path = ROOT_PATH.'public'.DS.'uploads';

        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            if ($keep_name) {
                $info = $file->move($path, '');
            } else {
                $info = $file->move($path);
            }

            if ($info) {
                $file_patah = '/uploads/'.$info->getSaveName();

                return str_replace('\\', '/', $file_patah);
            } else {
                echo $file->getError();
            }
        } else {
            return -1;
        }
    }

    /**
 * mongo恢复备份的数据库文件
 *
 * @param      $bak_path   备份文件的路径,需要加上备份的数据库的文件夹名称:例如:D:/bak/iots
 * @param null $mongo_path mongodb的安装目录,必须指向bin目录
 * @param null $host       host
 * @param null $port       端口
 * @param null $username   用户名
 * @param null $password   密码
 * @param null $database   要恢复的数据库的名字
 *
 * @return int
 */
    public static function mongo_recover($bak_path, $mongo_path = null, $host = null, $port = null, $username = null, $password = null, $database = null)
    {
        //数据库账号
        $db_user = $username ?? "";
        //数据库密码
        $db_pwd = $password ?? "";
        //host
        $host = $host ?? "127.0.0.1";
        $port = $port ?? "27017";
        //数据库名
        $db_name = $database ?? "iots";
        if (!is_dir($bak_path)) {
            return 1;
        }
        //mongo路径,文件夹不能有空格
        $mongo     = $mongo_path ?? "D:/bin";
        $dump_path = str_replace('\\', '/', $mongo).'/'.'mongorestore';
        //要执行的命令
        $exec = $dump_path." -h ".$host.':'.$port." -d ".$db_name.' '.$bak_path;

        exec($exec, $info, $status);

        return $status;
    }

    /**
 * mongo备份文件
 *
 * @param      $bak_path   数据备份的路径,全路径
 * @param null $mongo_path mongodb安装路径,必须指定到bin目录下
 * @param null $host       mongodb的使用的host
 * @param null $username   用户名
 * @param null $password   密码
 * @param null $database   要备份的数据库
 *
 * @return mixed
 */
    public static function mongo_bak($bak_path, $mongo_path = null, $host = null, $port = null, $username = null, $password = null, $database = null)
    {
        //数据库账号
        $db_user = $username ?? "";
        //数据库密码
        $db_pwd = $password ?? "";
        //host
        $host = $host ?? "127.0.0.1";
        $port = $port ?? "27017";
        //数据库名
        $db_name = $database ?? "iots";

        //数据库文件存储路径
        if (!is_dir($bak_path)) {
            mkdir($bak_path, '0777', true);
        }

        //mongo路径,文件夹不能有空格
        $mongo     = $mongo_path ?? "D:/bin";
        $dump_path = str_replace('\\', '/', $mongo).'/'.'mongodump';
        //要执行的命令
        $exec = $dump_path." -h ".$host.':'.$port." -d ".$db_name.' -o '.$bak_path;

        exec($exec, $info, $status);

        return $status;
    }

    /**
     * 解压文件
     *
     * @param $file    压缩文件zip
     * @param $output  要解压缩到的目录
     *
     * @return bool
     */
    public static function un_zip($file, $output)
    {
        $zip  = new ZipArchive();
        $open = $zip->open($file);
        if ($open === true) {
            $xx = $zip->extractTo($output);
            $zip->close();

            return true;
        }
    }

    /**
 * 程序恢复公共方法
 *
 * @param $zip_file   压缩文件的全路径:例如:D:/file.zip
 * @param $to_path    要恢复的目录名:例如:D:/project
 *
 * @return \app\index\controller\booleam
 */
    public static function project_recover($zip_file, $to_path)
    {
        $zip          = new ZipClass();
        $unzip_result = $zip->unzip($zip_file, $to_path);

        return $unzip_result;
    }


    /**
 * 备份文件(程序整体备份)
 *
 * @param $bak_name  备份的名字,全路径加备份文件名字:例如: D:/bak.zip
 * @param $bak_dir   要备份的文件夹,例如: D:/project
 *
 * @return \app\index\controller\booleam
 */
    public static function project_bak($bak_name, $bak_dir)
    {
        $zip        = new ZipClass();
        $zip_result = $zip->zip($bak_name, $bak_dir);

        return $zip_result;
    }

    /**
 * mysql 根据sql文件恢复数据
 *
 * @param      $data_path  sql文件的全路径,例:D:/xx.sql
 * @param null $mysql_path mysql的安装路径,需要到安装目录的bin目录下
 * @param null $username   数据库用户名
 * @param null $password   数据库密码
 * @param null $database   要导入的数据库,如果不存在需要先创建
 *
 * @return int 成功返回0,失败返回1
 */
    public static function mysql_recover($data_path, $mysql_path = null, $username = null, $password = null, $database = null)
    {
        //设置时区,以防时区不正常的时候引起的错误
        date_default_timezone_set("Asia/Shanghai");

        //数据库账号
        $db_user = $username ?? config('database.username');
        //数据库密码
        $db_pwd = $password ?? config('database.password');
        //数据库名
        $db_name = $database ?? config('database.database');
        //数据库文件存储路径
        if (!is_file($data_path)) {
            return 1;
        }
        $name = str_replace('\\', '/', $data_path);
        //mysql路径
        $mysql     = $mysql_path ?? "D:/phpStudy/PHPTutorial/MySQL/bin";
        $dump_path = str_replace('\\', '/', $mysql).'/'.'mysql ';
        //要执行的命令
        $exec = $dump_path." -u".$db_user." -p".$db_pwd." ".$db_name." < ".$name;

        exec($exec, $info, $status);

        return $status;
    }

    /**
 * mysql导出sql文件
 *
 * @param      $bak_path   备份的路径,需要全路径
 * @param null $mysql_path mysql的安装路径,需要到mysql安装路径的bin文件夹下
 * @param null $bak_name   备份的名字
 * @param null $username   数据库用户名
 * @param null $password   数据库的密码
 * @param null $database   要操作的数据库
 *
 * @return mixed  成功返回0,失败返回1
 */
    public function mysql_back($bak_path, $mysql_path = null, $bak_name = null, $username = null, $password = null, $database = null)
    {
        //设置时区,以防时区不正常的时候引起的错误
        date_default_timezone_set("Asia/Shanghai");

        //数据库账号
        $db_user = $username ?? config('database.username');
        //数据库密码
        $db_pwd = $password ?? config('database.password');
        //数据库名
        $db_name = $database ?? config('database.database');
        //备份文件名
        $filename = $bak_name ?? (date("Y-m-d")."-".time());
        //数据库文件存储路径
        if (!is_dir($bak_path)) {
            mkdir($bak_path);
        }
        $name = str_replace('\\', '/', $bak_path).'/'.$filename.'.sql';
        //mysql路径
        $mysql     = $mysql_path ?? "D:/phpStudy/PHPTutorial/MySQL/bin";
        $dump_path = str_replace('\\', '/', $mysql).'/'.'mysqldump';
        //要执行的命令
        $exec = $dump_path." -u".$db_user." -p".$db_pwd." ".$db_name." > ".$name;

        exec($exec, $info, $status);

        return $status;
    }

    /**
 * 循环删除目录和文件
 *
 * @param string $dir_name 目录名
 *
 * @return bool
 */
    public static function delete_dir_file($dir_name)
    {
        $result = false;
        if (is_dir($dir_name)) { //检查指定的文件是否是一个目录
        if ($handle = opendir($dir_name)) {   //打开目录读取内容
            while (false !== ($item = readdir($handle))) { //读取内容
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name.DS.$item)) {
                        delete_dir_file($dir_name.DS.$item);
                    } else {
                        unlink($dir_name.DS.$item);  //删除文件
                    }
                }
            }
            closedir($handle);  //打开一个目录，读取它的内容，然后关闭
            if (rmdir($dir_name)) { //删除空白目录
                $result = true;
            }
        }
        }

        return $result;
    }

    public static function return_json($status, $code, $message, $data = [])
    {
        $data = ['status' => $status,'code' => $code,'message' => $message,'data' => $data];

        return json_encode($data, 320);
    }
}
