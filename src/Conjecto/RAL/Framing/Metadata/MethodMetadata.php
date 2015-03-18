<?php
namespace Conjecto\RAL\Framing\Metadata;

use Metadata\MethodMetadata as BaseMethodMetadata;

/**
 * Extend Serializer MethodMetadata to handle extra options
 *
 * @package Conjecto\RAL\Bundle\Serializer\Metadata;
 */
class MethodMetadata extends BaseMethodMetadata
{
    use MetadataTrait;
}
