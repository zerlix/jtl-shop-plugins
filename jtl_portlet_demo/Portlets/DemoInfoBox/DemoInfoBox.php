<?php

declare(strict_types=1);

namespace Plugin\jtl_portlet_demo\Portlets\DemoInfoBox;

use JTL\Plugin\PluginInterface;
use JTL\Smarty\JTLSmarty;
use JTL\Shop;
use JTL\Catalog\Product\Artikel;
use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use JTL\OPC\InputType;

/**
 * Class DemoInfoBox
 * @package Plugin\jtl_portlet_demo\Portlets\DemoInfoBox
 */
class DemoInfoBox extends Portlet
{
    /**
     * @var ?PluginInterface
     */
    protected ?PluginInterface $plugin;

    /**
     * Definiert die Eigenschaften des Portlets, die im OPC konfigurierbar sind
     * 
     * @return array
     */
    public function getPropertyDesc(): array
    {
        return [
            'portlet-title' => [
                'label'   => __('Titel'),
                'type'    => InputType::TEXT,
                'width'   => 50,
                'default' => __('11Produktinformation'),
            ],
            'portlet-content' => [
                'label'   => __('Inhalt'),
                'type'    => InputType::TEXTAREA,
                'width'   => 100,
                'default' => __('Dies ist eine Demo-Infobox für das Produkt.'),
            ]
        ];
    }

    /**
     * Rendert das finale HTML für die Ausgabe im Frontend
     * 
     * @param PortletInstance $instance
     * @param bool $inContainer
     * @return string
     */
    public function getFinalHtml(PortletInstance $instance, bool $inContainer = true): string
    {
        $smarty = Shop::Smarty();
        $product = $this->getCurrentProduct();
        
        // Daten für das Template aufbereiten
        $data = [
            'title'   => $instance->getProperty('portlet-title') ?? __('Produktinformation'),
            'content' => $instance->getProperty('portlet-content') ?? __('Dies ist eine Demo-Infobox für das Produkt.'),
            'style'   => $instance->getProperty('portlet-style') ?? 'info',
            'product' => $product
        ];
        
        // Produktspezifische Daten hinzufügen, wenn ein Produkt verfügbar ist
        if ($product !== null && $product instanceof Artikel) {
            $data['productName'] = $product->cName;
            $data['productNumber'] = $product->cArtNr;
            // Überschreibe den Inhalt nur, wenn er nicht im OPC konfiguriert wurde
            if ($instance->getProperty('portlet-content') === null) {
                $data['content'] = __('Dies ist eine Demo-Infobox für das Produkt') . ' ' . $product->cName;
            }
        }
        
        // Template rendern und zurückgeben
        $templatePath = $this->getPlugin()->getPaths()->getBasePath() . 'templates/portlets/demo_infobox.tpl';
        return $smarty->assign('data', $data)->fetch($templatePath);
    }
    
    /**
     * Ermittelt das aktuelle Produkt auf verschiedenen Wegen
     * 
     * @return Artikel|null
     */
    private function getCurrentProduct(): ?Artikel
    {
        // Standard-Methode für JTL-Shop 5.x
        if (isset(Shop::getGlobals()['oArtikel']) && Shop::getGlobals()['oArtikel'] instanceof Artikel) {
            return Shop::getGlobals()['oArtikel'];
        }

        // Fallback 
        if (Shop::$kArtikel > 0) {
            $product = new Artikel();
            $product->fuelleArtikel(Shop::$kArtikel, Artikel::getDefaultOptions());
            return $product;
        }
        
        // Kein Produkt gefunden
        return null;
    }
}
