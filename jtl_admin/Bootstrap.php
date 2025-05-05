<?php

declare(strict_types=1);
/**
 * @package Plugin\jtl_admin
 * @author  Torsten Hemm
 */

namespace Plugin\jtl_admin;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;

/**
 * Class Bootstrap
 * @package Plugin\jtl_admin
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
        $dispatcher->listen('shop.hook.' . \HOOK_SMARTY_OUTPUTFILTER, function ($args) use ($plugin, $db, $cache) {
            echo '<div style="background-color:red; color:white; padding:10px; position:fixed; bottom:0; left:0; right:0; z-index:9999;">Hello World!</div>';
        });
    }


    /**
     * Rendert den Inhalt des Admin-Menü-Tabs
     *
     * @param string $tabName - Name des Tabs, wie in der info.xml definiert
     * @param int $menuID - ID des Menüeintrags
     * @param JTLSmarty $smarty - Smarty-Instanz
     * @return string - Der gerenderte Inhalt
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        if ($tabName !== 'Admin Demo') {
            return parent::renderAdminMenuTab($tabName, $menuID, $smarty);
        }
        $plugin = $this->getPlugin();

        // Testdaten
        $testData = [
            'item1' => 'Test Eintrag 1',
            'item2' => 'Test Eintrag 2',
            'item3' => 'Test Eintrag 3',
        ];

        // Template-Pfad direkt definieren
        $templatePath = $plugin->getPaths()->getBasePath() . 'adminmenu/templates/adminmenu.tpl';
        try {
            return $smarty->assign('testData', $testData)
                ->assign('plugin', $plugin)
                ->fetch($templatePath);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Fehler beim Laden des Templates: ' . 
                   htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
