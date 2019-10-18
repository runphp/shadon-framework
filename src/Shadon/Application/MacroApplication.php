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

namespace Shadon\Application;

use Composer\Autoload\ClassLoader;
use DI;
use FastRoute;
use MongoDB\BSON\ObjectId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MacroApplication
 * hehui<runphp@qq.com>.
 */
class MacroApplication
{
    /**
     * @var Di\Container
     */
    private $di;

    /**
     * @var Request
     */
    private $request;

    private $dispatcher;

    /**
     * @var JsonResponse
     */
    private $response;

    /**
     * MacroApplication constructor.
     *
     * @param string      $namespace
     * @param ClassLoader $classLoader
     *
     * @throws \Exception
     */
    public function __construct(string $namespace, ClassLoader $classLoader)
    {
        $this->di = new DI\Container();
        $this->initRuntime($namespace);
        $this->initService($classLoader);
    }

    /**
     * @return int
     */
    public function main(): int
    {
        $routeInfo = $this->dispatcher->dispatch($this->request->getMethod(), $this->request->getPathInfo());
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found 没找到对应的方法
                $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
                $returnData = [
                    'data'   => new \stdClass(),
                    'status' => [
                        'error' => 'not found',
                        'tips'  => 'not found',
                        'code'  => Response::HTTP_NOT_FOUND,
                    ],
                ];
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed  方法不允许
                $this->response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
                $returnData = [
                    'data'   => new \stdClass(),
                    'status' => [
                        'error' => 'method not allowed',
                        'tips'  => 'method not allowed',
                        'code'  => Response::HTTP_METHOD_NOT_ALLOWED,
                    ],
                ];
                break;
            case FastRoute\Dispatcher::FOUND: // 找到对应的方法
                $handler = $routeInfo[1]; // 获得处理函数
                $vars = $routeInfo[2]; // 获取请求参数
                // ... call $handler with $vars // 调用处理函数
                $bizData = $handler(...array_values($vars));
                $returnData = is_scalar($bizData) ? ['result' => $bizData] : $bizData;
                $returnData = [
                    'data'   => $returnData,
                    'status' => [
                        'error' => '',
                        'tips'  => '',
                        'code'  => Response::HTTP_OK,
                    ],
                ];
                break;
        }
        $returnData['status']['reuqestId'] = APP['requestId'];
        $this->response->setData($returnData);
        $this->response->send();

        return 0;
    }

    /**
     * @param string $namespace
     *
     * @throws \Exception
     */
    private function initRuntime(string $namespace): void
    {
        // created default .env
        if (!file_exists('.env')) {
            file_put_contents('.env', preg_replace(
                    '/^APP_KEY=/m',
                    'APP_KEY='.base64_encode(random_bytes(32)),
                    file_get_contents('.env.example'))
            );
        }
        $dotenv = \Dotenv\Dotenv::create(getcwd());
        $dotenv->load();
        $appEnv = getenv('APP_ENV');
        $appKey = getenv('APP_KEY');
        \define('APP', [
            'namespace'  => $namespace,
            'env'        => $appEnv,
            'key'        => $appKey,
            'requestId'  => (string) new ObjectId(),
         ]);
    }

    /**
     * @param ClassLoader $classLoader
     */
    private function initService(ClassLoader $classLoader): void
    {
        $this->request = Request::createFromGlobals();
        $this->response = JsonResponse::create(null, Response::HTTP_OK, ['content-type' => 'application/json']);
        $this->dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r): void {
            $r->addRoute('GET', '/', function () {
                return 'Hello, I\'m Shadon (｡A｡)';
            });
            $r->addRoute(['POST', 'GET'], '/{module}/{controller}/{action}', function ($module, $controller, $method): void {
                //dd($module, $controller, $method);
                // init config
                $config = require 'var/config/'.APP['env'].'/config.php';
                // TODO
            });
        });
    }
}
