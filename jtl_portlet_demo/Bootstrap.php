<?php declare(strict_types=1);

namespace Plugin\jtl_portlet_demo;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Smarty\JTLSmarty;

/**
 * Class Bootstrap
 * @package Plugin\jtl_portlet_demo
 */
class Bootstrap extends Bootstrapper
{
    /**
     * @inheritdoc
     */
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);
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
        if ($tabName !== 'Portlet Demo') {
            return parent::renderAdminMenuTab($tabName, $menuID, $smarty);
        }
        $plugin = $this->getPlugin();

        // Daten fürs Template
        $portletInfo = [
            'name' => 'Demo Infobox',
            'class' => 'DemoInfoBox',
            'group' => 'productdetails',
            'description' => 'Zeigt eine Info-Box auf der Produktdetailseite an'
        ];

        // Template-Pfad
        $templatePath = $plugin->getPaths()->getBasePath() . 'templates/admin/portlet_demo.tpl';
        
        try {
            return $smarty->assign('portletInfo', $portletInfo)
                ->assign('plugin', $plugin)
                ->fetch($templatePath);
        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Fehler beim Laden des Templates: ' . 
                   htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}