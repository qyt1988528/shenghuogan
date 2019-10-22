<?php
namespace Core\Event;
use Phalcon\Di\Injectable;
use Core\Service\ResultSet;

/**
 * Common Event.
 */
class Dispatch extends Injectable
{
	public function beforeDispatch() {
		if($this->request->getMethod() == 'OPTIONS'){
			exit();
		}
		$params = $this->request->getParams();

		//公用结果集
		$this->getDI()->setShared('resultSet','Core\Service\ResultSet');


		$language = $this->request->getParam('_language');

		//设置语种（翻译）
		$lang = isset($this->config->language[$language]) ? $this->config->language[$language] : 'en';
		$this->request->setParam('lang', $lang);

		//缓存服务
		$this->getDI()->setShared('redisCache','Core\Service\RedisCache');
	}
}