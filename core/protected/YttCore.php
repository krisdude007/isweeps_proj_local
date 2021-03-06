<?php

class YttCore extends CWebApplication {

    private $_viewPath;
    private $_layoutPath;

    public function getOverrideViewPath($viewName) {
        if ($this->_viewPath !== null) {
            return $this->_viewPath;
        } else {
            if(file_exists(Yii::getPathOfAlias('client') . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->controller->getId() . DIRECTORY_SEPARATOR . $viewName . '.php')){
                return $this->_viewPath = Yii::getPathOfAlias('client') . DIRECTORY_SEPARATOR . 'views';
            } else {
                return $this->_viewPath = Yii::getPathOfAlias('core').DIRECTORY_SEPARATOR.'views';
            }
        }
    }

    public function getLayoutPath() {
        if ($this->_layoutPath !== null) {
            return $this->_layoutPath;
        } else {
            if(file_exists(Yii::getPathOfAlias('client') . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->controller->layout . '.php')){
                return $this->_layoutPath = Yii::getPathOfAlias('client') . DIRECTORY_SEPARATOR . 'views';
            } else {
                return $this->_layoutPath = Yii::getPathOfAlias('core').DIRECTORY_SEPARATOR.'views';
            }
        }
    }

    public function createController($route, $owner=null) {
        if ($owner === null)
            $owner = $this;
        if (($route = trim($route, '/')) === '')
            $route = $owner->defaultController;
        $caseSensitive = $this->getUrlManager()->caseSensitive;

        $route.='/';
        while (($pos = strpos($route, '/')) !== false) {
            $id = substr($route, 0, $pos);
            if (!preg_match('/^\w+$/', $id))
                return null;
            if (!$caseSensitive)
                $id = strtolower($id);
            $route = (string) substr($route, $pos + 1);
            if (!isset($basePath)) {  // first segment
                if (isset($owner->controllerMap[$id])) {
                    return array(
                        Yii::createComponent($owner->controllerMap[$id], $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    );
                }

                if (($module = $owner->getModule($id)) !== null)
                    return $this->createController($route, $module);

                $basePath = $owner->getControllerPath();
                $controllerID = '';
            }
            else
                $controllerID.='/';
            $className = ucfirst($id) . 'Controller';
            $clientClassName = 'client' . ucfirst($id) . 'Controller';
            $classFile = $basePath . DIRECTORY_SEPARATOR . $className . '.php';
            $clientClassFile = Yii::getPathOfAlias('client') . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'client' . $className . '.php';
            if ($owner->controllerNamespace !== null)
                $className = $owner->controllerNamespace . '\\' . $className;
            if (is_file($classFile)) {
                if (!class_exists($className, false))
                    require_once($classFile);
                if (is_file($clientClassFile)) {
                    require_once($clientClassFile);
                    if (class_exists($clientClassName) && is_subclass_of($clientClassName, 'CController')) {
                        return array(
                            new $clientClassName($controllerID . $id, $owner === $this ? null : $owner),
                            $this->parseActionParams($route),
                        );
                    }
                }
                if (class_exists($className, false) && is_subclass_of($className, 'CController')) {
                    $id[0] = strtolower($id[0]);
                    return array(
                        new $className($controllerID . $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    );
                }
                return null;
            }
            $controllerID.=$id;
            $basePath.=DIRECTORY_SEPARATOR . $id;
        }
    }

}

?>
