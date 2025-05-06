<?php

declare(strict_types=1);

namespace Plugin\jtl_gtag;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Psr\Log\LoggerInterface;
use JTL\phpQuery\phpQueryObject;
use JTL\Consent\Item; // Hinzufügen für Consent Item

/**
 * Class Bootstrap
 * @package Plugin\jtl_gtag
 */
class Bootstrap extends Bootstrapper
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Initialisiert den Logger
     */
    private function initLogger(): void
    {
        if (\method_exists($this->getPlugin(), 'getLogger')) {
            $this->logger = $this->getPlugin()->getLogger();
        } else {
            // Fallback für Shop-Versionen < 5.3.0
            $this->logger = Shop::Container()->getLogService();
        }
        $this->logger->info('Plugin jtl_gtag initialized');
    }


    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);

        // Logger initialisieren
        $this->initLogger();

        // Listener für den HOOK_SMARTY_OUTPUTFILTER registrieren
        $dispatcher->listen('shop.hook.' . \HOOK_SMARTY_OUTPUTFILTER, [$this, 'addGoogleTagManagerScript']);

        // Listener für den JTL Consent Manager registrieren
        if (defined('\CONSENT_MANAGER_GET_ACTIVE_ITEMS')) {
            $dispatcher->listen('shop.hook.' . \CONSENT_MANAGER_GET_ACTIVE_ITEMS, [$this, 'addCustomConsentItems']);
            $this->logger->info('Listener für CONSENT_MANAGER_GET_ACTIVE_ITEMS registriert.');
        } else {
            $this->logger->error('Konstante für Consent Manager Hook nicht gefunden. Consent Items können nicht registriert werden.');
        }
    }

    /**
     * Fügt dem JTL Consent Manager benutzerdefinierte Einträge für GTM hinzu.
     *
     * @param array $args Argumente des Events, enthält die Collection der Consent Items.
     */
    public function addCustomConsentItems(array $args): void
    {
        if (!isset($args['items']) || !method_exists($args['items'], 'push') || !method_exists($args['items'], 'reduce')) {
            $this->logger->error('Consent Items Collection ist nicht im erwarteten Format.');
            return;
        }

        $lastID = $args['items']->reduce(static function ($result, Item $item) {
            $value = $item->getID();
            return $result === null || $value > $result ? $value : $result;
        }) ?? 0;

        // Basis-Shop-URL ermitteln
        $shopURL = Shop::getURL();

        // Consent Item für Analytics / Statistiken
        $analyticsItem = new Item();
        $analyticsItem->setName('Statistiken & Analyse (Google Tag Manager)'); 
        $analyticsItem->setID(++$lastID);
        $analyticsItem->setItemID('jtl_gtag_analytics'); 
        $analyticsItem->setDescription('Diese Einwilligung erlaubt uns, anonymisierte Daten über Ihre Nutzung unserer Webseite zu sammeln, um unser Angebot und Ihr Nutzererlebnis zu verbessern. Dies erfolgt über den Google Tag Manager.'); // Passen Sie diesen Text an
        $analyticsItem->setPurpose('Webseitenanalyse, Verbesserung des Nutzererlebnisses');
        $analyticsItem->setPrivacyPolicy($shopURL . '/datenschutz'); 
        $analyticsItem->setCompany('Google Inc.'); 
        $args['items']->push($analyticsItem);
        $this->logger->info('Consent Item "jtl_gtag_analytics" zum Consent Manager hinzugefügt.');

        // Consent Item für Marketing / Personalisierung
        $marketingItem = new Item();
        $marketingItem->setName('Marketing & Personalisierung (Google Tag Manager)'); 
        $marketingItem->setID(++$lastID);
        $marketingItem->setItemID('jtl_gtag_marketing'); 
        $marketingItem->setDescription('Diese Einwilligung erlaubt uns, Daten zu sammeln, um Ihnen relevantere Werbung auf dieser und anderen Webseiten anzuzeigen und Marketingkampagnen zu optimieren. Dies erfolgt über den Google Tag Manager.'); // Passen Sie diesen Text an
        $marketingItem->setPurpose('Personalisierte Werbung, Marketingoptimierung'); 
        $marketingItem->setPrivacyPolicy($shopURL . '/datenschutz'); 
        $marketingItem->setCompany('Google Inc.'); 
        $args['items']->push($marketingItem);
        $this->logger->info('Consent Item "jtl_gtag_marketing" zum Consent Manager hinzugefügt.');
    }

    /**
     * Fügt das Google Tag Manager Skript und Consent Mode Skripte der Seite hinzu.
     *
     * @param array $args_arr Argumente des Hooks
     * @return void
     */
    public function addGoogleTagManagerScript(array $args_arr): void
    {
        $gtmID = $this->getPlugin()->getConfig()->getValue('gtag');

        if (empty($gtmID) || $gtmID === 'GTM-XXXXXX') {
            $this->logger->debug('Google Tag Manager ID ist nicht konfiguriert. Überspringe das Einfügen der GTM-Skripte.');
            return;
        }

        /** @var phpQueryObject $document */
        $document = $args_arr['document'];

        // Standard Google Consent Mode v2 Skript (vor GTM einfügen)
        $defaultConsentScript = "
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('consent', 'default', {
            'ad_storage': 'denied',
            'analytics_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied'
            // 'wait_for_update': 500 // Optional: Wartezeit für ein Update in Millisekunden
          });
          console.log('jtl_gtag: Default consent gesetzt.');
        </script>";

        // GTM Skript für den <head>
        $gtmHeadScript = "
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','" . $gtmID . "');</script>
        <!-- End Google Tag Manager -->";

        // GTM <noscript> Tag für den <body>
        $gtmBodyNoScript = "
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=" . $gtmID . "\"
        height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->";

        // Skript zum Aktualisieren des Consent Status basierend auf localStorage
        $updateConsentScript = "
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            try {
              const consentData = localStorage.getItem('consent');
              if (!consentData) {
                console.log('jtl_gtag: consent nicht im localStorage gefunden.');
                return;
              }

              const consent = JSON.parse(consentData);
              console.log('jtl_gtag: Geladene Consent-Daten:', consent);

              const granted = {
                ad_storage: 'denied',
                analytics_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied'
              };

              if (consent && consent.settings) {
                // Prüfe auf Einwilligung für 'jtl_gtag_analytics'
                if (consent.settings.jtl_gtag_analytics === true) {
                  granted.analytics_storage = 'granted';
                  console.log('jtl_gtag: analytics_storage auf granted gesetzt (wegen jtl_gtag_analytics).');
                } else {
                  console.log('jtl_gtag: analytics_storage bleibt denied (jtl_gtag_analytics nicht true).');
                }

                // Prüfe auf Einwilligung für 'jtl_gtag_marketing'
                if (consent.settings.jtl_gtag_marketing === true) {
                  granted.ad_storage = 'granted';
                  granted.ad_user_data = 'granted';
                  granted.ad_personalization = 'granted';
                  console.log('jtl_gtag: ad_storage, ad_user_data, ad_personalization auf granted gesetzt (wegen jtl_gtag_marketing).');
                } else {
                  console.log('jtl_gtag: ad_storage, ad_user_data, ad_personalization bleiben denied (jtl_gtag_marketing nicht true).');
                }
              } else {
                console.log('jtl_gtag: consent.settings nicht im consent Objekt gefunden.');
              }

              if (window.gtag) {
                window.gtag('consent', 'update', granted);
                console.log('jtl_gtag: gtag consent update ausgeführt mit:', granted);
              } else {
                console.warn('jtl_gtag: window.gtag nicht gefunden für consent update.');
              }

            } catch (e) {
              console.warn('jtl_gtag: Fehler beim Verarbeiten des Consent Mode:', e);
            }
          });
        </script>";

        $head = $document->find('head');
        if ($head->length > 0) {
            $head->prepend($gtmHeadScript);
            $head->prepend($defaultConsentScript);
            $this->logger->info('Google Tag Manager Head-Skript und Default Consent eingefügt.');
        } else {
            $this->logger->warning('Konnte das <head>-Element nicht finden, um die GTM Head-Skripte einzufügen.');
        }

        $body = $document->find('body');
        if ($body->length > 0) {
            $body->prepend($gtmBodyNoScript);
            $this->logger->info('Google Tag Manager Body-Noscript eingefügt.');
            $body->append($updateConsentScript);
            $this->logger->info('Consent Update Skript eingefügt.');
        } else {
            $this->logger->warning('Konnte das <body>-Element nicht finden, um das GTM Body-Noscript und Update-Skript einzufügen.');
        }
    }
}
