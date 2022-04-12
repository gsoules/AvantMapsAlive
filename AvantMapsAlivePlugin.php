<?php

class AvantMapsAlivePlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $locationHistoryChanged = false;

    protected $_hooks = array(
        'admin_head',
        'config',
        'config_form',
        'define_routes',
        'initialize',
        'public_head'
    );

    protected $_filters = array(
    );

    protected function head()
    {
    }

    public function hookAdminHead($args)
    {
        $this->head();
    }

    public function hookConfig()
    {
        MapsAliveConfig::saveConfiguration();
    }

    public function hookConfigForm()
    {
        require dirname(__FILE__) . '/config_form.php';
    }

    public function hookDefineRoutes($args)
    {
        $args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
    }

    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages');
    }

    public function hookPublicHead($args)
    {
        $this->head();
    }
}
