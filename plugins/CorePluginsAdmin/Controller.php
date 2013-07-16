<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_CorePluginsAdmin
 */

/**
 *
 * @package Piwik_CorePluginsAdmin
 */
class Piwik_CorePluginsAdmin_Controller extends Piwik_Controller_Admin
{
    function extend()
    {
        $view = $this->configureView('@CorePluginsAdmin/extend');
        echo $view->render();
    }

    function plugins()
    {
        $view = $this->configureView('@CorePluginsAdmin/plugins');
        $view->pluginsInfo = $this->getPluginsInfo();
        echo $view->render();
    }

    function themes()
    {
        $view = $this->configureView('@CorePluginsAdmin/themes');
        $view->pluginsInfo = $this->getPluginsInfo($themesOnly = true);
        echo $view->render();
    }

    protected function configureView($template)
    {
        Piwik::checkUserIsSuperUser();
        $view = new Piwik_View($template);
        $this->setBasicVariablesView($view);
        $this->displayWarningIfConfigFileNotWritable($view);
        return $view;
    }

    protected function getPluginsInfo( $themesOnly = false )
    {
        $plugins = array();

        $listPlugins = array_merge(
            Piwik_PluginsManager::getInstance()->readPluginsDirectory(),
            Piwik_Config::getInstance()->Plugins['Plugins']
        );
        $listPlugins = array_unique($listPlugins);
        foreach ($listPlugins as $pluginName) {
            Piwik_PluginsManager::getInstance()->loadPlugin($pluginName);
            $plugins[$pluginName] = array(
                'activated'       => Piwik_PluginsManager::getInstance()->isPluginActivated($pluginName),
                'alwaysActivated' => Piwik_PluginsManager::getInstance()->isPluginAlwaysActivated($pluginName),
            );
        }
        Piwik_PluginsManager::getInstance()->loadPluginTranslations();

        $loadedPlugins = Piwik_PluginsManager::getInstance()->getLoadedPlugins();
        foreach ($loadedPlugins as $oPlugin) {
            $pluginName = $oPlugin->getPluginName();
            $plugins[$pluginName]['info'] = $oPlugin->getInformation();
        }

        foreach ($plugins as $pluginName => &$plugin) {
            if (!isset($plugin['info'])) {
                $plugin['info'] = array(
                    'description' => '<strong><em>' . Piwik_Translate('CorePluginsAdmin_PluginCannotBeFound')
                        . '</strong></em>',
                    'version'     => Piwik_Translate('General_Unknown'),
                    'theme' => false,
                );
            }
        }

        $pluginsFiltered = $this->keepPluginsOrThemes($themesOnly, $plugins);
        return $pluginsFiltered;
    }

    protected function keepPluginsOrThemes($themesOnly, $plugins)
    {
        $pluginsFiltered = array();
        foreach ($plugins as $name => $thisPlugin) {

            $isTheme = false;
            if (!empty($thisPlugin['info']['theme'])) {
                $isTheme = (bool)$thisPlugin['info']['theme'];
            }
            if (($themesOnly && $isTheme)
                || (!$themesOnly && !$isTheme)
            ) {
                $pluginsFiltered[$name] = $thisPlugin;
            }
        }
        return $pluginsFiltered;
    }

    public function deactivate($redirectAfter = true)
    {
        Piwik::checkUserIsSuperUser();
        $this->checkTokenInUrl();
        $pluginName = Piwik_Common::getRequestVar('pluginName', null, 'string');
        Piwik_PluginsManager::getInstance()->deactivatePlugin($pluginName);
        if ($redirectAfter) {
            Piwik_Url::redirectToReferer();
        }
    }

    public function activate($redirectAfter = true)
    {
        Piwik::checkUserIsSuperUser();
        $this->checkTokenInUrl();
        $pluginName = Piwik_Common::getRequestVar('pluginName', null, 'string');
        Piwik_PluginsManager::getInstance()->activatePlugin($pluginName);
        if ($redirectAfter) {
            Piwik_Url::redirectToReferer();
        }
    }
}
