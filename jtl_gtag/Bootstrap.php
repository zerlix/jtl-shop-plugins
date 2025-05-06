<?php

declare(strict_types=1);

namespace Plugin\jtl_gtag;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use Psr\Log\LoggerInterface;
use JTL\phpQuery\phpQueryObject;

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
              const consent = JSON.parse(localStorage.getItem('jtl_cookie_consent'));

              if (!consent) return;

              const granted = {
                ad_storage: 'denied',
                analytics_storage: 'denied',
                ad_user_data: 'denied',      // Hinzugefügt für Vollständigkeit mit Default
                ad_personalization: 'denied' // Hinzugefügt für Vollständigkeit mit Default
              };

              // Du kannst diese Keys je nach deinem Setup anpassen
              if (consent.marketing) {
                granted.ad_storage = 'granted';
                granted.ad_user_data = 'granted'; // Wenn Marketing auch diese umfasst
                granted.ad_personalization = 'granted'; // Wenn Marketing auch diese umfasst
              }

              if (consent.tracking) {
                granted.analytics_storage = 'granted';
              }

              // Google Consent Mode updaten
              window.gtag && window.gtag('consent', 'update', granted);

            } catch (e) {
              console.warn('Consent Mode konnte nicht gesetzt werden:', e);
            }
          });
        </script>";

        $head = $document->find('head');
        if ($head->length > 0) {
            // Wichtig: Zuerst Default Consent, dann GTM
            $head->prepend($gtmHeadScript); // GTM wird zuerst prepended
            $head->prepend($defaultConsentScript); // Dann Default Consent, damit es vor GTM steht
            // Alternativ: $head->prepend($defaultConsentScript . $gtmHeadScript);
            $this->logger->info('Google Tag Manager Head-Skript und Default Consent eingefügt.');
        } else {
            $this->logger->warning('Konnte das <head>-Element nicht finden, um die GTM Head-Skripte einzufügen.');
        }

        $body = $document->find('body');
        if ($body->length > 0) {
            $body->prepend($gtmBodyNoScript);
            $this->logger->info('Google Tag Manager Body-Noscript eingefügt.');
            // Das Update-Skript am Ende des Bodys einfügen
            $body->append($updateConsentScript);
            $this->logger->info('Consent Update Skript eingefügt.');
        } else {
            $this->logger->warning('Konnte das <body>-Element nicht finden, um das GTM Body-Noscript und Update-Skript einzufügen.');
        }
    }
}
