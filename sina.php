<?php
function workUrl($url) {
	$client = new SinHttpClient();
	$client -> request -> setHeader('User-Agent', 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)');
	$client -> get(trim($url));
	$article = $client -> response -> body;
	preg_match('/<!-- 正文开始 -->([\s\S]*)<!-- 正文结束 -->/', $article, $content);
	$content = str_replace(PHP_EOL . PHP_EOL, PHP_EOL, strip_tags($content[0]));
	preg_match('/<title>(.*?)_(.*)<\/title>/', $article, $title);
	$title = str_replace('&nbsp;', '', strip_tags($title[0]));
	$result['title'] = $title;
	$result['content'] = $content;
	return $result;
}

set_time_limit(0);
require 'sinhttp.php';
if ($argc < 3) {
	echo <<<EOF

==========
新浪博客爬虫+自动生成电子书
使用方法：
php $argv[0] <网址文件> <电子书格式>
参数解释：
<网址文件>：一行一个网址，请使用电脑版访问后复制
<电子书格式>：epub或者mobi
命令示例：
php $argv[0] urls.txt mobi
网址文件示例：
http://blog.sina.com.cn/s/blog_123456wsla.html
http://blog.sina.com.cn/s/blog_789456wsex.html
==========

EOF;
	exit ;
}
$format = $argv[2];
$urls = file_get_contents($argv[1]);
if (!$urls) {
	echo '无法读取网址文件 ' . $argv[1] . PHP_EOL;
	exit ;
}
$urls = explode(PHP_EOL, $urls);
$dir = time();
mkdir($dir);
foreach ($urls as $url) {
	if (empty($url)) {
		continue;
	}
	echo '#现在处理：' . $url . PHP_EOL;
	for ($i = 0; $i < 5; $i++) {
		$result = workUrl($url);
		echo '#标题：' . $result['title'] . PHP_EOL;
		if (empty($result['content'])) {
			if($i==4){
				echo '##ERROR：正则匹配失败，且超出5次重试次数，请打开网页检查问题。' . PHP_EOL . '---------' . PHP_EOL;
				break;
			}
			echo '##ERROR：正则匹配失败！重试次数'.$i . PHP_EOL;
			continue;
		} else {
			echo '##内容有效' . PHP_EOL;
		}
		$f = fopen($dir . '/all.md', 'a');
		fwrite($f, '###' . $result['title'] . PHP_EOL . $result['content'] . '--------' . PHP_EOL);
		fclose($f);
		echo '#写入数据完毕' . PHP_EOL . '---------' . PHP_EOL;
		break;
	}
}
echo '#全部任务处理完毕' . PHP_EOL . '===========' . PHP_EOL . PHP_EOL;
