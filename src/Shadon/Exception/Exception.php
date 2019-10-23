<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shadon\Exception;

use JsonSerializable;
use Throwable;

/**
 * Class Exception.
 *
 * @author hehui<runphp@qq.com>
 */
class Exception extends \Exception implements JsonSerializable
{
    /**
     * tips.
     *
     * @var string
     */
    protected $hint;

    public function __construct($message = 'uncatched exception', $code = 500, $hint = '服务器异常', Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->hint = $hint;
    }

    /**
     * @return string
     */
    public function getHint(): string
    {
        return $this->hint;
    }

    public function jsonSerialize()
    {
        return [
            'exception' => \get_class($this),
            'message'   => $this->message,
            'tips'      => $this->hint,
            'code'      => $this->code,
            'file'      => $this->file,
            'line'      => $this->line,
            'trace'     => $this->getTrace(),
        ];
    }
}
