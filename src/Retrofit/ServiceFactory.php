<?php
namespace Retrofit;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
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

    /**
     * @var array
     */
    private $option;

    /**
     * ServiceFactory constructor.
     * @param array $option
     */
    public function __construct($option = [])
    {
        $cacheDir = isset($option['cacheDir']) ? $option['cacheDir'] : false;
        $debug = isset($option['debug']) ? $option['debug'] : false;
        unset($option['cacheDir']);
        unset($option['debug']);

        AnnotationRegistry::registerAutoloadNamespace('Retrofit', (dirname(__DIR__)));

        if ($cacheDir && !$debug) {
            $config = new \ProxyManager\Configuration();
            $config->setProxiesTargetDir($cacheDir);
            $config->setGeneratorStrategy(new FileWriterGeneratorStrategy(new FileLocator($cacheDir)));
            spl_autoload_register($config->getProxyAutoloader());
            $this->proxyFactory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory($config);

            $this->annotationReader = new CachedReader(
                new AnnotationReader(),
                new PhpFileCache($cacheDir),
                $debug
            );
        } else {
            $this->proxyFactory = new \ProxyManager\Factory\LazyLoadingValueHolderFactory();
            $this->annotationReader = new CachedReader(
                new AnnotationReader(),
                new ArrayCache(),
                $debug
            );
        }

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