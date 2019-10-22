<?php
namespace Admin\User\Api;
use MDK\Api;
use Admin\User\Model\AdminRoleUser;

class Helper extends Api
{
    private function getChildren($router,$parent)
    {
        if(isset($parent['id'])){
            foreach ($router as $children) {
                if($parent['id'] == $children['parent_id']){
                    $menu = $this->getChildren($router,$children);
                    $menu = $this->filter($menu);
                    $parent['children'][] = $menu;
                }
            }
        }
        return $parent;
    }
    private function filter($array){
        foreach ($array as $key => $value) {
            if(!$value){
                unset($array[$key]);
            }
        }
        if($array['hidden'] == 'true'){
            $array['hidden'] = true;
        }else{
            $array['hidden'] = false;
        }
        unset($array['id'],$array['parent_id'],$array['sort']);
        return $array;
    }
    /**
     * 格式化router
     * @param $router
     * @return array
     */
    public function formatRouter($router)
    {
        $result = [];
        foreach ($router as $module) {
            if($module['parent_id'] == 0){
                $module['path'] = '/'.$module['path'];
                $module['type'] = 'module';
                $module['children'][] = ['path'=>'/','redirect'=>'index'];
                $module = $this->getChildren($router,$module);
                $module = $this->filter($module);
                $result[] = $module;
            }
        }
        return $result;
    }

    public function power($role,$roleRouter){
        $phql = "DELETE FROM Admin\User\Model\AdminRoleRouter  WHERE role_id=".$role->id;
        $this->modelsManager->executeQuery($phql);

        $value = '';
        foreach($roleRouter as $key => $router){
            if($key == 0){
                $value .= '('.$role->id.','.$router.')';
            }else{
                $value .= ',( '.$role->id.','.$router.')';
            }
        }
        $phql = 'INSERT INTO admin_role_router  (role_id,router_id) VALUES '.$value;
        return  $this->di->get('db')->execute($phql);
    }

    public function setRole($user,$roles){
        $data = [];
        foreach($roles as $role){
            $userRole = new AdminRoleUser();
            $row['user_id'] = $user->id;
            $row['role_id'] = $role;
            $userRole->create($row);
        }
        return $this;
    }
}