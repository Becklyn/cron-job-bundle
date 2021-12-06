<?php declare(strict_types=1);

namespace Becklyn\CronJobBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Marco Woehr <mw@becklyn.com>
 *
 * @since 2021-09-23
 */
class CronJobBundleConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder () : TreeBuilder
    {
        $treeBuilder = new TreeBuilder("becklyn_cron_job");

        $treeBuilder->getRootNode()
                ->children()
                ->integerNode("log_ttl")
                    ->defaultValue(30)
                    ->info("The ttl of a cron job log entry in days.")
                ->end()
            ->end();

        return $treeBuilder;
    }
}
