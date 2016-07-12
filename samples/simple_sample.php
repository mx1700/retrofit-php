<?php
/**
 * 调用 github api 的示例
 */

use Retrofit\ServiceFactory;
use Retrofit\Annotations\Get;

require '../vendor/autoload.php';

$factory = new ServiceFactory([ 'baseUrl' => 'https://api.github.com' ]);
$service = $factory->create(GithubService::class);

$user = $service->getUser('mx1700');
$repos = $service->getUser('mx1700', 'Blog');
$issues = $service->getIssues('mx1700', 'Blog', 1);

var_dump($user, $repos, $issues);


interface GithubService
{
    /**
     * @param $name
     * @return mixed
     * @Get("/users/{name}")
     */
    function getUser($name);

    /**
     * @param $user
     * @param $repos
     * @return mixed
     * @Get("/repos/{user}/{repos}")
     */
    function getRepos($user, $repos);

    /**
     * @param $user
     * @param $repos
     * @param int $page
     * @param int $per_page
     * @return mixed
     * @Get("/repos/{user}/{repos}/issues")
     */
    function getIssues($user, $repos, $page = 1, $per_page = 10);
}