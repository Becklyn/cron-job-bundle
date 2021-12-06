<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle;

use Becklyn\CronJobBundle\Cron\CronJobCleanUp;
use Becklyn\CronJobBundle\Cron\CronJobInterface;
use Becklyn\CronJobBundle\DependencyInjection\CronJobBundleConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
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
    public function getContainerExtension () : ?ExtensionInterface
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

                $config = $this->processConfiguration(
                    new CronJobBundleConfiguration(),
                    $configs
                );

                $container->getDefinition(CronJobCleanUp::class)
                    ->setArgument('$logTtl', $config["log_ttl"]);
            }


            /**
             * @inheritDoc
             */
            public function getAlias () : string
            {
                return "becklyn_cron_job";
            }
        };
    }
}
