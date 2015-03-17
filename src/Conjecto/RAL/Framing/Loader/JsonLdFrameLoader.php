<?php

namespace Conjecto\RAL\Framing\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * FilesystemLoader extends the default Twig filesystem loader
 * to work with the Symfony paths and jsonld frames references.
 */
class JsonLdFrameLoader extends \Twig_Loader_Filesystem
{
    /**
     * Return the decoded frame
     */
    public function load($name, $assoc = true)
    {
        $decoded = json_decode($this->getSource($name), $assoc);
        if($decoded === null) {
            throw new \Twig_Error_Loader(sprintf('Unable to decode frame "%s".', $name));
        }
        return $decoded;
    }

    /**
     * Returns the path to the template file.
     *
     * The file locator is used to locate the template when the naming convention
     * is the symfony one (i.e. the name can be parsed).
     * Otherwise the template is located using the locator from the twig library.
     *
     * @param string|TemplateReferenceInterface $template The template
     *
     * @return string The path to the template file
     *
     * @throws \Twig_Error_Loader if the template could not be found
     */
    protected function findTemplate($template)
    {
        $logicalName = (string) $template;

        if (isset($this->cache[$logicalName])) {
            return $this->cache[$logicalName];
        }

        $file = null;
        $previous = null;

        try {
            $file = parent::findTemplate($logicalName);
        } catch (\Twig_Error_Loader $e) {
            throw new \Twig_Error_Loader(sprintf('Unable to find frame "%s".', $logicalName), -1, null, $previous);
        }

        return $this->cache[$logicalName] = $file;
    }

}
