<?php

/**
 * @description 给dedecms站群批量打补丁
 * @author: Deloz
 * @version 0.0.1
 * @date 2014.02.26
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(__FILE__).DS);
define('PATCH_DIR', ROOT_DIR.'patch'.DS);
define('START_TIME', microtime(true));
define('LOG_DIR', ROOT_DIR.'logs'.DS);

/********************************配置开始********************************/

//站群的根目录,如 e:/www
$www_root = 'e:/www';


//dedecms的后台路径名, 默认是 dede
$admin_pathname = 'nohoutai';


/********************************配置结束********************************/

$www_root = checkDir($www_root);

($patch_files = readTheDir(PATCH_DIR)) or showEror('PATH目录 [  '.PATCH_DIR.'  ] 下没有补丁...');

$fail_site_dirs = array();
$site_total = 0;
$www_di = new DirectoryIterator($www_root);

foreach ($www_di as $www_fileinfo) {
    if (in_array($www_fileinfo->getFilename(), array('.', '..')) || $www_fileinfo->isFile()) {
        continue;
    }
    $site_dir = $www_fileinfo->getPathname().DS;
    echo PHP_EOL, '##########################################', PHP_EOL;
    echo convert2gbk('当前站点: '), $site_dir, PHP_EOL, '------------------------------------------', PHP_EOL;

    $config_file = $site_dir.'data'.DS.'common.inc.php';
    if (!file_exists($config_file)) {
        echo convert2gbk('该站不是dedecms做的,跳过...'), PHP_EOL;
        echo '##########################################', PHP_EOL, PHP_EOL;
        continue;
    }

    $start_time = microtime(true);

    foreach ($patch_files as $patch_file) {
        $file_path_part = str_ireplace(PATCH_DIR, '', $patch_file);
        $first_part = '';
        
        if (false !== ($pos = stripos($file_path_part, '\\'))) {
            $first_part = substr($file_path_part, 0, $pos);            
        }

        $is_default_admin_pathname = ($first_part === 'dede' && (!file_exists($site_dir.'dede')));
        if ($is_default_admin_pathname) {
            $file_path_part = $admin_pathname.substr($file_path_part, $pos);
        }

        $dede_src_file = $site_dir.$file_path_part;
        
        if (file_exists($dede_src_file)) {
            if (is_writable($dede_src_file)) {
                copy($patch_file, $dede_src_file) or showEror('失败:: 复制[ '.$patch_files.' ] 到 [ '.$dede_src_file.' ]');
                echo convert2gbk('替换成功: '.$dede_src_file), PHP_EOL;
            } else {
                echo convert2gbk('文件 [ '.$dede_src_file.' ] 不可写.....跳过....'), PHP_EOL;
                $fail_site_dirs[] = '不可写   '.$dede_src_file;
            }
        } elseif ($first_part === 'dede') {
            $fail_site_dirs[] = '不存在   '.$dede_src_file;
            echo convert2gbk('!!!失败,后台目录的文件 [ '.$dede_src_file.' ] 不存在...'), PHP_EOL;
        }
    }
    
    if (!chmod($config_file, 0444)) {
        echo convert2gbk('无法设置common.inc.php为只读属性..'), PHP_EOL, PHP_EOL;
    }

    $end_time = microtime(true);
    $site_total++;

    echo PHP_EOL, convert2gbk('本站替换用时: '.($end_time - $start_time).'秒'), PHP_EOL;
    echo '##########################################', PHP_EOL, PHP_EOL;
}


echo PHP_EOL, PHP_EOL, convert2gbk('全部完成, 共 '.$site_total.' 个网站, 总用时: '.(microtime(true) - START_TIME).'秒'), PHP_EOL;
if ($fail_site_dirs) {
    $log_file = LOG_DIR.date('YmdHis').'.txt';
    file_put_contents($log_file, implode(PHP_EOL, $fail_site_dirs));
    echo convert2gbk('没有替换成功的文件已记录在文件 [ '.$log_file.' ] 请检查.'), PHP_EOL, PHP_EOL;
}


/** functions **/

function mkdirp($path, $is_file = false) {
    $path = $is_file ? dirname($path) : $path;
    mkdir($path, 0777, true) or showEror(' 无法创建目录: '.$path);
}

function checkDir($dir) {
    if (!$new_dir = realpath($dir)) {
        showEror('目录 [ '.$dir.' ] 不存在,请检查.');
    }

    return $new_dir;
}

function showEror($msg) {
    die(convert2gbk($msg).PHP_EOL.PHP_EOL);
}

function detectEncoding($str) {
    return mb_detect_encoding($str, array('UTF-8', 'CP936', 'BIG5', 'ASCII'));
}

function convert2gbk($str) {
    return convertEncoding($str, 'CP936');
}

function convert2utf8($str) {
    return convertEncoding($str, 'UTF-8');
}

function convertEncoding($str, $to_encoding) {
    if ($to_encoding !== ($from_encoding = detectEncoding($str))) {
        $str = mb_convert_encoding($str, $to_encoding, $from_encoding);
    }

    return $str;
}

function readTheDir($dir) {
    $files = array();
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($dir)), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $file_info) {
        if ($file_info->isFile()) {
            $files[] = $file_info->getPathname();
        }
    }

    return $files;
}