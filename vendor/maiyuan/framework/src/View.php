<?php

namespace MDK;
use \Phalcon\Mvc\View as PhalconView;
use \Phalcon\Cache\BackendInterface;

/**
 * Bootstrap class.
 */
class View extends PhalconView
{
    public function cache($options = true)
	{
		$viewOptions = null; $cacheOptions = null; $key = null; $value = null; $cacheLevel = null;
		if (is_array($options)) {
			$viewOptions = $this->_options;
			if (!is_array($viewOptions)) {
				$viewOptions = [];
			}
            /**
             * Get the default cache options
             */
            $cacheOptions = isset($viewOptions["cache"]) ? $viewOptions["cache"] : [];
            foreach ($options as $key => $value) {
                $cacheOptions[$key] = $value;
            }
            /**
             * Check if the user has defined a default cache level or use self::LEVEL_MAIN_LAYOUT as default
             */
            $this->_cacheLevel = isset($cacheOptions["level"]) ?: self::LEVEL_MAIN_LAYOUT;
			$viewOptions["cache"] = $cacheOptions;
			$this->_options = $viewOptions;
		} else {
            /**
             * If 'options' isn't an array we enable the cache with default options
             */
            if ($options) {
                $this->_cacheLevel = self::LEVEL_MAIN_LAYOUT;
			} else {
                $this->_cacheLevel = self::LEVEL_NO_RENDER;
			}
        }
		return $this;
	}

    protected function _engineRender($engines, $viewPath,  $silence,  $mustClean, BackendInterface $cache = null)
	{
		$notExists = null;
        $renderLevel = null;
        $cacheLevel = null;
		$key = null; $lifetime = null; $viewsDir= null; $basePath= null; $viewsDirPath= null;
			$viewOptions= null; $cacheOptions= null;$cachedView= null; $viewParams= null; $eventsManager= null;
			$extension= null;$engine= null;$viewEnginePath= null;$viewEnginePaths = null;
		$notExists = true;
        $basePath = $this->_basePath= null;
        $viewParams = $this->_viewParams= null;
        $eventsManager = $this->_eventsManager= null;
        $viewEnginePaths = [];

        foreach ($this->getViewsDirs() as $viewsDir) {
            if (!$this->_isAbsolutePath($viewPath)) {
                $viewsDirPath = $basePath . $viewsDir . $viewPath;
            } else {
                $viewsDirPath = $viewPath;
			}

            $renderLevel = (int) $this->_renderLevel;
            $cacheLevel = (int) $this->_cacheLevel;
				if ($renderLevel >= $cacheLevel) {
                    /**
                     * Check if the cache is started, the first time a cache is started we start the
                     * cache
                     */
                    if (!$cache->isStarted()) {
                    $key = null;
							$lifetime = null;

						$viewOptions = $this->_options;

						/**
                         * Check if the user has defined a different options to the default
                         */
						if($viewOptions["cache"]) {
                            foreach ($viewOptions["cache"] as $cacheOptions ) {
                                if (is_array($cacheOptions)) {
                                    $key = $cacheOptions['key'];
                                    $lifetime = $cacheOptions['lifetime'];
                                }
                            }
                        }


						/**
                         * If a cache key is not set we create one using a md5
                         */
						if ($key === null) {
                            $key = md5(viewPath);
						}

						/**
                         * We start the cache using the key set
                         */
						$cachedView = $cache->start($key, 3600);
						if ($cachedView !== null) {
                            $this->_content = $cachedView;
							return null;
						}
					}
					/**
                     * This method only returns true if the cache has not expired
                     */
					if (!$cache->isFresh()) {
						return null;
					}
				}

			/**
             * Views are rendered in each engine
             */
			foreach ($engines as $extension => $engine) {
                $viewEnginePath = $viewsDirPath . $extension;
				if (file_exists($viewEnginePath)) {


                    $engine->render($viewEnginePath, $viewParams, $mustClean);

					/**
                     * Call afterRenderView if there is an events manager available
                     */
					$notExists = false;

					break;
				}

                $viewEnginePaths[] = $viewEnginePath;
            }


		}

		if ($notExists === true) {
			if (!$silence) {
                throw new Exception("View '" . $viewPath . "' was not found in any of the views directory");
            }
		}
	}

    public function render($controllerName, $actionName, $params = null)
	{
		$silence = null;
        $mustClean= null;
        $renderLevel= null;
         $layoutsDir= null;
         $layout= null;
         $pickView= null;
         $layoutName= null;
         $engines= null;
         $renderView= null;
         $pickViewAction= null;
         $eventsManager= null;
         $disabledLevels= null;
         $templatesBefore= null;
         $templatesAfter= null;

         $templateBefore= null;
         $templateAfter= null;
         $cache= null;

		$this->_currentRenderLevel = 0;

		/**
         * If the view is disabled we simply update the buffer from any output produced in the controller
         */
		if ($this->_disabled !== false)  {
			$this->_content = ob_get_contents();
			return false;
		}
        $this->_controllerName = $controllerName;
			$this->_actionName = $actionName;
			$this->_params = $params;

		/**
         * Check if there is a layouts directory set
         */
		$layoutsDir = $this->_layoutsDir;
		if (!$layoutsDir) {
            $layoutsDir = "layouts/";
		}

		/**
         * Check if the user has defined a custom layout
         */
		$layout = $this->_layout;
		if ($layout) {
            $layoutName = $layout;
		} else {
            $layoutName = $controllerName;
		}

		/**
         * Load the template engines
         */
		$engines = $this->_loadTemplateEngines();
		/**
         * Check if the user has picked a view different than the automatic
         */
		$pickView = $this->_pickView;

		if ($pickView === null) {
            $renderView = $controllerName . "/" . $actionName;
		} else {

            /**
             * The 'picked' view is an array, where the first element is controller and the second the action
             */
            $renderView = $pickView[0];
			if ($layoutName === null)  {
			    if(in_array(pickViewAction, pickView[1])) {
                    $layoutName = $pickViewAction;

                }
			}
		}

		/**
         * Start the cache if there is a cache level enabled
         */
		if ($this->_cacheLevel) {
            $cache = $this->getCache();
		} else {
            $cache = null;
		}

		$eventsManager = $this->_eventsManager;


		/**
         * Get the current content in the buffer maybe some output from the controller?
         */
		$this->_content = ob_get_contents();

		$mustClean = true;
		$silence = true;

		/**
         * Disabled levels allow to avoid an specific level of rendering
         */
		$disabledLevels = $this->_disabledLevels;

		/**
         * Render level will tell use when to stop
         */
		$renderLevel = (int) $this->_renderLevel;
		if ($renderLevel) {

            /**
             * Inserts view related to action
             */
            if ($renderLevel >= self::LEVEL_ACTION_VIEW) {
                if (!isset($disabledLevels[self::LEVEL_ACTION_VIEW])) {
                    $this->_currentRenderLevel = self::LEVEL_ACTION_VIEW;
                    $this->_engineRender($engines, $renderView, $silence, $mustClean, $cache);
				}
            }

            /**
             * Inserts templates before layout
             */
            if ($renderLevel >= self::LEVEL_BEFORE_TEMPLATE)  {
                if (!isset($disabledLevels[self::LEVEL_BEFORE_TEMPLATE])) {
                    $this->_currentRenderLevel = self::LEVEL_BEFORE_TEMPLATE;
					$templatesBefore = $this->_templatesBefore;
					$silence = false;
					foreach ($templatesBefore as $templateBefore) {
                        $this->_engineRender($engines, $layoutsDir . $templateBefore, $silence, $mustClean, $cache);

                    }
					$silence = true;
				}
            }

            /**
             * Inserts controller layout
             */
            if ($renderLevel >= self::LEVEL_LAYOUT) {
                if (!isset($disabledLevels[self::LEVEL_LAYOUT])) {
                    $this->_currentRenderLevel = self::LEVEL_LAYOUT;
                    $this->_engineRender($engines, $layoutsDir . $layoutName, $silence, $mustClean, $cache);
				}
            }

            /**
             * Inserts templates after layout
             */
            if ($renderLevel >= self::LEVEL_AFTER_TEMPLATE) {
                if (!isset($disabledLevels[self::LEVEL_AFTER_TEMPLATE])) {
                    $this->_currentRenderLevel = self::LEVEL_AFTER_TEMPLATE;
                    $templatesAfter = $this->_templatesAfter;
					$silence = false;
					foreach ($templatesAfter as $templateAfter) {
                        $this->_engineRender($engines, $layoutsDir . $templateAfter, $silence, $mustClean, $cache);

                    }
					$silence = true;
				}
            }

            /**
             * Inserts main view
             */
            if ($renderLevel >= self::LEVEL_MAIN_LAYOUT) {
                if (!isset($disabledLevels[self::LEVEL_MAIN_LAYOUT])) {
                    $this->_currentRenderLevel = self::LEVEL_MAIN_LAYOUT;
					$this->_engineRender($engines, $this->_mainView, $silence, $mustClean, $cache);
				}
            }
            $this->_currentRenderLevel = 0;

			/**
             * Store the data in the cache
             */
            if ($cache->isStarted() && $cache->isFresh()) {
                $cache->save();
            } else {
                $cache->stop();
            }
		}

		return this;
	}

}