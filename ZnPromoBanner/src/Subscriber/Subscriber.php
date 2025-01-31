<?php declare(strict_types=1);

namespace Zn\PromoBanner\Subscriber;

use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class Subscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService $config
     */
    private SystemConfigService $config;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;


    public function __construct(SystemConfigService $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HeaderPageletLoadedEvent::class => 'headerLoaded'
        ];
    }

    public function headerLoaded(HeaderPageletLoadedEvent $event): void {
        $context = $event->getSalesChannelContext();
        $content = $this->config->get('ZnPromoBanner.config.content', $context->getSalesChannelId());
        // $content = $this->config->get('ZnPromoBanner.config.content');
        if($content == '') {
            return;
        }
        $startingAt = $this->config->get('ZnPromoBanner.config.startingAt');
        $endsAt = $this->config->get('ZnPromoBanner.config.endsAt');
        $displayAreas = $this->config->get('ZnPromoBanner.config.displayAreas');
        $bgcolor = $this->config->get('ZnPromoBanner.config.bgcolor');
        $now = new \DateTime('now');
        if(!empty($startingAt)) {
            if(new \DateTime($startingAt) > $now) {
                return;
            }
            if(!empty($endsAt)) {
                if(new \DateTime($startingAt) > new \DateTime($endsAt) && $now > new \DateTime($endsAt)) {
                    return;
                }
            }
        }
        if(!empty($endsAt)) {
            if($now > new \DateTime($endsAt)) {
                return;
            }
        }
        if($this->displayPromo($event->getRequest()->getPathInfo(), $displayAreas)) {
            $event->getPagelet()->addArrayExtension('promoBanner', ['content' => $content, 'bgcolor' => $bgcolor]);
        }
    }

    private function displayPromo(string $path, array $displayAreas):bool {
        $matchAreas = [
            'home' => '/',
            'content' => ['/navigation/', '/blog/', '/detail/'],
            'system' => ['/account/', '/checkout/']
        ];
        foreach($displayAreas as $area) {
            $tempArea = $matchAreas[$area];
            if(!is_array($tempArea) && $tempArea != '') {
                if ($path === $tempArea) {
                    return true;
                }
                continue;  
            }
            foreach($tempArea as $display) {
                if (str_contains($path, $display)) {
                    return true;
                }   
            }
        }       
        return false;
    }

}