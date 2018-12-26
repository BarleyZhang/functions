# functions
项目中常用的一些公用方法

1. cleanhtml($str,$tags='<p><br><img>')                            去除html标签

2. splitArray($array, $groupNum)                                   将数组分为指定个数

3. copydirs($source, $dest)                                        复制文件夹

4. rmdirs($dirname, $withself = true)                              删除文件夹

5. is_really_writable($file)                                       判断文件或文件夹是否可写

6. datetime($time, $format = 'Y-m-d H:i:s')                        将时间戳转换为日期时间

7. unique_arr($array2D,$stkeep=false,$ndformat=true)               保留键值,二维数组去重               

8. toArray($xml)                                                   XML转Array

9. delDirAndFile($path, $delDir = FALSE)                           删除文件和文件夹

10. arr2xml($data, $root = true)                                   将数组转换为xml

11. arrToStr ($array)                                              数组转为字符串
