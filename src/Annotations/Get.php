<?php
/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 上午11:07
 */

namespace Retrofit\Annotations;

/**
 * Class Method
 * @package Retrofit\Annotations
 * @Annotation
 * @Target({"METHOD"})
 */
class Get
{
    /** @var string */
    public $url;

    static function __set_state($values)
    {
        $r = new Get();
        $r->url = $values['url'];
        return $r;
    }
}