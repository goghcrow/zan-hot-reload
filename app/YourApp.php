<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 16/8/16
 * Time: 下午2:40
 */
namespace YourNamespace\Init;

use Kdt\App\Material\Client\Init\ServerStart\Notify;
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
use Zan\Framework\Foundation\Core\Config;

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
            // src文件延迟到workerstart阶段加载
            InitializeCache::class,
            InitializeKv::class,
        ];

        foreach ($bootstrapItems as $bootstrap) {
            $this->make($bootstrap)->bootstrap($this);
        }


        // 引入容器配置
        $diMap = Config::get("container");
        foreach ($diMap as $contract => $impl) {
            $instance = $this->container->make($impl, [], false);
            $this->container->set($contract, $instance); // Interface::class => Impl::class
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
        ];


        // $loader = Loader::getInstance();
        foreach ($paths as $path) {
            // $loader->load($path); // BUG
            foreach (Notify::iter($path) as $file => $matches) {
                if (isset($except[$file])) {
                    unset($except[$file]);
                    continue;
                }
                
                include_once $file;
            }
        }
    }
}