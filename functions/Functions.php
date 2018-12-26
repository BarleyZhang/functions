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
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
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
    public static function cleanhtml($str,$tags='<p><br><img>'){
        $search = array(
            '@<script[^>]*?>.*?</script>@si',// 除去JavaScript
            '@<[\/\!]*?[^<>]*?>@si',//除去html标记*/
            '@<style[^>]*?>.*?</style>@siU',// Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'// 带多行注释包括CDATA
        );
        $str = preg_replace($search,'', $str);
        $str = strip_tags($str,$tags);
        return $str;
    }

    /**
     * 把数组按指定的个数分隔
     * @param array $array 要分割的数组
     * @param int $groupNum 分的组数
     */
    public static function splitArray($array, $groupNum){
        if(empty($array)) return array();

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

        if($enum > 0){
            //被分数组中 能整除 分成数组中元素个数 的部分
            $firstLength = $enum * $groupNum;
            $firstArray = array();
            for($i=0; $i<$firstLength; $i++){
                array_push($firstArray, $array[$i]);
                unset($array[$i]);
            }
            for($i=0; $i<$groupNum; $i++){

                //从原数组中的指定开始位置和长度 截取元素放到新的数组中
                $result[] = array_slice($firstArray, $start, $enum);

                //开始位置加上累加元素的个数
                $start += $enum;
            }
            //数组剩余部分分别加到结果集的前几项中
            $secondLength = $allLength - $firstLength;
            for($i=0; $i<$secondLength; $i++){
                array_push($result[$i], $array[$i + $firstLength]);
            }
        }else{
            for($i=0; $i<$allLength; $i++){
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
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item
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
	
}