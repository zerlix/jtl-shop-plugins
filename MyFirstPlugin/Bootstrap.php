<?php declare(strict_types=1);
/**
 * @package Plugin\MyFirstPlugin
 * @author  Torsten Hemm
 */

namespace Plugin\MyFirstPlugin;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;

/**
 * Class Bootstrap
 * @package Plugin\MyFirstPlugin
 */
class Bootstrap extends Bootstrapper
{
    /**
     * @inheritdoc
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
        $plugin = $this->getPlugin();
        $db     = $this->getDB();
        $cache  = $this->getCache();
        $dispatcher->listen('shop.hook.' .\HOOK_SMARTY_OUTPUTFILTER, function ($args) use ($plugin, $db, $cache) {
            echo '<div style="background-color:red; color:white; padding:10px; position:fixed; bottom:0; left:0; right:0; z-index:9999;">Hello World!</div>';
        });
    }
}
