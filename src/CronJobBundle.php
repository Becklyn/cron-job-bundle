<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle;

use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CronJobBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build (ContainerBuilder $container) : void
    {
        $container
            ->registerForAutoconfiguration(CronJobInterface::class)
            ->addTag("cron.job");
    }


    /**
     * @inheritdoc
     */
    public function getContainerExtension ()
    {
        return new class() extends Extension {
            /**
             * @inheritdoc
             */
            public function load (array $configs, ContainerBuilder $container) : void
            {
                // load main services.yml
                $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
                $loader->load('services.yaml');
            }


            /**
             * @inheritDoc
             */
            public function getAlias ()
            {
                return "becklyn_cron_job";
            }
        };
    }
}
