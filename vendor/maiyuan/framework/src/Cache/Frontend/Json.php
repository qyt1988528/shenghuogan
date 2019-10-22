<?php
namespace MDK\Cache\Frontend;
use \Phalcon\Cache\Frontend\Json as FrontendJson;

class Json extends FrontendJson
{
    /**
     * Serializes data before storing them
     */
    public function beforeStore($data)
	{
	    if(!is_string($data)) {
            $data = json_encode($data);
        }
		return $data;
	}
    /**
     * Unserializes data after retrieval
     */
    public function afterRetrieve($data) {
        if(is_string($data)) {
            $data = json_decode($data, true);
        }
		return $data;
	}
}