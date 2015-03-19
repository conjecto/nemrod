<?php
namespace Conjecto\RAL\Framing\Metadata;

use Metadata\MethodMetadata as BaseMethodMetadata;

/**
 * Extend Serializer MethodMetadata to handle extra options.
 */
class MethodMetadata extends BaseMethodMetadata
{
    use MetadataTrait;
}
