<?php
namespace Retrofit;
use Retrofit\Annotations\Get;
use \Curl\Curl;
use Retrofit\Annotations\QueryMap;

/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 下午5:59
 */
class ProxyObject
{
    private $className;
    private $refClass;
    private $annotationsReader;

    public function __construct($className, \Doctrine\Common\Annotations\Reader $annotationsReader)
    {
        $this->className = $className;
        $this->refClass = new \ReflectionClass($className);
        $this->annotationsReader = $annotationsReader;
    }

    function __call($name, $arguments)
    {
        $refMethod = $this->refClass->getMethod($name);
        //TODO:检查方法是否存在
        $paramNames = array_map(function($refPar) { return $refPar->name; }, $refMethod->getParameters());
        $annotations = $this->annotationsReader->getMethodAnnotations($refMethod);

        $method = 'GET';
        $url = '';
        $params = array_combine($paramNames, $arguments);

        foreach($annotations as $annotation) {
            if ($annotation instanceof Get) {
                $method = 'GET';
                $url = $annotation->url;
            } else if ($annotation instanceof QueryMap) {
                foreach($annotation->params as $param) {
                    $map = $params[$param]; //TODO:判断是否存在属性
                    unset($params[$param]);
                    $params = array_merge($params, $map);
                }
            }
        }

        preg_match_all('/\{(\w+)\}/', $url, $match);
        $pathPars = $match[1];
        foreach($pathPars as $pathPar) {
            if (isset($params[$pathPar])) {
                $val = urlencode($params[$pathPar]);
                $url = str_replace('{' . $pathPar . '}', $val, $url);
                unset($params[$pathPar]);
            } else {
                throw new \Doctrine\Common\Annotations\AnnotationException("Path 参数 {$pathPar} 未找到");
            }
        }

        return $this->callApi($url, $method, $params);
        //TODO:处理网络,
    }

    private function callApi($url, $method, array $query)
    {
        //返回结果按照响应头处理
        var_dump($url, $method, $query);
        $curl = new Curl();
        if ($method == "GET") {
            return $curl->get($url, $query);
        } else if ($method == "POST") {
            return $curl->post($url, $query);
        }

        return null;
    }
}