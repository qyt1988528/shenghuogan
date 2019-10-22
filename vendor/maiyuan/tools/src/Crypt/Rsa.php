<?php
namespace Maiyuan\Tool\Crypt;

class Rsa
{
    protected $_public = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1oCqoAmQSAKNyrFfOXNC
CVm1MoyWy64HdOJOduKXHN8joYrMbjA0O8eMMcmXP6yGSDwen5OtFGvUuTrvkWXv
io83+c7m1VlMZWa3Y9vIf3tX4BcXFiyochmeJCXPS47U92g6oI3tCI+Hg2n+NuVC
DTcb9hEOH6xSmDZuASQ2v9ACWjqG9IDmC+oNr5OhtZNos+4iGwmU8d64gHwzjUI3
srcJ/FIEB3kSCg8NNeDVk/QdSriat2vOwSgM0w8uU3IVcK18ISe2ZxrrEuu55ZrT
qzjwhwFWZ+mRcEMYzeYU1I2V44U+xgGsL9AiPrL4mg45B5K7BC3WK6Pun1AO8BwS
jwIDAQAB
-----END PUBLIC KEY-----';

    protected $_private = '-----BEGIN PRIVATE KEY-----
MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDWgKqgCZBIAo3K
sV85c0IJWbUyjJbLrgd04k524pcc3yOhisxuMDQ7x4wxyZc/rIZIPB6fk60Ua9S5
Ou+RZe+Kjzf5zubVWUxlZrdj28h/e1fgFxcWLKhyGZ4kJc9LjtT3aDqgje0Ij4eD
af425UINNxv2EQ4frFKYNm4BJDa/0AJaOob0gOYL6g2vk6G1k2iz7iIbCZTx3riA
fDONQjeytwn8UgQHeRIKDw014NWT9B1KuJq3a87BKAzTDy5TchVwrXwhJ7ZnGusS
67nlmtOrOPCHAVZn6ZFwQxjN5hTUjZXjhT7GAawv0CI+sviaDjkHkrsELdYro+6f
UA7wHBKPAgMBAAECggEBANZmsSVZqc9wTg3FkKq5283Azu6Bu9BGWMmp0kfeYLDJ
ELJNK90PbYY7BJRXLUjFx0q4XNugHiYFShEDKaYpP289i/OzrOKfaU8JhlDXw+Y4
gTNJoRfW919k37691B9v2sqYcdE9Syl9XTQrY5+3M3hGFzqy/W0HL19ZOEcHzXmW
OFxhujF723WRwFojqithqwXWm5rVL7+0bY9awEHTmnTw5BQTfDIBtmdiCS0OPJSU
1pKYOvg7mM1IoqCXDt+SeWbyuMTrWxQrdrE6CjNVd/UEm0hOJ7Ie8vzGbZUFKb6O
fgVcmESTXxocC8dZz2D0d/TJnqMwTdYdwYKajh3JzvECgYEA/llf+iGPuC+k+Y9J
10dtSKiibaR2A1BAwjJp4bhVwy6lOnkK0w5zMX91RUqs1g+D3CtnzlXpj5bZM/fJ
zWEv0qaTTDLHz8keSfdbQv/EuBemZztIEIK8DpYLxJqtqhravbua4RKumqrh5gdb
pqwNgCPSHUrkhpm1/CI/6rd6gc0CgYEA1+UVNR90GEJP03bR6hLPlX+NssDvfdJn
gKiAmyTv+KUCUq2umNGzXHYG7iaXi4wHubgD483KXpUb7PpnV3aEaZE5Zd8N+wTa
JDQ8n6U83lPogxIVFKchmFkCxa8SL3sJQ7SqPo6BHTzSoxgADvlkNaMddy32Csly
3HAejzDSucsCgYBXBw2n8EPUqbixCy4g0ve5nLm2+kbG63a8+7Lu4Lu3hQZT84aY
oKEZlprxkpOAyt47Pz7NguffkaXP+kC6XT6XvRc5Q0bK+e27MT+wtQMCWlU9jTMj
MxhVhVGRe7tgMMAXm1FrIZFMqpuQsYPSi5wy9A64px96Tq3OD4n/LbhlIQKBgQCM
HTO/T1OELv0pq3KerGimiKrIuSh1Cyl7OWCFz9oiD81LJUcsDOSP/FWRF+DAgze8
U23K2ZMeAT2ndfe9rsBO1x5eO/4RzFtapcA2iAHR9Ljw37potfM6sYH4FCAESqB/
nW/ju46WEBRQHtJi5X4gxWTpJR4KcCUoZWef3LrWMwKBgQDQwg6NFGv3bFY/nhxD
IaCWBLXIPvSxme9e6It+WKu8qPby30YSkAg5HSnPf+e6o1u6znZQteUqZObXIC7c
iV5iiL78abcmdyAvcZZWbm1SoRc577s7guqXz+m68mitbJlXoP33ddhO8FQ03U+K
X2krm4QUb9YE407sN2zF99PiGw==
-----END PRIVATE KEY-----';

    public function __construct() {
        if(!extension_loaded("openssl")) {
            throw new Exception('Aes encrypt failed, openssl module not loaded!');
        }
    }

    public function encode($data) {
        if(!openssl_public_encrypt($data , $crypted , $this->_public)) {
            $crypted = '';
        }
        return base64_encode($crypted);
    }

    public function decode($data) {
        $data = str_replace(array('%2B'),array('+'), $data);
        $data = base64_decode($data);
        if(!openssl_private_decrypt($data , $decrypted , $this->_private)) {
            $decrypted = '';
        }
        return $decrypted;
    }
}