<?php

namespace Tests\Unit\Fixtures;

use OpenApi\Builder\Hooks;
use OpenApi\Contracts\PluginInterface;
use OpenApi\Spec\Server;
use OpenApi\Specification;

class TestPlugin implements PluginInterface
{
    public function configure(Hooks $hooks, array $config): void
    {
        $url = $config['url'] ?? 'https://plugin.test';

        $hooks->withSpecification(
            static fn (Specification $specification) => $specification->add(new Server(url: $url))
        );
    }
}
