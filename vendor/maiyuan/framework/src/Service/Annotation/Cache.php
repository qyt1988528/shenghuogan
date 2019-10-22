<?php
namespace MDK\Service\Annotation;
class Cache extends Abs
{
    protected function _getRequestId() {
        $method = $this->request->getMethod();
        $key = implode('|', [
            (int)$this->request->isAjax(),
            $this->request->getScheme(),
            $this->request->getHttpHost(),
            $this->request->getPort(),
            $this->dispatcher->getHandlerClass(),
            $this->request->getUri(),
            $method
        ]);
        return $key;
    }

    public function save(array $data) {
        $allow = ($this->request->getMethod() == 'GET') ? true : false;
        if($data && $this->cache->isStarted() && $allow) {
            $lifetime = $this->_annotation->getNamedParameter('lifetime');
            $key = $this->_annotation->getNamedParameter('key') ?: $this->_getRequestId();
            return $this->cache->save($key, $data, $lifetime);
        }
        return true;
    }

    public function saveView() {
        $option = [
            'lifetime' => $this->_annotation->getNamedParameter('lifetime'),
            'key' => $this->_annotation->getNamedParameter('key') ?: $this->_getRequestId()
        ];
        $this->view->cache($option);
        return true;
    }
}