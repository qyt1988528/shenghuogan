<?php
namespace Maiyuan\Tool\Crypt;

/**
 * 可逆的加密方法
 */
class Auth
{

    /**
     * 字符串加密函数
     *
     * @param	string	$txt		字符串
     * @param	string	$key		密钥：数字、字母、下划线
     * @param	string	$expiry		过期时间
     * @return	string
     */
    public function encode($string, $key = '', $expiry = 0)
    {
        return $this->confuse($string, 'ENCODE', $key, $expiry);
    }

    /**
     * 字符串解密函数
     *
     * @param	string	$txt		字符串
     * @param	string	$key		密钥：数字、字母、下划线
     * @param	string	$expiry		过期时间
     * @return	string
     */
    public function decode($string, $key = '', $expiry = 0)
    {
        return $this->confuse($string, 'DECODE', $key, $expiry);
    }

    /**
     * 字符串加密、解密函数
     *
     *
     * @param	string	$txt		字符串
     * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
     * @param	string	$key		密钥：数字、字母、下划线
     * @param	string	$expiry		过期时间
     * @return	string
     */
    public function confuse($string, $operation = 'ENCODE', $key = 'MaiyuanToolsCryptAuth', $expiry = 0)
    {
        $key_length = 4;
        $fixedkey = md5($key);
        $egiskeys = md5(substr($fixedkey, 16, 16));
        $runtokey = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
        $keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
        $string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));
        $i = 0; $result = '';
        $string_length = strlen($string);
        for ($i = 0; $i < $string_length; $i++){
            $result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
        }
        if($operation == 'ENCODE') {
            return $runtokey . str_replace('=', '', base64_encode($result));
        } else {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        }
    }
	
}