<?php
namespace Maiyuan\Service\Oicq;

use Maiyuan\Service\Standard;
use Maiyuan\Service\Exception;

class Bootstrap extends Standard
{
    const APP_ID = 501004106,   //固定不变
        // CACHE_TIME = 172800,  //可设置缓存时间,一般2天QQ自动下线
        CLIENT_ID = 53999199; //客户单ID,固定不变

    private $cookie;
    private $cache;
    private $headers;
    private $referer;
    // private $cacheModel;
    private $qqDir; //原来phalcon的cache,现在改为文件存储 /vendor/maiyuan/services/src/oicq/
    private $userDir = null;
    public $sequence = 0;
    public $curl_error = null;

    public function __construct(){
    }

    public function init(){
        // $this->cacheModel = new BackendCache(new FrontendCache(['lifetime'=>99999999]), ['prefix'=>'qq_', "cacheDir" => VAR_PATH . 'cache' . DS]);
        if (!$this->userDir) {
            $this->qqDir = dirname(__FILE__).'/qq_file/';   //定义文件存储位置 默认
        } else {
            $this->qqDir = $this->userDir;
        }
        if (!file_exists($this->qqDir)) {
            mkdir($this->qqDir);
        }
        $this->cookie = $this->getCookie();
        $this->cache = $this->getCache();
        $this->headers['User-Agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:27.0) Gecko/20100101 Firefox/27.0';
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $this->autoLogin();
    }

    // 更改文件存储位置
    public function setDir($dir = null){
        if ($dir) {
            $dir = rtrim(trim($dir), "\\");
            if (substr($dir, -1) != '/') {
                $dir .= '/';
            }
            $this->userDir = $dir;
        }
    }

    public function setCookie($name = null, $value = null){
        if (!is_null($name)){
            $this->cookie[$name] = $value;
        }
        // $this->cacheModel->save('qqbot_cookie', $this->cookie);
        file_put_contents($this->qqDir.'qqbot_cookie', json_encode($this->cookie));
    }

    public function getCookie($name = null){
        // $cookie = $this->cacheModel->get('qqbot_cookie');
        $cookie = file_exists($this->qqDir.'qqbot_cookie')?json_decode(file_get_contents($this->qqDir.'qqbot_cookie'), true):null;
        if (is_null($name)){
            return $cookie;
        }
        if (!empty($this->cookie[$name])){
            return $this->cookie[$name];
        }
        if (empty($cookie[$name])){
            return false;
        }
        $this->cookie[$name] = $cookie[$name];
        return $this->cookie[$name];
    }

    /*
     * 生成缓存
     */
    public function setCache($name, $value){
        $cache = $this->getCache();
        $cache[$name] = $value;
        $this->cache = $cache;
        // return $this->cacheModel->save('qqbot_cache', $cache);
        return file_put_contents($this->qqDir.'qqbot_cache', json_encode($cache));
    }

    /*
     * 获取缓存
     */
    public function getCache($name = null){
        // $cache = $this->cacheModel->get('qqbot_cache');
        $cache = file_exists($this->qqDir.'qqbot_cache')?json_decode(file_get_contents($this->qqDir.'qqbot_cache'), true):null;
        if (!$cache){
            return false;
        }
        return is_null($name) ? $cache : (isset($cache[$name]) ? $cache[$name] : false);
    }

    /*
     * 删除缓存
     */
    public function removeCache($name = null){
        // return $this->cacheModel->delete($name);
        if (file_exists($this->qqDir.$name)) {
            return unlink($this->qqDir.$name);
        }
        return false;
    }

    /*
     * 自动登录
     */
    public function autoLogin(){
        if (isset($this->cache['vfwebqq_time']) && (time() - $this->cache['vfwebqq_time']) > 600){
            $this->getVfwebqq();
        }
    }

    /*
     * 验证登录状态
     * return 登录时间
     */
    public function testLogin(){
        $cache = $this->getCache();
        if (!$cache || !isset($cache['vfwebqq']) || !isset($cache['psessionid'])){
                return false;
            }
            $url = 'http://d1.web2.qq.com/channel/get_online_buddies2?vfwebqq='.$cache['vfwebqq'].'&clientid='.self::CLIENT_ID.'&psessionid='.$cache['psessionid'].'&t='.time();
        $result = $this->curl($url, array(
                'referer'=>'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2'
            ));
            $body = json_decode($result['body'], true);
        if ($body['retcode']!=0){
                return false;
            }
        return $cache['login_time'];
    }

    /*
     * 清理CACHE 重新登录
     */
    public function resetLogin(){
        $this->removeCache('qqbot_cookie');
        $this->removeCache('qqbot_cache');
        return true;
    }

    /*
     * Step1 - 获取二维码
     * return 图片Base64编码
     */
    public function getQrcode(){
        $this->removeCache('qqbot_cookie');
        $this->removeCache('qqbot_cache');
        $qrcode = $this->curl('https://ssl.ptlogin2.qq.com/ptqrshow?appid=' . self::APP_ID . '&e=0&l=M&s=5&d=72&v=4&t='.(explode(' ', microtime())[0]));
        preg_match('/qrsig=(.*?);/', $qrcode['header'], $header);
        $this->setCookie('qrsig', $header[1]);
        $qrcodeBase64 = 'data:image/png;base64,'.base64_encode($qrcode['body']);
        return $qrcodeBase64;
    }

    /*
     * Step2 - 查询验证码状态
     */
    public function checkQrcode(){
        $ptqrtoken = $this->hash33($this->cookie['qrsig'], 0);
        $checkUrl = 'https://ssl.ptlogin2.qq.com/ptqrlogin?ptqrtoken='.$ptqrtoken.'&webqq_type=10&remember_uin=1&login2qq=1&aid=' . self::APP_ID . '&u1=http%3A%2F%2Fw.qq.com%2Fproxy.html%3Flogin2qq%3D1%26webqq_type%3D10&ptredirect=0&ptlang=2052&daid=164&from_ui=1&pttype=1&dumy=&fp=loginerroralert&action=0-0-2086&mibao_css=m_webqq&t=1&g=1&js_type=0&js_ver=10187&login_sig=&pt_randsalt=2';
        $result = $this->curl($checkUrl);
        preg_match_all("/'(.*?)'/", $result['body'], $ptui);
        if (!isset($ptui[1])){
            return false;
        }
        $checkSigUrl = isset($ptui[1][2]) ? $ptui[1][2] : '';
        if ($checkSigUrl!=''){
            preg_match_all('/Set-Cookie: (.*?)=(.*?);/', $result['header'], $cookies);
            if (isset($cookies[1])){
                foreach($cookies[1] AS $k=>$f){
                    if ($cookies[2][$k]=='') continue;
                    $this->cookie[$f] = $cookies[2][$k];
                }
                $this->setCookie();
            }
            $result = $this->curl($checkSigUrl);
            preg_match_all('/Set-Cookie: (.*?)=(.*?);/', $result['header'], $cookies);
            if (isset($cookies[1])){
                foreach($cookies[1] AS $k=>$f){
                    if ($cookies[2][$k]=='') continue;
                    $this->cookie[$f] = $cookies[2][$k];
                }
                $this->setCookie();
            }
            $this->setCache('ptwebqq', $this->cookie['ptwebqq']);
            if (!$this->getVfwebqq()){
                return 'Vfwebqq 获取失败';
            }
            if (!$this->getUinAndPsessionid()){
                return '获取UID和Psessionid失败';
            }
        }
        return $ptui[1][4];
    }

    /*
     * Step3 - 扫码成功后获得Vfwebqq
     */
    public function getVfwebqq(){
        $ptwebqq = $this->getCookie('ptwebqq');
        $url = 'http://s.web2.qq.com/api/getvfwebqq?ptwebqq='.$ptwebqq.'&clientid='.self::CLIENT_ID.'&psessionid=&t='.time();
        $this->referer = 'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1';
        $result = $this->curl($url);
        preg_match('/"vfwebqq":"(.*?)"/', $result['body'], $vfwebqq);
        if (!isset($vfwebqq[1])){
            return false;
        }
        $this->setCache('vfwebqq', $vfwebqq[1]);
        $this->setCache('vfwebqq_time', time());
        return $vfwebqq[1];
    }

    /*
     * Step4 - 获取UIN 和 Psessionid
     */
    public function getUinAndPsessionid(){
        $post['r'] = json_encode(array(
            'ptwebqq'=>$this->getCache('ptwebqq'),
            'clientid'=>self::CLIENT_ID,
            'psessionid'=>'',
            'status'=>'online'
        ));
        $result = $this->curl('http://d1.web2.qq.com/channel/login2', array(
            'method' => 'POST',
            'body' => $post,
            'referer' => 'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2'
        ));
        $body = json_decode($result['body'], true);
        if (!is_array($body) || $body['retcode'] != 0){
            return false;
        }
        $this->setCache('cip', $body['result']['cip']);
        $this->setCache('psessionid', $body['result']['psessionid']);
        $this->setCache('uin', $body['result']['uin']);
        $this->setCache('login_time', date('Y-m-d H:i:s'));
        $this->setCache('hash', $this->getHash($this->cache['uin'], $this->cache['ptwebqq']));
        $this->setCache('bkn', $this->hash33($this->cookie['skey']));
        return array(
            'uin'=>$body['result']['uin'],
            'psessionid'=>$body['result']['psessionid'],
            'hash'=>$this->cache['hash'],
            'bkn'=>$this->cache['bkn']
        );
    }

    /*
     * 获取好友列表
     * return 好友列表
     */
    public function getBuddies(){
        $post['r'] = json_encode(array(
            'vfwebqq'=>$this->cache['vfwebqq'],
            'hash'=>$this->getHash()
        ));
        $result = $this->curl('http://s.web2.qq.com/api/get_user_friends2', array(
            'method'=>'POST',
            'body'=>$post,
            'referer'=>'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1'
        ));
        $body = json_decode($result['body'], true);
        if($body['retcode']!=0){
            return false;
        }
        return $body['result']['info'];
    }

    public function getGroup($nick = '')
    {
        $groups = $this->getGroups();
        foreach($groups AS $group){
            if ($group['name']!=$nick){
                continue;
            }
            return $group;
        }
        return false;
    }

    /*
     * 获取群列表
     * return 群列表
     */
    public function getGroups(){
        $post['r'] = json_encode(array(
            'vfwebqq'=>$this->cache['vfwebqq'],
            'hash'=>$this->getHash()
        ));
        $result = $this->curl('http://s.web2.qq.com/api/get_group_name_list_mask2', array(
            'method'=>'POST',
            'body'=>$post,
            'referer'=>'http://s.web2.qq.com/proxy.html?v=20130916001&callback=1&id=1'
        ));
        $body = json_decode($result['body'], true);
        if($body['retcode']!=0){
            return false;
        }
        return $body['result']['gnamelist'];
    }

    /*
     * 通过UIN获取好友信息
     * return QQ号
     */
    public function getBuddyQQ($uin){
        $result = $this->curl('http://s.web2.qq.com/api/get_friend_uin2?tuin='.$uin.'&type=1&vfwebqq='.$this->cache['vfwebqq'].'&t=0.1', array('referer'=>'http://d1.web2.qq.com/proxy.html?v=20151105001&callback=1&id=2'));
        $body = json_decode($result['body'], true);
        if($body['retcode']!=0){
            return false;
        }
        return $body['result']['account'];
    }

    /*
     * 通过UIN得到好友QQ号
     */
    public function setBuddiesCache($list = null){
        if (is_null($list)){
            $list = $this->getBuddies();
        }
        $buddies = array();
        foreach($list AS $d){
            $buddies[] = [
                'uin'=>$d['uin'],
                'nick'=>$d['nick'],
                'qq'=>$this->getBuddyQQ($d['uin'])
            ];
        }
        return $buddies;
    }

    public function getUinByQQ($qq){
        // $buddies = $this->cacheModel->get('qqbot_buddies');
        $buddies = file_exists($this->qqDir.'qqbot_buddies')?json_decode(file_get_contents($this->qqDir.'qqbot_buddies'), true):null;
        foreach($buddies AS $d){
            if ($d['qq']==$qq){
                return $d['uin'];
            }
        }
        return false;
    }

    /*
     * 发送QQ消息
     */
    public function send($qq, $msg, $to = 'user'){
        $msg = $this->replaceFace($msg);

        $content = array(
            array('font', array('name'=>'宋体', 'size'=>10, 'style'=>array(0,0,0), 'color'=>'000000'))
        );
        if (is_array($msg)){
            $content = array_merge($msg, $content);
        }else{
            array_unshift($content, $msg);
        }

        if ($to == 'user'){
            $sendData = array(
                'to'=>$qq,
                'content'=>json_encode($content, JSON_UNESCAPED_UNICODE),
                'face'=>534,
                'clientid'=>self::CLIENT_ID,
                'psessionid'=>$this->cache['psessionid'],
                'msg_id'=>$this->geMsgId()
            );
            $url = 'https://d1.web2.qq.com/channel/send_buddy_msg2';
        }elseif($to == 'group'){
            $sendData = array(
                'group_uin'=>$qq,
                'content'=>json_encode($content, JSON_UNESCAPED_UNICODE),
                'face'=>534,
                'clientid'=>self::CLIENT_ID,
                'psessionid'=>$this->cache['psessionid'],
                'msg_id'=>$this->geMsgId()
            );
            $url = 'https://d1.web2.qq.com/channel/send_qun_msg2';
        }
        $post['r'] = json_encode($sendData, JSON_UNESCAPED_UNICODE);
        $result = $this->curl($url, array(
            'method'=>'POST',
            'body'=>$post,
            'referer'=>'https://d1.web2.qq.com/cfproxy.html?v=20151105001&callback=1'
        ));
        $body = json_decode($result['body'], true);
        if ($body['retcode']==0){
            return true;
        }
        return false;
    }

    public function toUser($qq, $msg){
        if (is_array($msg)){
            foreach($msg AS $content){
                echo $content;
                $this->send($qq*1, $content, 'user');
                sleep(1);
            }
            return true;
        }else{
            return $this->send($qq*1, $msg, 'user');
        }
    }

    public function toGroup($qq, $msg){
        if (is_array($msg)){
            foreach($msg AS $content){
                $this->send($qq*1, $content, 'group');
                sleep(1);
            }
            return true;
        }else{
            return $this->send($qq*1, $msg, 'group');
        }
    }

    public function mbSplit($string, $len=1) {
        $start = 0;
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string,$start,$len,"utf8");
            $string = mb_substr($string, $len, $strlen,"utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    public function geMsgId(){
        $t = round(microtime(1) * 1000);
        $t = ($t - $t % 1000) / 1000;
        $t = $t % 10000 * 10000;
        $this->sequence++;
        return $t + $this->sequence;
    }

    public function poll(){
        $post['r'] = json_encode([
            'ptwebqq'=>$this->cookie['ptwebqq'],
            'clientid'=>self::CLIENT_ID,
            'psessionid'=>$this->cache['psessionid'],
            'key'=>''
        ], JSON_UNESCAPED_UNICODE);
        $result = $this->curl('https://d1.web2.qq.com/channel/poll2', array(
            'method'=>'POST',
            'body'=>$post,
            'referer'=>'https://d1.web2.qq.com/cfproxy.html?v=20151105001&callback=1',
            'timeout'=>60
        ));
        $body = json_decode($result['body'], true);
        if ($body['retcode'] == 0){
            return $body['result'];
        }
        return $body['retcode'];
    }

    /*
     * Hash
     */
    public function getHash($uin = null, $ptwebqq = null){
        $uin = is_null($uin) ? $this->cache['uin'] : $uin;
        $ptwebqq = is_null($ptwebqq) ? $this->cache['ptwebqq'] : $ptwebqq;
        $K = str_split($ptwebqq, 1);
        $N = array(0,0,0,0);
        foreach($K AS $i=>$n){
            $N[$i%4] ^= ord($n);
        }
        $U = 'ECOK';
        $V[0] = (($uin >> 24) & 255) ^ ord($U[0]);
        $V[1] = (($uin >> 16) & 255) ^ ord($U[1]);
        $V[2] = (($uin >>  8) & 255) ^ ord($U[2]);
        $V[3] = (($uin >>  0) & 255) ^ ord($U[3]);
        for($i=0;$i<=7;$i++){
            if ($i%2 == 0){
                $U1[$i] = $N[$i >> 1];
            }else{
                $U1[$i] = $V[$i >> 1];
            }
        }
        $N1 = '0123456789ABCDEF';
        $V1 = '';
        foreach($U1 AS $n){
            $V1 .= $N1[(($n >> 4) & 15)];
            $V1 .= $N1[(($n >> 0) & 15)];
        }
        return $V1;
    }

    public function hash33($skey, $initStr = 5341){
        $hashStr = $initStr;
        $skey = str_split($skey);
        foreach($skey AS $i){
            $hashStr += (($hashStr << 5) & 0x7fffffff) + ord($i) & 0x7fffffff;
            $hashStr &= 0x7fffffff;
        }
        $hashStr = intval($hashStr & 0x7fffffff);
        return $hashStr;
    }

    private function charCodeAt($str, $index){
        $char = mb_substr($str, $index, 1, 'UTF-8');

        if (mb_check_encoding($char, 'UTF-8')){
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        }
        else
        {
            return null;
        }
    }

    function str_split_unicode($str, $l = 0) {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    /*
     * HTTP请求
     */
    public function curl($url, $args = array()){
        $defaults = array(
            'method' => 'GET',
            'timeout' => 30,
            'redirection' => 5,
            'headers' => $this->headers,
            'body' => null,
            'cookies' => $this->getCookie(),
            'referer' => $this->referer,
        );
        $param = array_merge($defaults, $args);
        if (isset($param['headers']['User-Agent'])) {
            $param['user-agent'] = $param['headers']['User-Agent'];
            unset($param['headers']['User-Agent']);
        } elseif (isset($param['headers']['user-agent'])) {
            $param['user-agent'] = $param['headers']['user-agent'];
            unset($param['headers']['user-agent']);
        }

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $param['timeout']);
        curl_setopt($handle, CURLOPT_TIMEOUT, $param['timeout']);

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPPROXYTUNNEL, true);
        curl_setopt($handle, CURLOPT_USERAGENT, $param['user-agent']);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_REFERER, $param['referer']);
        curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        switch ($param['method']) {
            case 'HEAD':
                curl_setopt($handle, CURLOPT_NOBODY, true);
                break;
            case 'POST':
                //curl_setopt($handle, CURLOPT_HEADER, false);
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($param['body']));
                break;
            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, $param['body']);
                break;
            default:
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $param['method']);
                if (!is_null($param['body']))
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $param['body']);
                break;
        }
        $cookies = [];
        if (!empty($param['cookies'])){
            foreach($param['cookies'] AS $name=>$value){
                $cookies[] = $name.'='.$value;
            }
        }
        $param['headers']['Cookie'] = implode(';', $cookies);
        //echo $param['headers']['Cookie'];
        if (!empty($param['headers'])) {
            $headers = array();
            foreach ($param['headers'] as $name => $value) {
                $headers[] = "{$name}: $value";
            }
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($handle);
        $info = curl_getinfo($handle);
        $this->curl_error = curl_error($handle);
        curl_close($handle);
        if ($info['http_code'] == 200){
            $header = substr($body, 0, $info['header_size']);
            $body = substr($body, $info['header_size']);

            $this->referer = $url;

            return array(
                'header' => $header,
                'body' => $body
            );
        }
        return false;
    }

    public function replaceFace($content){
        $faceMap = $this->facemap();
        $regx = array();
        $faceKey = array_flip($faceMap);
        foreach($faceMap AS $k=>$v){
            $regx[] = '\/'.$v;
        }
        $regx = implode('|', $regx);
        preg_match_all("/{$regx}/", $content, $faceData);
        if (!empty($faceData[0])){
            foreach($faceData[0] AS $face)
            {
                $faceTxt = trim($face, '/');
                $faceId = $faceKey[$faceTxt];
                $faceJson = json_encode(array('face', $faceId));
                $content = str_replace($face, '@#@'.$faceJson.'@#@', $content);
            }
            $content = explode('@#@', $content);
            $result = array();
            foreach($content AS $val){
                $result[] = is_null(json_decode($val, true)) ? $val : json_decode($val, true);
            }
            if (end($result) == ''){
                array_pop($result);
            }
        }else{
            $result = $content;
        }
        return $result;
    }

    public function facemap(){
        return array(0=>'惊讶',1=>'撇嘴',2=>'色',3=>'发呆',4=>'得意',5=>'流泪',6=>'害羞',7=>'闭嘴',8=>'睡',9=>'大哭',10=>'尴尬',11=>'发怒',12=>'调皮',13=>'呲牙',14=>'微笑',21=>'飞吻',23=>'跳跳',25=>'发抖',26=>'怄火',27=>'爱情',29=>'足球',32=>'西瓜',33=>'玫瑰',34=>'凋谢',36=>'爱心',37=>'心碎',38=>'蛋糕',39=>'礼物',42=>'太阳',45=>'月亮',46=>'强',47=>'弱',50=>'难过',51=>'酷',53=>'抓狂',54=>'吐',55=>'惊恐',56=>'流汗',57=>'憨笑',58=>'大兵',59=>'猪头',62=>'拥抱',63=>'咖啡',64=>'饭',71=>'握手',72=>'便便',73=>'偷笑',74=>'可爱',75=>'白眼',76=>'傲慢',77=>'饥饿',78=>'困',79=>'奋斗',80=>'咒骂',81=>'疑问',82=>'嘘',83=>'晕',84=>'折磨',85=>'衰',86=>'骷髅',87=>'敲打',88=>'再见',90=>'雾',91=>'闪电',92=>'炸弹',93=>'刀',94=>'女人',95=>'胜利',96=>'冷汗',97=>'擦汗',98=>'抠鼻',99=>'鼓掌',100=>'糗大了',101=>'坏笑',102=>'左哼哼',103=>'右哼哼',104=>'哈欠',105=>'鄙视',106=>'委屈',107=>'快哭了',108=>'阴险',109=>'亲亲',110=>'吓',111=>'可怜',112=>'菜刀',113=>'啤酒',114=>'篮球',115=>'乒乓',116=>'示爱',117=>'瓢虫',118=>'抱拳',119=>'勾引',120=>'拳头',121=>'差劲',122=>'爱你',123=>'NO',124=>'OK',125=>'转圈',126=>'磕头',127=>'回头',128=>'跳绳',129=>'挥手',130=>'激动',131=>'街舞',132=>'献吻',133=>'左太极',134=>'右太极',135=>'招财猫',136=>'双喜',137=>'鞭炮',138=>'灯笼',139=>'发财',140=>'K歌',141=>'购物',142=>'邮件',143=>'帅',144=>'喝彩',145=>'祈祷',146=>'劲爆',147=>'棒棒糖',148=>'喝奶',149=>'面条',150=>'香蕉',151=>'飞机',152=>'开车',153=>'高铁左车头',154=>'车厢',155=>'高铁右车头',156=>'多云',157=>'下雨',158=>'钞票',159=>'熊猫',160=>'灯泡',161=>'风车',162=>'闹钟',168=>'药',169=>'手枪',170=>'青蛙',171=>'粥',172=>'眨眼睛',173=>'泪奔',174=>'无奈',175=>'卖萌',176=>'小纠结',177=>'喷血',178=>'斜眼笑',179=>'doge',180=>'惊喜',181=>'骚扰',182=>'笑哭',183=>'我最美',184=>'河蟹',185=>'羊驼',187=>'幽灵',188=>'蛋',189=>'马赛克',190=>'菊花',191=>'肥皂',192=>'红包',193=>'大笑',194=>'不开心',195=>'啊',196=>'恐慌',197=>'冷漠',198=>'呃',199=>'好棒',200=>'拜托',201=>'点赞',202=>'无聊',203=>'托脸',204=>'吃',205=>'送花',206=>'害怕',207=>'花痴',208=>'小样儿',209=>'脸红',210=>'飙泪',211=>'我不看',212=>'托腮');
    }

}
