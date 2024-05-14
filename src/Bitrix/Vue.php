<?php

namespace Dbogdanoff\Bitrix;

use Exception;
use Bitrix\Main\Page\Asset;

class Vue
{
    const COMPONENTS_PATH = '/local/components-vue';

    protected static $init = false;
    protected static $arHtml = [];
    protected static $arIncluded = [];

    /**
     * Подключает Vue-компонент
     *
     * @param string|array $componentName
     * @param array $addFiles
     * @throws Exception
     */
    public static function includeComponent($componentName, $innerPath = '', array $addFiles = [])
    {
        if (self::$init !== true) {
            System::checkBitrix();
            self::$init = true;

            \AddEventHandler('main', 'OnEndBufferContent', ['\Dbogdanoff\Bitrix\Vue', 'insertComponents']);
        }

        foreach ((array)$componentName as $name) {
            if (self::$arIncluded[$name] === true) {
                continue;
            }

            self::$arIncluded[$name] = true;

            $docPath = self::getComponentsPath();
            $rootPath = $_SERVER['DOCUMENT_ROOT'] . $docPath;
            // Подключает зависимости скрипты/стили
            if (file_exists($settings = $rootPath . '/' . $name . '/.settings.php')) {
                $settings = require_once $settings;
                if (array_key_exists('require', $settings)) {
                    foreach ((array)$settings['require'] as $file) {
                        self::addFile($file);
                    }
                }
            }

            // Подключает доп. зависимости скрипты/стили
            foreach ($addFiles as $file) {
                self::addFile($file);
            }

            $files = scandir($rootPath . '/' . $name);
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if ($fileInfo['extension'] === "vue") {
                    $fileTemplate = file_get_contents($rootPath . '/' . $name . '/' . $file);
                    $tplElementPosStart = strpos($fileTemplate, '<template id="') + 14;
                    $tplElementPosEnd = strpos($fileTemplate, '"', $tplElementPosStart);
                    $templateId = substr($fileTemplate, $tplElementPosStart, $tplElementPosEnd - $tplElementPosStart);
                    $fileTemplate = str_replace('export default {', '', $fileTemplate);
                    $fileTemplate = str_replace("<script>",
                        "<script>BX.Vue.component('" . $templateId . "', {'template': '#" . $templateId . "',",
                        $fileTemplate);
                    if (strpos($fileTemplate, '<style scoped')) {
                        $scopeClass = 'scope-' . uniqid();
                        $tplElementPosEndQuote = strpos($fileTemplate, '>', $tplElementPosStart);
                        $fileTemplate = substr_replace($fileTemplate, '<div class="' . $scopeClass . '">',
                            $tplElementPosEndQuote + 1, 0);
                        $fileTemplate = str_replace('</template>', '</div></template>', $fileTemplate);
                        $fileTemplate = str_replace('<style scoped>',
                            '<style scoped>@scope (.' . $scopeClass . ') {', $fileTemplate);
                        $fileTemplate = str_replace('</style>', '}</style>', $fileTemplate);
                    }
                    $fileTemplate = str_replace("</script>", ")</script>", $fileTemplate);
                    self::$arHtml[] = $fileTemplate;
                }
            }

            if (!defined('DBOGDANOFF_DEV') && file_exists($rootPath . '/' . $name . '/script.min.js')) {
                self::addFile($docPath . '/' . $name . '/script.min.js');
            } elseif (file_exists($rootPath . '/' . $name . '/script.js')) {
                self::addFile($docPath . '/' . $name . '/script.js');
            } elseif (file_exists($rootPath . '/' . $name . '/script.min.js')) {
                self::addFile($docPath . '/' . $name . '/script.min.js');
            }

            if (!defined('DBOGDANOFF_DEV') && file_exists($rootPath . '/' . $name . '/style.min.css')) {
                self::addFile($docPath . '/' . $name . '/style.min.css');
            } elseif (file_exists($rootPath . '/' . $name . '/style.css')) {
                self::addFile($docPath . '/' . $name . '/style.css');
            } elseif (file_exists($rootPath . '/' . $name . '/style.min.css')) {
                self::addFile($docPath . '/' . $name . '/style.min.css');
            }
        }
    }

    public static function includeComponentByPath($componentName, $componentPath, array $addFiles = [])
    {
        if (self::$init !== true) {
            System::checkBitrix();
            self::$init = true;

            \AddEventHandler('main', 'OnEndBufferContent', ['\Dbogdanoff\Bitrix\Vue', 'insertComponents']);
        }


        if (self::$arIncluded[$componentName] === true) {
            exit;
        }

        self::$arIncluded[$componentName] = true;

        $rootPath = $componentPath;
        // Подключает зависимости скрипты/стили
        if (file_exists($settings = $rootPath . '/.settings.php')) {
            $settings = require_once $settings;
            if (array_key_exists('require', $settings)) {
                foreach ((array)$settings['require'] as $file) {
                    self::addFile($file);
                }
            }
        }

        // Подключает доп. зависимости скрипты/стили
        foreach ($addFiles as $file) {
            self::addFile($file);
        }

        $files = scandir($rootPath);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if ($fileInfo['extension'] === "vue") {
                $fileTemplate = file_get_contents($rootPath . '/' . $file);
                $tplElementPosStart = strpos($fileTemplate, '<template id="') + 14;
                $tplElementPosEnd = strpos($fileTemplate, '"', $tplElementPosStart);
                $templateId = substr($fileTemplate, $tplElementPosStart, $tplElementPosEnd - $tplElementPosStart);
                $fileTemplate = str_replace('export default {', '', $fileTemplate);
                $fileTemplate = str_replace("<script>",
                    "<script>BX.Vue.component('" . $templateId . "', {'template': '#" . $templateId . "',",
                    $fileTemplate);
                if (strpos($fileTemplate, '<style scoped')) {
                    $scopeClass = 'scope-' . uniqid();
                    $tplElementPosEndQuote = strpos($fileTemplate, '>', $tplElementPosStart);
                    $fileTemplate = substr_replace($fileTemplate, '<div class="' . $scopeClass . '">',
                        $tplElementPosEndQuote + 1, 0);
                    $fileTemplate = str_replace('</template>', '</div></template>', $fileTemplate);
                    $fileTemplate = str_replace('<style scoped>',
                        '<style scoped>@scope (.' . $scopeClass . ') {', $fileTemplate);
                    $fileTemplate = str_replace('</style>', '}</style>', $fileTemplate);
                }
                $fileTemplate = str_replace("</script>", ")</script>", $fileTemplate);
                self::$arHtml[] = $fileTemplate;
            }
        }

        if (!defined('DBOGDANOFF_DEV') && file_exists($rootPath . '/script.min.js')) {
            self::addFile($docPath . '/' . $name . '/script.min.js');
        } elseif (file_exists($rootPath  . '/script.js')) {
            self::addFile($docPath  . '/script.js');
        } elseif (file_exists($rootPath  . '/script.min.js')) {
            self::addFile($docPath  . '/script.min.js');
        }

        if (!defined('DBOGDANOFF_DEV') && file_exists($rootPath  . '/style.min.css')) {
            self::addFile($docPath . '/' . $name . '/style.min.css');
        } elseif (file_exists($rootPath . '/style.css')) {
            self::addFile($docPath  . '/style.css');
        } elseif (file_exists($rootPath . '/style.min.css')) {
            self::addFile($docPath  . '/style.min.css');
        }
    }

    /**
     * Подключает js или css файл
     *
     * @param string $file
     */
    public static function addFile(string $file)
    {
        global $APPLICATION;

        if (strpos($file, '.js') !== false) {
            Asset::getInstance()->addJs($file);
        } else {
            if (strpos($file, '.css') !== false) {
                $APPLICATION->SetAdditionalCSS($file);
            }
        }
    }

    /**
     * Вставляет все подключенные компоненты в тело документа
     * Метод обработчик события OnEndBufferContent
     *
     * @param $content
     */
    public static function insertComponents(&$content)
    {
        $include = "<div style='display:none'>";
        $include .= implode("\n", self::$arHtml);
        $include .= "</div>";
        $content = preg_replace('/<body([^>]*)?>/', "<body$1>" . $include, $content, 1);
        if (
            defined('DBOGDANOFF_VUE_MINIFY') &&
            strpos($_SERVER['REQUEST_URI'], '/bitrix') === false &&
            strpos($_SERVER['REQUEST_URI'], '/local') === false &&
            strpos($_SERVER['REQUEST_URI'], '/rest') === false &&
            strpos($_SERVER['REQUEST_URI'], '/api') === false &&
            !preg_match('/.*\.(pdf|png|jpg|jpeg|gif|webp|exe)/i', $_SERVER['REQUEST_URI']) &&
            $GLOBALS['APPLICATION']->PanelShowed !== true
        ) {
            $content = System::minifyContent($content, DBOGDANOFF_VUE_MINIFY);
        }
    }

    /**
     * Путь к директории с компонентами
     * @return string
     */
    public static function getComponentsPath(): string
    {
        if (defined('DBOGDANOFF_VUE_PATH')) {
            return '/' . trim(DBOGDANOFF_VUE_PATH, '/');
        }

        return self::COMPONENTS_PATH;
    }

    public static function includeAllComponentFromPath($componentsRootPath = '')
    {
        $directory = '';
        if (!$componentsRootPath) {
            $directory = $_SERVER['DOCUMENT_ROOT'] . self::getComponentsPath();
        } else {
            $directory = $componentsRootPath;
        }
        $dirs = scandir($directory);
        foreach ($dirs as $key => $value) {
            if ($value != "." && $value != "..") {
                $path = realpath($directory . DIRECTORY_SEPARATOR . $value);
                if (is_dir($path)) {
                    if (count(glob($path . DIRECTORY_SEPARATOR . '*.vue')) > 0) {
                        self::includeComponentByPath($value, $directory . DIRECTORY_SEPARATOR . $value);
                    } else {
                        self::includeAllComponentFromPath($directory. DIRECTORY_SEPARATOR . $value);
                    }
                }
            }
        }
    }
}
