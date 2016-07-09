<?php
namespace Retrofit;
use Retrofit\Annotations\Get;
use \Curl\Curl;
use Retrofit\Annotations\Headers;
use Retrofit\Annotations\QueryMap;
use \Doctrine\Common\Annotations\Reader;
use Zend\Code\Exception\BadMethodCallException;
use Zend\Code\Exception\InvalidArgumentException;

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

    private $baseUrl;
    static private $options = ['baseUrl'];

    public function __construct($className, Reader $annotationsReader, array $option = [])
    {
        $this->className = $className;
        $this->refClass = new \ReflectionClass($className);
        $this->annotationsReader = $annotationsReader;

        $this->setOption($option);
    }

    function __call($name, $arguments)
    {
        $refMethod = $this->refClass->getMethod($name);
        $paramNames = array_map(function($refPar) { return $refPar->name; }, $refMethod->getParameters());
        $annotations = $this->annotationsReader->getMethodAnnotations($refMethod);

        $method = 'GET';
        $url = '';
        $params = array_combine($paramNames, $arguments);
        $headers = [];

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
            } else if ($annotation instanceof Headers) {
                $headers = $annotation->headers;
                foreach($headers as $key => $val) {
                    $headers[$key] = $this->fillParam($val, $params, $findParam);
                }
            }
        }

        //TODO:检查 curl 相关参数[get post put delete]等是否都存在
        $url = $this->fillParam($url, $params, $findParam);

        foreach($findParam as $key => $val) {
            unset($params[$key]);
        }

        if ($this->baseUrl) {
            $url = $this->baseUrl . $url;
        }
        return $this->callApi($url, $method, $params, $headers);
    }

    /**
     * @param $str
     * @param $params
     * @param $findParams
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    private function fillParam($str, &$params, &$findParams)
    {
        if(!$findParams) {
            $findParams = [];
        }
        preg_match_all('/\{(\w+)\}/', $str, $match);
        $pathPars = $match[1];
        foreach($pathPars as $pathPar) {
            if (isset($params[$pathPar])) {
                $val = urlencode($params[$pathPar]);
                $str = str_replace('{' . $pathPar . '}', $val, $str);
                $findParams[$pathPar] = $params[$pathPar];
            } else {
                throw new \Doctrine\Common\Annotations\AnnotationException("参数 \{$pathPar\} 未找到");
            }
        }
        return $str;
    }

    private function callApi($url, $method, array $query, array $headers)
    {
        var_dump($url, $method, $query, $headers);
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);    //自动跳转

        foreach($headers as $key => $val) {
            $curl->setHeader($key, $val);
        }

        if ($method == "GET") {
            return $curl->get($url, $query);
        } else if ($method == "POST") {
            return $curl->post($url, $query);
        }

        return null;
    }

    private function setOption($option) {
        foreach($option as $key => $val) {
            if (in_array($key, static::$options)) {
                $this->$key = $val;
            } else {
                throw new InvalidArgumentException("$key 是无效的 options");
            }
        }
    }
}