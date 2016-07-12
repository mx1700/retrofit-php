Retrofit-php
========

Retrofit-php 是一个 HTTP client 库，参考了 square/retrofit 的 API ，并使用 php 实现。


概述
--------
可以使用简单的接口来定义一个 HTTP client

```php
interface GithubService
{
    /**
     * @Get("/users/{name}")
     */
    function getUser($name);
}
```

API的配置信息是通过 注解 来定义的（如上的 Get 注解），然后通过 ServiceFactory 类自动生成接口的实现

```php
$factory = new ServiceFactory([ 'baseUrl' => 'https://api.github.com' ]);
$service = $factory->create(GithubService::class);
```

使用生成的 service 对象就可以调用 HTTP api 并返回结果

```php
$user = $service->getUser('mx1700');
```


License
=======