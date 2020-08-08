<?php
 function getClientIp()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    if (getenv('HTTP_X_REAL_IP')) {
        $ip = getenv('HTTP_X_REAL_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
        $ips = explode(',', $ip);
        $ip = $ips[0];
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '0.0.0.0';
    }

    return $ip;
}
$ip = getClientIp();
 print_r($ip);exit;
print_r($_SERVER);exit;

//1)
$weimiao = 'weimiao 666, 2020';
$pattern = '/(\w+) (\d+), (\d+)/';
$replacement = '${1}1,$3';
echo "\n";
echo preg_replace($pattern,$replacement,$weimiao);
//weimiao1,2020

echo "\n";


exit;
//2)
//git回滚
//在工作区的代码
//git checkout -- filename

//代码git add到缓存区 暂存区，并未commit提交
//git reset HEAD filename

//git commit到本地分支，但没有git push到远程
//git reset --hard HEAD^


//git push把修改提交到远程仓库
//如果想恢复到之前某个提交的版本，且那个版本之后提交的版本我们都不要了
//不保留后续版本 git reset --hard commitId  git push -f
//保留后续版本 git revert --n commitId add-commit-push





//3)
//grep 'getVideoInfo' test.log
//  | awk -F '\|' '{print $1}'   |cut -d '+' -f 1 | sort -b | uniq -c


//4)
//自动编号 学号 姓名 课程编号 课程名称 分数
//删除除了自动编号不同，其他都相同的学生冗余信息
/*
 delete from test_unique
where id in (select tmp_t1.id from
(
select id from test_unique where (`stu_no`,`name`) in
(SELECT		`stu_no`,`name`	FROM test_unique GROUP BY `stu_no`,`name` HAVING count(1) > 1 )
) as tmp_t1)
and id not in (select tmp_t2.id from
(
select max(id) as id from test_unique where (`stu_no`,`name`) in
(SELECT		`stu_no`,`name`	FROM test_unique GROUP BY `stu_no`,`name` HAVING count(1) > 1)
GROUP BY `stu_no`,`name`
) as tmp_t2)
;
 */
//5)
//php-fpm的进程管理模式及使用场景

// include_once "wxBizDataCrypt.php";


$appid = 'wx4f4bc4dec97d474b';
$sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

$encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==";

$iv = 'r7BXXKkLb8qrSNn05n0qiA==';

$pc = new WXBizDataCrypt($appid, $sessionKey);
$errCode = $pc->decryptData($encryptedData, $iv, $data );

if ($errCode == 0) {
    print($data . "\n");
} else {
    print($errCode . "\n");
}

/*

# 根据某个字段查询该字段存在重复的情况
# Select stu_no From test_unique Group By stu_no Having Count(stu_no)>1;

# SELECT count(*),count(stu_no) from test_unique;
#Select `name` From test_unique Group By `name` Having Count(*)>1;

# 根据某个字段查询该字段存在重复的情况，并展示刚该行数据
 Select * From test_unique Where `name` In (Select `name` From test_unique Group By `name` Having Count(*)>1);
 Select max(id) From test_unique Where `name` In (Select `name` From test_unique Group By `name` Having Count(*)>1);
#Select max(id) From test_unique Group By `name` Having Count(*)>1;

Select `name`    From test_unique Group By `name` Having Count(*)> 1 ;


SELECT tmp_test_unique1.`name` from  (Select *        From test_unique Group By `name` Having Count(*)>1) as tmp_test_unique1;


# 删除存在重复的数据 一条不留
# DELETE From test_unique
# Where
# `name` In  (SELECT tmp_test_unique.`name` from  (Select `name` From test_unique Group By `name` Having Count(*)>1) as tmp_test_unique);

# 删除存在重复的数据 保留最新的那条（即id最大）
 select *  From test_unique
 Where `name` In
 (SELECT tmp_test_unique1.`name` from  (Select *        From test_unique Group By `name` Having Count(*)>1) as tmp_test_unique1 )
 and   id not in
 (SELECT tmp_test_unique2.*      from  (Select max(id)  From test_unique Group By `name` Having Count(*)>1) as tmp_test_unique2 );


如何获取用户的真实ip。
复杂sql编写的考察。
索引存储在磁盘的结构。
b树，红黑二叉树，b-，b+。
如何设计支持退款和各种优惠活动的订单相关数据库表。
视频课程防盗版的经验。
redis分布式事务。
消息队列在业务中的使用考察。







 */