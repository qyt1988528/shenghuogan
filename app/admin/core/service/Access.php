<?php
namespace Admin\Core\Service;
use Admin\User\Model\AdminUser;

class Access
{
    const KEY = 'soufeelapp';
    public function getToken($user)
    {
        $str = $user->id.uniqid(self::KEY);
        $token = md5($str);
        $user->token = $token;
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->save();
        return $token;
    }
    public function verify($token)
    {
        $user = AdminUser::findFirst([
            "token = :token:",
            'bind' => [
                'token' => $token
            ]
        ]);
        return $user;
    }

}