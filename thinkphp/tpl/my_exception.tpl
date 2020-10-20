<?php
header("Content-type:text/html;charset=utf-8");

if(!\think\App::$debug) {
    echo json_encode(['code'=>-1,'msg'=>$message]);return;
}

if(!function_exists('parse_file')){
    function parse_file($file, $line)
    {
        return basename($file)." line {$line}";
    }
}

if(!function_exists('parse_class')){
    function parse_class($name)
    {
        $names = explode('\\', $name);
        return end($names);
    }
}

$fileLine = sprintf('%s in %s', parse_class($name), parse_file($file, $line));
$main = $message;
$arr = [
    'code'=>-1,
    'msg'=>'服务器内部错误',
    'data'=>[
        'tip'=>$fileLine,
        'main'=>$main
    ]
];

echo json_encode($arr);
?>