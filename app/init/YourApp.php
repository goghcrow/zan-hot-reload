<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 16/8/16
 * Time: 下午2:40
 */
namespace YourNamespace\Init;

use YourNamespace\Init\ServerStart\Notify;
use Zan\Framework\Foundation\Application;
use Zan\Framework\Foundation\Booting\InitializeCache;
use Zan\Framework\Foundation\Booting\InitializeCliInput;
use Zan\Framework\Foundation\Booting\InitializeDebug;
use Zan\Framework\Foundation\Booting\InitializeEnv;
use Zan\Framework\Foundation\Booting\InitializeKv;
use Zan\Framework\Foundation\Booting\InitializePathes;
use Zan\Framework\Foundation\Booting\InitializeRunMode;
use Zan\Framework\Foundation\Booting\InitializeSharedObjects;
use Zan\Framework\Foundation\Booting\LoadConfiguration;
use Zan\Framework\Foundation\Booting\RegisterClassAliases;
use Zan\Framework\Foundation\Container\Di;
use Zan\Framework\Foundation\Core\Config;
use Zan\Framework\Foundation\Core\RunMode;
use Zan\Framework\Network\Tcp\Server as TcpServer;
use Zan\Framework\Network\Tcp\ServerStart\InitializeSqlMap;
use swoole_server as SwooleTcpServer;

class YourApp extends Application
{
    protected function bootstrap() {
        // parent::bootstrap();

        $this->setContainer();

        $this->loadFrameworkFiles();

        $bootstrapItems = [
            InitializeEnv::class,
            InitializeCliInput::class,
            InitializeRunMode::class,
            InitializeDebug::class,
            InitializePathes::class,
            LoadConfiguration::class,
            InitializeSharedObjects::class,
            RegisterClassAliases::class,
            // LoadFiles::class,
            InitializeCache::class,
            InitializeKv::class,
        ];

        foreach ($bootstrapItems as $bootstrap) {
            $this->make($bootstrap)->bootstrap($this);
        }
    }

    private function loadFrameworkFiles() {
        $basePath = $this->basePath;

        $paths = [
            $basePath . '/vendor/zanphp/zan/src',
            $basePath . '/vendor/zanphp/nova/src',
            // $basePath . '/src',
        ];

        $except = [
            realpath($basePath . "/vendor/zanphp/zan/src/Foundation/View/Pages/Error.php") => true,
            realpath($basePath . "/vendor/zanphp/zan/src/Network/Tcp/Client.php") => true,
            realpath($basePath . "/vendor/zanphp/zan/src/Utilities/Types/Fluent.php") => true,
            realpath($basePath . "/vendor/zanphp/zan/src/Foundation/View/Pagelet/Component/ComponentAbstract.php") => true,
        ];


        foreach ($paths as $path) {
            foreach (Notify::iter($path) as $file => $matches) {
                if (isset($except[$file])) {
                    unset($except[$file]);
                    continue;
                }
                include_once $file;
            }
        }
    }

    private function dontLoadSql($server) {
        $refServer = new \ReflectionObject($server);
        $refProp = $refServer->getProperty("serverStartItems");
        $refProp->setAccessible(true);
        $items = $refProp->getValue($server);
        foreach ($items as $i => $item) {
            if ($item === InitializeSqlMap::class) {
                unset($items[$i]);
                break;
            }
        }
        $refProp->setValue($server, $items);
    }

    public function createTcpServer() {
        $config = Config::get('server');
        if (empty($config)) {
            throw new \RuntimeException('tcp server config not found');
        }

        $host = $config['host'];
        $port = $config['port'];
        $config = $config['config'];
        if (empty($host) || empty($port)) {
            throw new \RuntimeException('tcp server config error: empty ip/port');
        }

        $swooleServer = Di::make(SwooleTcpServer::class, [$host, $port], true);
        $server = Di::make(TcpServer::class, [$swooleServer, $config]);

        if (strtolower(RunMode::get()) === "test") {
            $this->dontLoadSql($server);
        }

        return $server;
    }
}