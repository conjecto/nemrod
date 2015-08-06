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
use Conjecto\Nemrod\ResourceManager\FiliationBuilder;
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
     * @var FiliationBuilder
     */
    protected $filiationBuilder;

    /**
     * @param FiliationBuilder $filiationBuilder
     */
    public function setFiliationBuilder(FiliationBuilder $filiationBuilder)
    {
        $this->filiationBuilder = $filiationBuilder;
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

    public function load($name, $type = null, $includeSubFrames = true, $assoc = true, $findTypeInFrames = false)
    {
        if ($findTypeInFrames) {
            $frame = $this->getFrame($name, true);
            if (isset($frame['@type'])) {
                $type = $frame['@type'];
            }
        }

        if ($includeSubFrames) {
            $frames = $this->getParentFrames($type);
            if (!in_array($name, $frames)) {
                $frames[] = $name;
            }

            // merge frames together
            $finalFrame = array();
            foreach ($frames as $currentFrame) {
                if (!empty($currentFrame)) {
                    // find frame with frame path and merge included frames
                    $frame = $this->mergeWithIncludedFrames($this->getFrame($currentFrame), $assoc);
                    // merge current frame with other frames
                    $finalFrame = $this->array_merge_recursive($finalFrame, $frame);
                }
            }

            return $finalFrame;
        }
        else {
            return $this->mergeWithIncludedFrames($this->getFrame($name), $assoc);
        }
    }

    /**
     * Search parent frame pathes
     * @param $parentClass
     * @param array $parentFrames
     * @return array
     */
    public function getParentFrames($type)
    {
        $parentClasses = $this->filiationBuilder->getParentTypes($type);
        $parentFrames = array();

        if (!$parentClasses || empty($parentClasses)) {
            return $parentFrames;
        }

        foreach ($parentClasses as $parentClass) {
            $phpClass = TypeMapper::get($parentClass);
            if ($phpClass) {
                $metadata = $this->metadataFactory->getMetadataForClass($phpClass);
                $frame = $metadata->getFrame();
                if ($frame) {
                    $parentFrames[] = $metadata->getFrame();
                }
            }
        }

        return $parentFrames;
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
            $frame = $this->array_merge_recursive($frame, $includedFrame);
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
            $frame = $this->array_merge_recursive($includedFrame, $frame);
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
                        $frame = $this->array_merge_recursive($frame, $this->getFrame($classMetadata));
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

    private function array_merge_recursive($array1, $array2)
    {
        if (empty($array1) && !empty($array2)) {
            return $array2;
        }
        if (empty($array2) && !empty($array1)) {
            return $array1;
        }
        $finalArray = array();
        $keys = array_merge(array_keys($array1), array_keys($array2));

        foreach ($keys as $key) {
            $result = array();
            if (isset($array1[$key])) {
                if (isset($array2[$key])) {
                    if (!is_array($array1[$key])) {
                        $result = $array1[$key];
                    }
                    else if(!is_array($array2[$key])) {
                        $result = $array1[$key];
                    }
                    else {
                        $result = $this->array_merge_recursive($array1[$key], $array2[$key]);
                    }
                }
                else {
                    $result = $array1[$key];
                }
            }
            else if (isset($array2[$key])){
                $result = $array2[$key];
            }
            $finalArray[$key] = $result;
        }

        return $finalArray;
    }
}
