<?php

//统计昨日以前的相关数据
//每日1：00执行一次
require_once __DIR__ . '/../init.inc.php';
try {
    $db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
    $db->exec("SET time_zone = '+8:00'");
    $db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    echo date('Y-m-d') . PHP_EOL;

    $model = new Model();

    $xunfei = new Xunfei();
    $treeList = $xunfei->treeList();
    if ($treeList->retcode != '0000') {
        throw new \Exception("未获取到栏目信息");
    }

    foreach ($treeList->data->cols as $treeInfo) {
        $sql = 'REPLACE INTO t_xunfei_video_tree SET id = ?, name = ?, `desc` = ?, img = ?, targetid = ?';
        $db->exec($sql, $treeInfo->id, $treeInfo->name, $treeInfo->desc, $treeInfo->simg, $treeInfo->targetid);
        $px = 0;
        while (true) {
            $subList = $xunfei->subList($treeInfo->targetid, $px);
            if ($subList->retcode != '0000') {
                throw new \Exception("未获取到子项目信息");
            }
            foreach ($subList->data as $subInfo) {
                $sql = 'INSERT INTO t_xunfei_video_sub (id, name, url, song, cover_url, targetid) SELECT ?, ?, ?, ?, ?, ? FROM DUAL WHERE NOT EXISTS (SELECT sub_id FROM t_xunfei_video_sub WHERE id = ?)';
                $db->exec($sql, $subInfo->id, $subInfo->nm, $subInfo->url, isset($subInfo->song) ? json_encode($subInfo->song) : '', $subInfo->pvurl, $treeInfo->targetid, $subInfo->id);
//                var_dump($subInfo);exit;
            }
            if (!$subList->more) {
                break;
            }
            $px = $subList->px;
        }
    }
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
echo 'done';

//id	String	视频编号
//nm	String	视频名称
//url	String	预览视频url
//pvurl	String	预览图url
//charge	String	是否收费
//0-不收费
//1-收费
//默认0
//price	String	价格，单位：分（人民币）
//charge为1时有效，无值则默认为0分
//song	BackgroundMusic
//视频的配乐信息
//seton	String	是否支持设置视频彩铃（运营商设置）
//1-支持
//0-不支持
//默认是0
//size	String	视频大小，单位bytes
//width	String	视频宽度，单位像素
//height	String	视频高度，单位像素
//duration	String	视频时长，单位秒
//videos	Video[]	更多高清格式的视频信息
//推荐设置场景使用



//id	String	栏目编号
//name	String	栏目名称
//desc	String	描述
//type	String	栏目类型
//20010001	栏目（存放20020002、20020003、20030001、20040001、及同类型资源20010001）
//20020002	无图合辑（targetid为对应铃音包（20020001）的编号）
//20020003	图片合辑（targetid为对应铃音包（20020001）的编号）
//20030001	轮播图，banner列表（存放20030002、20030003）
//20030002	广告之url（linkurl为该广告指向的链接地址）
//20030003	广告之合辑（targetid为对应合辑（20020002、20020003）的编号）
//20040001	分类（存放20020002、20020003）
//
//21020002	视频无图合辑（targetid为对应视频包（21020001）的编号）
//21020003	视频图片合辑（targetid为对应视频包（21020001）的编号）
//21030003	广告之视频合辑（targetid为对应视频合辑（21020002、21020003）的编号）
//21040001	视频分类（存放视频合辑（21020002、21020003））
//
//详细说明请见附件《栏目类型使用补充说明》
//simg	String	封面图片
//detimg	String	详情图片
//linkurl	String	链接地址（广告链接URL时使用）
//targetid	String	栏目链接目标ID，（广告合辑时使用）
//cols	ColRes[]	子栏目列表（当有子栏目时使用）
//wks	ResItemSimple[]	铃音列表
//btncolor	String	按钮颜色 #FFFFFFF
//bgcolor	String	背景颜色 #FFFFFFF
//fontcolor	String	字体颜色 #FFFFFFF
//listentimes	String	栏目试听次数

