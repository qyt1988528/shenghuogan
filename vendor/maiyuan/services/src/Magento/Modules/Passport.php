<?php
namespace Maiyuan\Service\Magento\Modules;

class Passport extends Module
{

    public function login($userName,$password)
    {
        $data = [
            'username' => $userName,
            'password' => $password
        ];
        return $this->client->post( 'passport/login',$data);
    }

    public function register($data){
        return $this->client->post('passport/register',$data);
    }

    public function loginWithFacebook($data)
    {
        return $this->client->post('passport/login/facebook',$data);
    }

    public function forgotPassword($email,$condition=[])
    {
        $condition['email'] = $email;
        return $this->client->post('passport/forget',$condition);
    }

    public function emailExists($email)
    {
        return $this->client->get('passport/exists',['email'=>$email]);
    }
}