<?php
namespace Admin\User\Api;

use MDK\Api;
use Admin\User\Model\AdminRouter;;

/**
 * common config api
 */
class Router extends Api
{

	/**
	 * [$router router]
	 * @var Admin\Model\Router
	 */
	protected $router;
	/**
	 * [$list router list]
	 * @var array
	 */
	protected $list;


	public function __construct()
	{
		$this->router = AdminRouter::find([
			'order' => 'sort'
		]);
	}
	/**
	 * [fomat 格式化路由]
	 * @param  [array] $router [路由数组]
	 * @return [array]         [格式化后的数组]
	 */
	private function fomat()
	{
		foreach ($this->router->toArray() as $module) {
			if($module['parent_id'] == 0 && isset($module)){
				$module['tree'] = [];
				$module['pre'] = '';
				$module['parent'] = $module['parent_id'];
				$module['hidden'] = $module['hidden']=='true'?true:false;
				$result[] = $children = $this->getChildren($module);
			}
		}
		return $result;
	}
	/**
	 * [getChildren 递归获取子路由]
	 * @param  [type] $module [description]
	 * @param  [type] $router [description]
	 * @return [type]         [description]
	 */
	private function getChildren($module)
	{
		$router = $this->router->toArray();
		foreach ($router as $menu) {
			if($menu['parent_id'] == $module['id']){
				$menu['tree'] = $module['tree'];
				$menu['tree'][] = $module['id'];
				$menu['parent'] = $menu['parent_id'];
				$menu['hidden'] = $menu['hidden']=='true'?true:false;
				$menu['pre'] = count($menu['tree']) == 1?'　|- ':'　|　　|- ';
				$menu = $this->getChildren($menu,$router);
				$module['children'][] = $menu;
			}
		}
		return $module;
	}
	private function getGroup($item)
	{
		unset($item['parent'],$item['hidden'],$item['url'],$item['pre'],$item['sort'],$item['icon'],$item['path'],$item['tree'],$item['redirect']);
		if(isset($item['children'])){
			$item['children'] = array_map(array($this,'getGroup'), $item['children']);
		}
		return $item;
	}
	private function getSelect($item)
	{
		if(isset($item['tree'])){
			$item['tree'][] = $item['id'];
			unset($item['parent'],$item['hidden'],$item['view'],$item['url'],$item['pre'],$item['sort'],$item['icon'],$item['path']);

		}
		return $item;
	}
	private function getList($data)
	{
		foreach ($data as $k => $v)
		{
			if(isset($v['children'])){
				$tmp = $v['children'];
				unset($v['children']);
				$this->list[] = $v;
				$this->getList($tmp);
			}else{
				$this->list[] = $v;
			}
		}
		return $this->list;
	}
	public function getData(){
		$formatRouter = $this->fomat();
		$this->getList($formatRouter);
		$data['list'] = $this->list;
		$data['select'] = $this->getSelect($formatRouter);
		$header = [
			'id'        => 0 ,
			'name'      =>"作为一级路由" ,
			'parent_id' => 0 ,
		];
		array_unshift($data['select'],$header);
		return $data;
	}
	public function getGroupRouter()
	{
		$formatRouter = $this->fomat();
		$groupRouter = array_map(array($this,'getGroup'), $formatRouter);
		return $groupRouter;
	}


}