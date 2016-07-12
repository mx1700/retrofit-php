<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Retrofit\Annotations\Get;
use Retrofit\Annotations\QueryMap;
use Retrofit\Annotations\Headers;
use Retrofit\Annotations\Post;
use Retrofit\Annotations\Body;
use Retrofit\Annotations\Timeout;

require '../vendor/autoload.php';


$bench = new \Ubench;
$bench->start();

$factory = new \Retrofit\ServiceFactory([
    'baseUrl' => 'https://api.github.com',
    "beforeRequest" => function(&$url, &$method, &$query, &$body, &$headers, &$timeout) {
        var_dump($url);
        $url = "http://baidu.com";
    },
    "afterRequest" => function(&$result, &$exception, $url, $method, $query, $body, $headers, $timeout) {
        //$result->login = '111';
    }
]);
$proxy = $factory->create(GithubService::class);
$factory->create(GithubService::class);

$user = $proxy->getUser("mx1700");
echo json_encode($user);


$bench->end();
echo $bench->getTime();
echo $bench->getMemoryUsage();

//https://api.github.com/users/mx1700?access_token=111


interface GithubService
{
    /**
     * @param $name
     * @return mixed
     * @Get("/users/{name}")
     * @Timeout(10)
     */
    function getUser($name);

    /**
     * @param $issues
     * @return mixed
     * @Post("/users?id={id}")
     * @Body({ "name" })
     */
    function getIssues($issues, $id = 123, $name = "zhangxx");
}
