<?php
/**
 * Created by PhpStorm.
 * User: lizhaoguang
 * Date: 16/7/9
 * Time: 下午6:31
 */

namespace Retrofit\Annotations;

/**
 * Class Method
 * @package Retrofit\Annotations
 * @Annotation
 * @Target({"METHOD"})
 */
class Body
{
    /**
     * @var array<string>
     */
    public $params;

    static function __set_state($values)
    {
        $r = new self();
        $r->params = $values['params'];
        return $r;
    }
}