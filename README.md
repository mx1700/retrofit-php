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

只需使用 注解 定义API的配置信息，然后通过 ServiceFactory 类生成接口的实现类

```php
$factory = new ServiceFactory([ 'baseUrl' => 'https://api.github.com' ]);
$service = $factory->create(GithubService::class);
```

使用生成的 service 对象就可以调用 http api 并返回结果

```php
$user = $service->getUser('mx1700');
```


License
=======