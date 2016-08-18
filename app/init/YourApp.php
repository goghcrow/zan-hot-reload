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
    }

    private function loadFrameworkFiles() {
        $basePath = $this->basePath;

        $paths = [
            $basePath . '/vendor/zanphp/zan/src',
            $basePath . '/vendor/zanphp/nova/src',
            // $basePath . '/src',
        ];

        foreach ($paths as $path) {
            foreach (Notify::iter($path) as $file => $matches) {
                @include_once $file;
            }
        }
    }
}