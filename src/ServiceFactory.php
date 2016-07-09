<?php
namespace Retrofit;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

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
        $cacheDir = __DIR__ . '/../cache';
        $config = new \ProxyManager\Configuration();
        $config->setProxiesTargetDir($cacheDir);
        $config->setGeneratorStrategy(new FileWriterGeneratorStrategy(new FileLocator($cacheDir)));
        spl_autoload_register($config->getProxyAutoloader());

        $this->proxyFactory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory($config);

        $this->annotationReader = new CachedReader(
            new AnnotationReader(),
            new PhpFileCache($cacheDir),
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