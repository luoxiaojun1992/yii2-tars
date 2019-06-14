<?php

/*
 * This file is part of the huang-yi/laravel-swoole-http package.
 *
 * (c) Huang Yi <coodeer@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lxj\Yii2\Tars;

use Tars\core\Request as TarsRequest;

class Request
{
    /**
     * @var \yii\web\Request
     */
    protected $yii2Request;

    /**
     * Make a request.
     *
     * @param TarsRequest $tarsRequest
     * @return Request
     */
    public static function make(TarsRequest $tarsRequest)
    {
        return new static($tarsRequest);
    }

    /**
     * Request constructor.
     * @param $tarsRequest
     */
    public function __construct(TarsRequest $tarsRequest)
    {
        $this->createYii2Request($tarsRequest);
    }

    /**
     * Create Yii2 Request.
     *
     * @param $tarsRequest
     */
    protected function createYii2Request($tarsRequest)
    {
        $this->yii2Request = (new Yii2Request())->setTarsRequest($tarsRequest);
    }

    /**
     * @return \yii\web\Request
     */
    public function toYii2()
    {
        return $this->getYii2Request();
    }

    /**
     * @return \yii\web\Request
     */
    public function getYii2Request()
    {
        return $this->yii2Request;
    }
}
