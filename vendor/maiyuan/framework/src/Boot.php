<?php

namespace MDK;

final class Boot {
	
    /**
     * Application model
     * @var Mage_Core_Model_App
     */
    static private $_app;
	
    public static function app($mode = 'web') {
        if (null === self::$_app) {
			$class = 'MDK\Application\\' . ucfirst($mode);
            self::$_app = new $class();
        }
        return self::$_app;
    }
}