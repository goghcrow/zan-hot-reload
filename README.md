## 一个高效的zan开发环境热加载实现

test开发环境，监控src目录，重启worker进程实现热加载；

推荐配置worker_num = 2, 加快重启速度；

原本在swoole定时器里用递归遍历目录，fstat比较修改时间与文件体积，发现cpu占用比较高；

谷歌了下，inotify是linux平台，osx平台有fsevents，功能类似；简单拼了个osx平台文件监控的扩展，不支持php7；

有个麻烦的事情是，osx的fsevents私有api需要root权限执行，

可能需要导出phpstrom配置文件，以root权限启动

sudo /Applications/PhpStorm.app/Contents/MacOS/phpstorm

再导入配置文件，就在使用fsevents监控文件了～

**最终效果**：

可以实时修改src目录代码，实时生效


**附**：

1. 扩展安装：

```shell
sudo -i
cd ./extension
PHP_BIN=/Users/chuxiaofeng/yz_env/php/bin
PHP_CONF=/Users/chuxiaofeng/yz_env/conf/
$PHP_BIN/phpize
./configure --with-php-config=$PHP_BIN/php-config
make -j4
make install

echo "extension=fsev.so" >> $PHP_CONF/php.ini
$PHP_BIN/php --ri fsev
```

2. 一个实时输出系统变动文件的例子；

~~~php
<?php
$fp = fsev_open();
while(true) {
	$data = fread($fp, 8192);
	$ret = fsev_decode($data);
	print_r($ret);
}
fclose($fp);
~~~

