<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Loader;

use EasyRdf\TypeMapper;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Metadata\MetadataFactory;

/**
 * FilesystemLoader extends the default Twig filesystem loader
 * to work with the Symfony paths and jsonld frames references.
 */
class JsonLdFrameLoader extends \Twig_Loader_Filesystem
{
    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @param MetadataFactory $metadataFactory
     */
    public function setMetadataFactory($metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Return the decoded frame.
     */
    public function getFrame($name, $assoc = true)
    {
        $decoded = json_decode($this->getSource($name), $assoc);
        if ($decoded === null) {
            throw new \Twig_Error_Loader(sprintf('Unable to decode frame "%s".', $name));
        }

        return $decoded;
    }

    public function load($name, $parentClass = null, $includeSubFrames = true, $assoc = true, $getTypeFromFrame = false)
    {
        // if the parentClass have not been defined before, for elasticsearch mapping for example
        if ($getTypeFromFrame) {
            $frame = $this->load($name);
            if (isset($frame['@type'])) {
                $typeClass = $frame['@type'];
                $phpClass = TypeMapper::get($typeClass);
                if ($phpClass) {
                    $metadata = $this->metadataFactory->getMetadataForClass($phpClass);
                    $parentClass = $metadata->getParentClass();
                }
            }
        }

        if ($includeSubFrames) {
            // find parent class frames
            $frames = $this->getParentFrames($parentClass);
            $frames[] = $name;

            // merge frames together
            $finalFrame = array();
            foreach ($frames as $currentFrame) {
                if (!empty($currentFrame)) {
                    // find frame with frame path and merge included frames
                    $frame = $this->mergeWithIncludedFrames($this->getFrame($currentFrame), $assoc);
                    // merge current frame with other frames
                    $finalFrame = array_merge_recursive($finalFrame, $frame);
                }
            }

            // keep the original type
            if (isset($finalFrame['@type'])) {
                $types = $finalFrame['@type'];
                if (is_array($types)) {
                    $finalFrame['@type'] = $types[count($types) - 1];
                }
            }

            return $finalFrame;
        }
        else {
            return $this->getFrame($name, $assoc);
        }
    }

    /**
     * Search parent frame pathes
     * @param $parentClass
     * @param array $parentFrames
     * @return array
     */
    public function getParentFrames($parentClass, $parentFrames = array(), $skipRoot = false)
    {
        if (!$parentClass) {
            return $parentFrames;
        }

        $phpClass = TypeMapper::get($parentClass);
        if ($phpClass) {
            $metadata = $this->metadataFactory->getMetadataForClass($phpClass);
            $parentClass = $metadata->getParentClass();
            // if we don't want to get the root frame
            if (!$skipRoot) {
                $parentFrames[] = $metadata->getFrame();
            }

            return $this->getParentFrames($metadata->getParentClass(), $parentFrames);
        }
        else {
            return $parentFrames;
        }
    }

    /**
     * Include subFrames defined we @ include property
     * @param $frame
     * @param null $parentKey
     * @return array
     */
    protected function mergeWithIncludedFrames($frame, $parentKey = null)
    {
        if (!is_array($frame)) {
            return $frame;
        }

        $includedFrame = array();
        foreach ($frame as $key => $subFrame) {
            if ($key === "@include") {
                return $this->includeSubFrame($frame, $subFrame);
            }
            $includedFrame[$key] = $this->mergeWithIncludedFrames($subFrame, $key);
        }

        return $includedFrame;
    }

    protected function includeSubFrame($frame, $subFrame)
    {
        // get frame type before manipulating the frame
        $initialFrameType = null;
        if (isset($frame['@type'])) {
            $initialFrameType = $frame['@type'];
        }

        if (is_string($subFrame)) {
            $includedFrame = $this->getFrame($subFrame);
            unset($frame["@include"]);
            $frame = array_merge_recursive($frame, $includedFrame);
        }
        else if (is_array($subFrame) && isset($subFrame))
        {
            // get parentObjectFrames option before manipulating the frame
            $parentObjectFrames = null;
            if (isset($subFrame['parentObjectFrames'])) {
                $parentObjectFrames = $subFrame['parentObjectFrames'];
            }

            // get and merge the included frame
            unset($frame["@include"]);
            $includedFrame = $this->getFrame($subFrame);
            $frame = array_merge_recursive($includedFrame, $frame);
            // clear the frame
            unset($frame["@include"]);
            if ($initialFrameType) {
                $frame["@type"] = $initialFrameType;
            }

            // include subFrame parent frames if parentObjectFrames is setted to true
            if ($parentObjectFrames && isset($frame["@type"])) {
                $parentClassMetadatas = $this->getParentMetadatas($frame["@type"], array(), true);
                foreach ($parentClassMetadatas as $classMetadata) {
                    if ($classMetadata && isset($classMetadata) && !empty($classMetadata)) {
                        $frame = array_merge_recursive($frame, $this->getFrame($classMetadata));
                    }
                }

                // reset the initial type
                if (isset($frame['@type'])) {
                    $types = $frame['@type'];
                    if (is_array($types)) {
                        $frame['@type'] = $types[0];
                    }
                }
            }
        }

        if ($initialFrameType) {
            $frame["@type"] = $initialFrameType;
        }

        // recall recursive frame include if included frames have other included frames
        return $this->mergeWithIncludedFrames($frame);
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
