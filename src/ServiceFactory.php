<?php
namespace Retrofit;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;

/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 下午5:58
 */
class ServiceFactory
{
    /**
     * @var \ProxyManager\Factory\LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $annotationReader;

    private $option;

    public function __construct($option = [])
    {
        $config = new \ProxyManager\Configuration();
        $config->setProxiesTargetDir(__DIR__ . '/../cache/proxy/');
        spl_autoload_register($config->getProxyAutoloader());

        $this->proxyFactory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory($config);

        $this->annotationReader = new CachedReader(
            new AnnotationReader(),
            new PhpFileCache('../cache'),
            $debug = true   //会自动更新缓存
        );

        $this->option = $option;
    }

    /**
     * @param $className string
     * @return \ProxyManager\Proxy\VirtualProxyInterface
     */
    public function create($className)
    {
        $reader = $this->annotationReader;
        $option = $this->option;
        return $this->proxyFactory->createProxy(
            $className,
            function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use($className, $reader, $option) {
                $wrappedObject = new ProxyObject($className, $reader, $option);
                $initializer   = null;
            }
        );
    }
}