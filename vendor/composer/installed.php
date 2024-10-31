<?php return array(
    'root' => array(
        'name' => 'quickdemoimport/quick-demo-import',
        'pretty_version' => '1.0.1',
        'version' => '1.0.1.0',
        'reference' => '41b2d3d40fcea2e0c8dbdbd9918af986bf0f48c0',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'composer/installers' => array(
            'pretty_version' => 'v1.11.0',
            'version' => '1.11.0.0',
            'reference' => 'ae03311f45dfe194412081526be2e003960df74b',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'quickdemoimport/quick-demo-import' => array(
            'pretty_version' => '1.0.1',
            'version' => '1.0.1.0',
            'reference' => '41b2d3d40fcea2e0c8dbdbd9918af986bf0f48c0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
