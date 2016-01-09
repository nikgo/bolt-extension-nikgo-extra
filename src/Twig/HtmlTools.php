<?php

namespace Bolt\Extension\NikGo\Extra\Twig;

use Bolt\Library as Lib;

class HtmlTools extends \Twig_Extension {

    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Twig_Environment
     */
    private $twig = null;

    public function __construct(\Silex\Application $app) {
        $this->app = $app;
        $this->config = $this->app['config']->config;
    }

    public function initRuntime(\Twig_Environment $environment) {
        $this->twig = $environment;
    }

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('htmlThumbs', array($this, 'htmlThumbs')),
            new \Twig_SimpleFilter('htmlRespImg', array($this, 'htmlRespImg')),
        );
    }
    
    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('htmlThumbs', array($this, 'htmlThumbs')),
            new \Twig_SimpleFunction('htmlRespImg', array($this, 'htmlRespImg')),
        );
    }

    public function htmlThumbs($html) {
        $handler = $this->app['twig.handlers'];

        $dom = $this->createDOMDocument($html);

        $elements = $dom->getElementsByTagName('img');

        foreach ($elements as $element) {
            if ($element->hasAttribute('src') && $element->hasAttribute('width') && $element->hasAttribute('height')) {
                $file = $element->getAttribute('src');
                $width = $element->getAttribute('width');
                $height = $element->getAttribute('height');

                $src = $handler['image']->thumbnail(Lib::safeFilename($file), $width, $height, 'r');

                // @TODO: File exist?
                $element->setAttribute('src', $src);
            }
        }

        $result = $dom->saveHTML();
        return new \Twig_Markup($result, 'UTF-8');
    }

    public function getName() {
        return 'bolt_htmltools';
    }

    public function htmlRespImg($html, $name, array $options = array()) {
        
        $dom = $this->createDOMDocument($html);
        $elements = $dom->getElementsByTagName('img');

        if (count($elements) === 0) {
            return $html;
        }
        
        // Get Extension boltresponsiveimages
        $extensionName = 'boltresponsiveimages';
        
                
        if (!$this->app['extensions']->isEnabled($extensionName)) {
            return $html;
        }
        
        $extension = $this->app['extensions.' . $extensionName];
                
        if ($extension == null) {
            return $html;
        }

        // Get Twig Function respImg
        $twigFunction = $this->twig->getFunction('respImg');
        if (!$twigFunction) {
            return $html;
        }
        
        $respImg = $twigFunction->getCallable();

        // Not override sizes, if defined
        if (!$options['sizes']) {
            $options['sizes'] = $extension->getSizesAttrib($name);
        }

        foreach ($elements as $element) {
            if (!$element->hasAttribute('src')) {
                continue;
            }

            $file = $element->getAttribute('src');
            $filename = Lib::safeFilename($file);

            // Set options for specific image
            $optionsImg = $options;
            
            // Add width fallback to sizes because layout reasons
            // Example: An editor choose a specific width
            if ($element->hasAttribute('width')) {
                $width = $element->getAttribute('width');
                $optionsImg['sizes'][] = $width . 'px';
            }

            if ($element->hasAttribute('class')) {
                $attrClass = $element->getAttribute('class');
                $optionsImg['class'] = $attrClass;
            }

            $htmlImg = (string) $respImg($filename, $name, $optionsImg);
            $domImg = $this->createDOMDocument($htmlImg);

            // Load the $domImg document fragment node into the current document
            $newnode = $dom->importNode($domImg->documentElement, true);

            // Replace current img node
            $element->parentNode->replaceChild($newnode, $element);
        }

        $result = $dom->saveHTML();
        return new \Twig_Markup($result, 'UTF-8');
    }

    private function createDOMDocument($html) {
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL);

        foreach ($dom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item); // remove hack
                break;
            }
        }

        return $dom;
    }

}
