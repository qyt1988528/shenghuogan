<?php
namespace Core\Api;
class Demo {
    public function __construct() {
        print_r('demo');exit("");
    }

    public function test(){
        $this->db->begin();
        $this->db->rollback();
        $this->db->commit();
    }
}