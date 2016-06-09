<?php

namespace Bolt\Extension\NikGo\Extra;

use Bolt\Library as Lib;

class Extension extends \Bolt\BaseExtension {

    public function getName() {
        return "Bolt Extras";
    }

    public function initialize() {
        $this->app->before(array($this, 'initTemplating'));
        $this->app->before(array($this, 'patchTranslationPath'));
        $this->app->before(array($this, 'patchSearchStorage'));
        $this->app->before(array($this, 'addAssets'));
        $this->app['twig']->addExtension(new Twig\HtmlTools($this->app));
        $this->addTwigCleanExcerpt();
    }

    public function initTemplating() {
        $side = $this->app['config']->getWhichEnd();
        if ($side == 'backend') {
            $this->app['twig.loader.filesystem']->prependPath(__DIR__ . '/twig/');
        }
    }

    public function patchSearchStorage() {
            $message = "[Override] Core class 'Bolt/Storage' is extended.";
            $this->app['logger.system']->debug($message, array('event' => 'extension'));
            $this->app['storage'] = $this->app->share(function ($app) {
                $storage = new Storage($app);
                return $storage;
            });
       
    }
    
    public function addAssets()
    {
       
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->app['htmlsnippets'] = true;
            $this->addJavascript('assets/js/extras.js', array('late'=>true));
        }
    
    }
    
    public function addTwigCleanExcerpt() {
        $this->addTwigFilter('cleanexcerpt', 'twigCleanExcerpt');
        $this->addTwigFunction('cleanexcerpt', 'twigCleanExcerpt');
    }
    
    public function twigCleanExcerpt($excerpt) {
               
        $excerpt = str_replace('>', '> ', $excerpt); # add spaces after tags
        $excerpt = str_replace('&nbsp;', ' ', $excerpt); # remove all HTML spaces
        $excerpt = preg_replace('/_+/', ' ', $excerpt); # remove all multiple underscores
        $excerpt = preg_replace('/\s+/', ' ', $excerpt); # remove all multiple spaces

        return new \Twig_Markup($excerpt, 'UTF-8');
    }

    public function patchTranslationPath() {
        # workaround for Bolt 2.2 - fix in master (3.0), see #3553 and #3800
        //        $app->before(function() use ($app) {
        //            $path = $app['resources']->getPath('root/app/resources/translations');
        //            $app['translator']->addResource('yml', $path.'/contenttypes.de_DE.yml', 'de_DE', 'contenttypes');
        //        });

        if ("Bolt\Configuration\Composer" != get_class($this->app['resources'])) {
            return;
        }

//        $versionParser = new \Composer\Package\Version\VersionParser();
//        $version = $versionParser->parseConstraints($app['bolt_version']);
//        print_r($version);

        
            // Directory to look for translation file(s)
            $transDir = $this->app['resources']->getPath('root/app/resources/translations/' . $this->app['locale']);

            if (is_dir($transDir)) {
                $iterator = new \DirectoryIterator($transDir);
                /**
                 * @var \SplFileInfo $fileInfo
                 */
                foreach ($iterator as $fileInfo) {
                    $ext = Lib::getExtension((string) $fileInfo);
                    if (!$fileInfo->isFile() || !in_array($ext, array('yml', 'xlf'))) {
                        continue;
                    }
                    list($domain) = explode('.', $fileInfo->getFilename());
                    $this->app['translator']->addResource($ext, $transDir . '/' . $fileInfo->getFilename(), $this->app['locale'], $domain);
                }
            }
        
    }

}
