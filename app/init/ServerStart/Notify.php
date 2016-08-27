<?php
namespace YourNamespace\Init\ServerStart;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Zan\Framework\Foundation\Application;
use Zan\Framework\Foundation\Core\RunMode;
use Zan\Framework\Contract\Network\Bootable;
use Zan\Framework\Network\Http\Server;
use Zan\Framework\Store\Database\Sql\SqlMapInitiator;

/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 16/8/16
 * Time: 下午11:25
 *
 * TODO: fix WARNING	swReactorKqueue_wait: Kqueue[#0] Error: Bad file descriptor[9]
 */
class Notify implements Bootable
{
    private $path;
    private $fileStat = [];

    /**
     * @param Server $server
     */
    public function bootstrap($server){
        if (strtolower(RunMode::get()) !== "test") {
            return;
        }

        $server->swooleServer->on('workerStart', function(\swoole_server $swServer, $workerId) use($server) {
            $server->onWorkerStart($swServer, $workerId);

            // worker start 阶段sqlmap
            SqlMapInitiator::getInstance()->init();

            $this->path = Application::getInstance()->getAppPath();
            // Loader::getInstance()->load($this->path); // BUG
            foreach (static::iter($this->path) as $file => $matches) {
                include_once $file;
            }

            if ($workerId !== 0) {
                return;
            }

            if (function_exists("fsev_open")) {
                $this->fsevMonitor($swServer);
            } else {
                $this->init();
                $this->monitor($swServer);
            }
        });
    }

    private function fsevMonitor(\swoole_server $swServer) {
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("fork fail");
        } else if ($pid > 0) {
            return;
        } else {
            $fp = fsev_open();
            $appPath = Application::getInstance()->getAppPath();
            $sqlPath = Path::getSqlPath();

            while(true) {
                $data = fread($fp, 8192);
                $changeFiles = fsev_decode($data);
                foreach ($changeFiles as $file) {
                    if (strrpos($file, $sqlPath, -strlen($file)) !== false) {
                        break 2;
                    }
                    
                    if (strrpos($file, $appPath, -strlen($file)) !== false) {
                        break 2;
                    }
                }
            }
            fclose($fp);
            // reload
            posix_kill($swServer->master_pid, SIGUSR1);
        }
    }

    // 定时器需要在worker启动之后才能使用, 否则无法startServer
    private function monitor(\swoole_server $swServer) {
        swoole_timer_tick(1000, function() use($swServer) {
            if ($this->diff()) {
                $swServer->reload();
            }
        });
    }
    
    private function init() {
        foreach (static::iter($this->path) as $file => $matches) {
            $stat = stat($file);
            $this->fileStat[$file] = [
                "size" => $stat["size"],
                "mtime" => $stat["mtime"]
            ];
        }
    }
    
    private function diff() {
        $ret = false;
        foreach (static::iter($this->path) as $file => $matches) {
            $stat = stat($file);
            $isChange = !isset($this->fileStat[$file]) ||
                    $stat["size"] !== $this->fileStat[$file]["size"] ||
                    $stat["mtime"] !== $this->fileStat[$file]["mtime"];
            if ($isChange) {
                $this->fileStat[$file] = [
                    "size" => $stat["size"],
                    "mtime"=> $stat["mtime"],
                ];
                $ret = true;
            }
        }
        return $ret;
    }

    public static function iter($path, $pattern = '/^.+\.php$/i') {
        clearstatcache();

        $dir = realpath($path);

        $dirIter = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterIter = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::LEAVES_ONLY);
        $regexIter = new RegexIterator($iterIter, $pattern, RegexIterator::GET_MATCH);
        return $regexIter; // [ file => matches ]
    }
}