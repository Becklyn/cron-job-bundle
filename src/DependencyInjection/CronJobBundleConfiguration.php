<?php declare(strict_types=1);


namespace Becklyn\CronJobBundle;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Marco Woehr <mw@becklyn.com>
 * @since 2021-09-23
 */
class CronJobBundleConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder () : TreeBuilder
    {
        $treeBuilder = new TreeBuilder("becklyn_cron_job");

        $treeBuilder->getRootNode()
                ->children()
                ->integerNode("storage_duration")
                ->defaultValue(30)
                ->info("The Duration how long a Cron Job Log will be stored in the Database before it get's deleted in Days.")
                ->end()
            ->end();

        return $treeBuilder;
    }
}
