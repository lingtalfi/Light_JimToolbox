<?php


namespace Ling\Light_JimToolbox\Service;


use Ling\BabyYaml\BabyYamlUtil;
use Ling\Light\ServiceContainer\LightServiceContainerInterface;
use Ling\Light_ControllerHub\Service\LightControllerHubService;
use Ling\Light_JimToolbox\Exception\LightJimToolboxException;
use Ling\Light_ReverseRouter\Service\LightReverseRouterService;
use Ling\UrlSmuggler\UrlSmugglerTool;


/**
 * The LightJimToolboxService class.
 */
class LightJimToolboxService
{

    /**
     * This property holds the container for this instance.
     * @var LightServiceContainerInterface
     */
    protected LightServiceContainerInterface $container;

    /**
     * This property holds the options for this instance.
     *
     * Available options are:
     *
     *
     *
     * See the @page(Light_JimToolbox conception notes) for more details.
     *
     *
     * @var array
     */
    protected $options;


    /**
     * Builds the LightJimToolboxService instance.
     */
    public function __construct()
    {
        $this->options = [];
    }

    /**
     * Sets the container.
     *
     * @param LightServiceContainerInterface $container
     */
    public function setContainer(LightServiceContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns the option value corresponding to the given key.
     * If the option is not found, the return depends on the throwEx flag:
     *
     * - if set to true, an exception is thrown
     * - if set to false, the default value is returned
     *
     *
     * @param string $key
     * @param null $default
     * @param bool $throwEx
     * @throws \Exception
     */
    public function getOption(string $key, $default = null, bool $throwEx = false)
    {
        if (true === array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        if (true === $throwEx) {
            $this->error("Undefined option: $key.");
        }
        return $default;
    }


    /**
     * Returns the array of jim toolbox items.
     *
     * See the @page(Light_JimToolbox conception notes) for more information.
     *
     * Available options are:
     * - execute: string = Ling\Light_JimToolbox\Controller\JimToolboxController->render
     *      Defines the controller to execute
     *
     *
     * @param array $options
     * @return array
     */
    public function getJimToolboxItems(array $options = []): array
    {
        $ret = [];
        $uri = $this->container->getLight()->getHttpRequest()->getUri();
        $file = $this->getJimToolboxItemsFile();
        if (true === is_file($file)) {
            $arr = BabyYamlUtil::readFile($file);
            foreach ($arr as $k => $v) {
                $ret[$k] = $v;
                if (true === array_key_exists("acp_class", $v)) {

                    /**
                     * @var $hu LightControllerHubService
                     */
                    $hu = $this->container->get("controller_hub");
                    $route = $hu->getRouteName();


                    $execute = $v['execute'] ?? "Ling\Light_JimToolbox\Controller\JimToolboxController->render";

                    $get = $v['get'] ?? [];
                    /**
                     * @var $rr LightReverseRouterService
                     */
                    $rr = $this->container->get("reverse_router");
                    $url = $rr->getUrl($route, array_merge($get, [
                        'execute' => $execute,
                        'acp_class' => $v['acp_class'],
                        'current_uri' => UrlSmugglerTool::smuggle($uri),
                    ]), true);
                    $ret[$k]['url'] = $url;
                }
            }
        }
        return $ret;
    }


    /**
     * Returns the location of our default template.
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->container->getApplicationDir() . "/templates/Ling.Light_JimToolbox/light_kit_jimtoolbox.inc.php";
    }

    /**
     * Registers a jim toolbox item.
     * See the @page(Light_JimToolbox conception notes) for more information.
     *
     * @param string $key
     * @param array $item
     */
    public function registerJimToolboxItem(string $key, array $item)
    {
        $file = $this->getJimToolboxItemsFile();
        if (true === is_file($file)) {
            $arr = BabyYamlUtil::readFile($file);
        } else {
            $arr = [];
        }
        if (true === array_key_exists($key, $arr)) {
            throw new LightJimToolboxException("A jim toolbox item with the key $key already exists. Aborting.");
        }

        $arr[$key] = $item;
        BabyYamlUtil::writeFile($arr, $file);
    }


    /**
     * Unregisters a jim toolbox item, and returns whether the given key was actually registered.
     *
     * If the given key didn't exist, false is returned.
     *
     *
     * See the @page(Light_JimToolbox conception notes) for more information.
     *
     * @param string $key
     * @return bool
     */
    public function unregisterJimToolboxItem(string $key): bool
    {
        $found = false;
        $file = $this->getJimToolboxItemsFile();
        if (true === is_file($file)) {
            $arr = BabyYamlUtil::readFile($file);
        } else {
            $arr = [];
        }
        if (true === array_key_exists($key, $arr)) {
            unset($arr[$key]);
            $found = true;
        }

        BabyYamlUtil::writeFile($arr, $file);
        return $found;
    }




    //--------------------------------------------
    //
    //--------------------------------------------
    /**
     * Throws an exception.
     *
     * @param string $msg
     * @throws \Exception
     */
    private function error(string $msg)
    {
        throw new LightJimToolboxException($msg);
    }


    /**
     * Returns the path to the jim toolbox items file.
     * @return string
     */
    private function getJimToolboxItemsFile(): string
    {
        $appDir = $this->container->getApplicationDir();
        return $appDir . '/config/open/Ling.Light_JimToolbox/items.byml';
    }

}