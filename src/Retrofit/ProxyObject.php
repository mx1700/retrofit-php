<?php
namespace Retrofit;
use Doctrine\Common\Annotations\AnnotationException;
use Retrofit\Annotations\Body;
use Retrofit\Annotations\Get;
use \Curl\Curl;
use Retrofit\Annotations\Headers;
use Retrofit\Annotations\Post;
use Retrofit\Annotations\QueryMap;
use \Doctrine\Common\Annotations\Reader;
use Retrofit\Annotations\Timeout;
use Zend\Code\Exception\InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 下午5:59
 */
class ProxyObject
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var \ReflectionClass
     */
    private $refClass;

    /**
     * @var Reader
     */
    private $annotationsReader;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var array
     */
    private $body = [];

    /**
     * @var int
     */
    private $timeout = 0;

    /**
     * @var array
     */

    /**
     * @var \Closure
     */
    private $beforeRequest;
    /**
     * @var \Closure
     */
    private $afterRequest;

    static private $options = ['baseUrl', 'headers', 'query', 'body', 'timeout', 'beforeRequest', 'afterRequest'];


    public function __construct($className, Reader $annotationsReader, array $option = [])
    {
        $this->className = $className;
        $this->refClass = new \ReflectionClass($className);
        $this->annotationsReader = $annotationsReader;

        $this->setOption($option);
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

    function __call($name, $arguments)
    {
        $refMethod = $this->refClass->getMethod($name);
        $paramNames = array_map(function($refPar) { return $refPar->name; }, $refMethod->getParameters());
        $annotations = $this->annotationsReader->getMethodAnnotations($refMethod);

        $method = 'GET';
        $url = '';
        $params = array_combine($paramNames, $arguments);
        $query = $this->query;
        $body = $this->body;
        $headers = $this->headers;
        $timeout = $this->timeout;

        foreach($annotations as $annotation) {
            if ($annotation instanceof Get) {
                $method = 'GET';
                $url = $annotation->url;
            } else if ($annotation instanceof Post) {
                $method = 'POST';
                $url = $annotation->url;
            } else if ($annotation instanceof QueryMap) {
                foreach($annotation->params as $param) {
                    if (!isset($params[$param])) {
                        throw new AnnotationException("QueryMap 参数 {{$param}} 未找到");
                    }
                    $map = $params[$param];
                    unset($params[$param]);
                    $params = array_merge($params, $map);
                }
            } else if ($annotation instanceof Headers) {
                $headers = array_merge($headers, $annotation->headers);
            } else if ($annotation instanceof Body) {
                foreach($annotation->params as $param) {
                    if (!isset($params[$param])) {
                        throw new AnnotationException("Body 参数 {{$param}} 未找到");
                    }
                    $body[$param] = $params[$param];
                    unset($params[$param]);
                }
            } else if ($annotation instanceof Timeout) {
                $timeout = $annotation->second;
            }
        }

        $url = $this->fillParam($url, $params, $findParam, $method);
        foreach($headers as $key => $val) {
            $headers[$key] = $this->fillParam($val, $params, $findParam, 'Headers.'.$key);
        }

        foreach($findParam as $key => $val) {
            unset($params[$key]);
        }
        $query = array_merge($query, $params);

        if ($this->baseUrl) {
            $url = $this->baseUrl . $url;
        }

        return $this->httpCall($url, $method, $query, $body, $headers, $timeout);
    }

    /**
     * @param $str
     * @param $params
     * @param $findParams
     * @param $className
     * @return mixed
     * @throws AnnotationException
     */
    private function fillParam($str,array $params, &$findParams, $className)
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
                throw new AnnotationException("{$className} 参数 {{$pathPar}} 未找到");
            }
        }
        return $str;
    }

    private function httpCall($url, $method, array $query, array $body, array $headers, $timeout)
    {
        //var_dump($url, $method, $query, $body, $headers, $timeout);
        if ($this->beforeRequest) {
            $beforeRequest = $this->beforeRequest;
            $beforeRequest($url, $method, $query, $body, $headers, $timeout);
        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false); //TODO: 不应该关闭 ssl 验证，但是 windows 版 php 默认不加载根证书，https 会报错

        foreach($headers as $key => $val) {
            $curl->setHeader($key, $val);
        }

        if ($timeout) {
            $curl->setTimeout($timeout);
        }

        $result = null;
        $exception = null;
        if ($method == "GET") {
            $result = $curl->get($url, $query);
        } else if ($method == "POST") {
            if (!empty($query)) {
                $url = strpos($url, '?') > 0 ? $url.'&'.http_build_query($query) : $url.'?'.http_build_query($query);
            }
            $result = $curl->post($url, $body);
        }
        $curl->close();

        if ($curl->error) {
            if ($curl->errorCode == 404) {
                $result = null;
            } else {
                $exception = new HttpException($curl->errorMessage, $curl->errorCode);
            }
        }

        if ($this->afterRequest) {
            $afterRequest = $this->afterRequest;
            $afterRequest($result, $exception, $url, $method, $query, $body, $headers, $timeout);
        }

        if ($exception) {
            throw $exception;
        }

        return $result;
    }
}