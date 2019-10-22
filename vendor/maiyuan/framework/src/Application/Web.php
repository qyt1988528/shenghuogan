<?php

namespace MDK\Application;
use MDK\Application;

/**
 * Console class.
 */
class Web extends Application
{

    public function test() {
        $router = $this->router;
        $router->handle();
        print_r($router->getRoutes());exit("");
        $modules = $this->getModules();
        $routeModule = $router->getModuleName();
    }

    public function run() {
        if(isset($_GET['debug'])) {
            $this->test();
        }
        echo $this->handle()->getContent();
    }


    /**
     * Clear application cache.
     * @param array $cacheDir Cache directory.
     * @return void
     */
    public function clearCache($cacheDir = []) {
        $cacheOutput = $this->_dependencyInjector->get('cacheOutput');
        $cacheData = $this->_dependencyInjector->get('cacheData');
        $config = $this->_dependencyInjector->get('config');

        $cacheOutput->flush();
        $cacheData->flush();

        if (empty($cacheDir)) {
            $cacheDir = [
                'cache',
                'view',
                'metadata',
                'annotations'
            ];
        }

        // Files deleter helper.
        $deleteFiles = function ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        };

        // Clear files cache.
        if (isset($config->system->cache->cacheDir) && in_array('cache', $cacheDir)) {
            $deleteFiles(glob($config->system->cache->cacheDir . '*'));
        }

        // Clear view cache.
        if (in_array('view', $cacheDir)) {
            $deleteFiles(glob($config->system->view->compiledPath . '*'));
        }

        // Clear metadata cache.
        if ($config->system->metadata && $config->system->metadata->metaDataDir && in_array('metadata', $cacheDir)) {
            $deleteFiles(glob($config->system->metadata->metaDataDir . '*'));
        }
        // Clear annotations cache.
        if ($config->system->annotations && $config->system->annotations->annotationsDir && in_array('annotations', $cacheDir)) {
            $deleteFiles(glob($config->system->annotations->annotationsDir . '*'));
        }
    }

    /**
     * Handles a MVC request
     */
    public function handle($uri = null) {
        $dependencyInjector = null;
        $eventsManager = null;
        $router = null;
        $dispatcher = null;
        $response = null;
        $view = null;
        $module = null;
        $moduleObject = null;
        $moduleName = null;
        $className = null;
        $path = null;
        $implicitView = null;
        $returnedResponse = null;
        $controller = null;
        $possibleResponse = null;
        $renderStatus = null;
        $matchedRoute = null;
        $match = null;

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injection object is required to access internal services");
        }

        $eventsManager = $this->_eventsManager;

        /**
         * Call boot event, this allow the developer to perform initialization actions
         */
        if (is_object($eventsManager)) {
            if ($eventsManager->fire("application:boot", $this) === false) {
                return false;
            }
        }

        $router = $dependencyInjector->getShared("router");

        /**
         * Handle the URI pattern (if any)
         */
        $router->handle($uri);

        /**
         * If a 'match' callback was defined in the matched route
         * The whole dispatcher+view behavior can be overridden by the developer
         */
        $matchedRoute = $router->getMatchedRoute();
        if (is_object($matchedRoute)) {
            $match = $matchedRoute->getMatch();
            if ($match !== null) {

                if ($match instanceof \Closure) {
                    $match = \Closure::bind($match, $dependencyInjector);
                }

                /**
                 * Directly call the match callback
                 */
                $possibleResponse = call_user_func_array($match, $router->getParams());

                /**
                 * If the returned value is a string return it as body
                 */
                if (is_string($possibleResponse)) {
                    $response = $dependencyInjector->getShared("response");
                    $response->setContent($possibleResponse);
                    return $response;
                }

                /**
                 * If the returned string is a ResponseInterface use it as response
                 */
                if (is_object($possibleResponse)) {
                    if ($possibleResponse instanceof ResponseInterface) {
                        $possibleResponse->sendHeaders();
                        $possibleResponse->sendCookies();
                        return $possibleResponse;
                    }
                }
            }
        }

        /**
         * If the router doesn't return a valid module we use the default module
         */
        $moduleName = $router->getModuleName();
        if (!$moduleName) {
            $moduleName = $this->_defaultModule;
        }
        $moduleObject = null;

        /**
         * Process the module definition
         */
        if ($moduleName) {

            if (is_object($eventsManager)) {
                if ($eventsManager->fire("application:beforeStartModule", $this, $moduleName) === false) {
                    return false;
                }
            }

            /**
             * Gets the module definition
             */
            $module = $this->getModule($moduleName);
            /**
             * A module definition must ne an array or an object
             */
            if (!is_array($module) && !is_object($module)) {
                throw new Exception("Invalid module definition");
            }

            /**
             * An array module definition contains a path to a module definition class
             */
            if (is_array($module)) {
                /**
                 * Class name used to load the module definition
                 */
                $className = isset($module["className"]) ? $module["className"] : "Module";
                /**
                 * If developer specify a path try to include the file
                 */
                $path = isset($module["path"]) ? $module["path"] : null;
                if ($path) {
                    if (!class_exists($className, false)) {
                        if (!file_exists($path)) {
                            throw new Exception("Module definition path '" . $path . "' doesn't exist");
                        }
                        require $path;
                    }
                }
                $moduleObject = $dependencyInjector->get($className);

                /**
                 * 'registerAutoloaders' and 'registerServices' are automatically called
                 */
                $moduleObject->registerAutoloaders($dependencyInjector);
                $moduleObject->registerServices($dependencyInjector);

            } else {

                /**
                 * A module definition object, can be a Closure instance
                 */
                if (!($module instanceof \Closure)) {
                    throw new Exception("Invalid module definition");
                }

                $moduleObject = call_user_func_array($module, [$dependencyInjector]);
            }

            /**
             * Calling afterStartModule event
             */
            if (is_object($eventsManager)) {
                $eventsManager->fire("application:afterStartModule", $this, $moduleObject);
            }
        }

        /**
         * Check whether use implicit views or not
         */
        $implicitView = $this->_implicitView;

        if ($implicitView === true) {
            $view = $dependencyInjector->getShared("view");
        }

        /**
         * We get the parameters from the router and assign them to the dispatcher
         * Assign the values passed from the router
         */
        $dispatcher = $dependencyInjector->getShared("dispatcher");
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());

        /**
         * Start the view component (start output buffering)
         */
        if ($implicitView === true) {
            $view->start();
        }

        /**
         * Calling beforeHandleRequest
         */
        if (is_object($eventsManager)) {
            if ($eventsManager->fire("application:beforeHandleRequest", $this, $dispatcher) === false) {
                return false;
            }
        }

        /**
         * The dispatcher must return an object
         */
        $controller = $dispatcher->dispatch();

        /**
         * Get the latest value returned by an action
         */
        $possibleResponse = $dispatcher->getReturnedValue();
        /**
         * Returning false from an action cancels the view
         */
        if (is_bool($possibleResponse) && $possibleResponse === false) {
            $response = $dependencyInjector->getShared("response");
        } else {

            /**
             * Returning a string makes use it as the body of the response
             */
            if (is_string($possibleResponse)) {
                $response = $dependencyInjector->getShared("response");
                $response->setContent($possibleResponse);
            } else {

                /**
                 * Check if the returned object is already a response
                 */
                $returnedResponse = ((is_object($possibleResponse)) && ($possibleResponse instanceof ResponseInterface));
                /**
                 * Calling afterHandleRequest
                 */
                if (is_object($eventsManager)) {
                    $eventsManager->fire("application:afterHandleRequest", $this, $controller);
                }

                /**
                 * If the dispatcher returns an object we try to render the view in auto-rendering mode
                 */
                if ($returnedResponse === false && $implicitView === true) {
                    if (is_object($controller)) {
                        $renderStatus = true;

                        /**
                         * This allows to make a custom view render
                         */
                        if (is_object($eventsManager)) {
                            $renderStatus = $eventsManager->fire("application:viewRender", $this, $view);
                        }
                        /**
                         * Check if the view process has been treated by the developer
                         */
                        if ($renderStatus !== false) {

                            /**
                             * Automatic render based on the latest controller executed
                             */
                            $view->render(
                                $dispatcher->getControllerName(),
                                $dispatcher->getActionName()
                            );
                        }
                    }
                }

                /**
                 * Finish the view component (stop output buffering)
                 */
                if ($implicitView === true) {
                    $view->finish();
                }

                if ($returnedResponse === true) {

                    /**
                     * We don't need to create a response because there is one already created
                     */
                    $response = $possibleResponse;
                } else {
                    $response = $dependencyInjector->getShared("response");
                    if ($implicitView === true) {

                        /**
                        * The content returned by the view is passed to the response service
                        */
                        $response->setContent($view->getContent());
                    }
                }
            }
        }

        /**
         * Calling beforeSendResponse
         */
        if (is_object($eventsManager)) {
            $eventsManager->fire("application:beforeSendResponse", $this, $response);
        }

        /**
         * Headers and Cookies are automatically sent
         */
        $response->sendHeaders();
        $response->sendCookies();

        /**
         * Return the response
         */
        return $response;
    }
}