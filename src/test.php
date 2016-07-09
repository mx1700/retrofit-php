<?php
namespace Retrofit;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Retrofit\Annotations\Get;
use Retrofit\Annotations\QueryMap;
use Retrofit\Annotations\Headers;
use Retrofit\Annotations\Post;
use Retrofit\Annotations\Body;

$loader = require '../vendor/autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));


$bench = new \Ubench;
$bench->start();

$factory = new ServiceFactory([
    "baseUrl" => "https://api.github.com",
    'query' => ['token' => '1111'],
    'body' => ['body1' => '111']
]);
$proxy = $factory->create(GithubService::class);
//$user = $proxy->getUser("mx1700", 123, [ "a" => "12345", "b" => 123 ]);
//var_dump($user);

$proxy->getIssues("aaa");

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
     * @QueryMap("filter")
     * @Headers({ "name" = "name is {name}" })
     */
    function getUser($name, $id = 0, array $filter = []);

    /**
     * @param $issues
     * @return mixed
     * @Post("/users?id={id}")
     * @Body({ "name" })
     */
    function getIssues($issues, $id = 123, $name = "zhangxx");
}
