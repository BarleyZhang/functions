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
     *
     * @param [type] $object
     *
     * @return void
     */
    public static function objectToArray($object)
    {
        $result = array();

        $object = is_object($object) ? get_object_vars($object) : $object;

        foreach ($object as $key => $val) {
            $val = (is_object($val) || is_array($val)) ? $this->objectToArray($val) : $val;

            $result[$key] = $val;
        }

        return $result;
    }
}
