<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class AsistenteIaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('asistente_ia.base_url', $config['base_url']);
        $container->setParameter('asistente_ia.chat_endpoint', $config['chat_endpoint']);
        $container->setParameter('asistente_ia.index_endpoint', $config['index_endpoint']);
        $container->setParameter('asistente_ia.tenant_name', $config['tenant_name']);
        $container->setParameter('asistente_ia.api_key', $config['api_key']);
        $container->setParameter('asistente_ia.connect_timeout', $config['connect_timeout']);
        $container->setParameter('asistente_ia.timeout', $config['timeout']);
        $container->setParameter('asistente_ia.verify_peer', $config['verify_peer']);
        $container->setParameter('asistente_ia.verify_host', $config['verify_host']);
        $container->setParameter('asistente_ia.default_headers', $config['default_headers']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }
}
