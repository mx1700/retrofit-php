<?php
namespace Retrofit;
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
    private $className;
    private $refClass;
    private $annotationsReader;

    private $baseUrl;
    private $headers = [];
    private $query = [];
    private $body = [];
    private $timeout = 0;
    static private $options = ['baseUrl', 'headers', 'query', 'body', 'timeout'];


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
                    $map = $params[$param]; //TODO:判断是否存在属性
                    unset($params[$param]);
                    $params = array_merge($params, $map);
                }
            } else if ($annotation instanceof Headers) {
                $headers = array_merge($headers, $annotation->headers);
            } else if ($annotation instanceof Body) {
                foreach($annotation->params as $param) {
                    $body[$param] = $params[$param];    //TODO:判断是否存在属性
                    unset($params[$param]);
                }
            } else if ($annotation instanceof Timeout) {
                $timeout = $annotation->second;
            }
        }

        $url = $this->fillParam($url, $params, $findParam);
        foreach($headers as $key => $val) {
            $headers[$key] = $this->fillParam($val, $params, $findParam);
        }

        foreach($findParam as $key => $val) {
            unset($params[$key]);
        }
        $query = array_merge($query, $params);

        if ($this->baseUrl) {
            $url = $this->baseUrl . $url;
        }

        return $this->callApi($url, $method, $query, $body, $headers, $timeout);
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

    private function callApi($url, $method, array $query, array $body, array $headers, $timeout)
    {
        //var_dump($url, $method, $query, $body, $headers);
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);    //自动跳转
        //$curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);

        foreach($headers as $key => $val) {
            $curl->setHeader($key, $val);
        }

        if ($timeout) {
            $curl->setTimeout($timeout);
        }

        $r = null;
        if ($method == "GET") {
            $r = $curl->get($url, $query);
        } else if ($method == "POST") {
            if (!empty($query)) {
                $url = strpos($url, '?') > 0 ? $url.'&'.http_build_query($query) : $url.'?'.http_build_query($query);
            }
            $r = $curl->post($url, $body);
        }
        $curl->close();
        if ($curl->error) {
            //echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage;
            if ($curl->errorCode == 404) {
                return null;
            } else {
                throw new \Exception($curl->errorMessage, $curl->errorCode);
            }
        }

        return $r;
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