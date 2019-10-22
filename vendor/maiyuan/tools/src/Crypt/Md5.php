<?php
namespace Maiyuan\Tool\Crypt;

class Md5
{
    public function encode($data) {
        return md5($data);
    }
}