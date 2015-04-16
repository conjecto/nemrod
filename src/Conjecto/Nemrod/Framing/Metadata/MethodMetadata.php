<?php

/*
 * This file is part of the Nemrod package.
 *
 * (c) Conjecto <contact@conjecto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Conjecto\Nemrod\Framing\Metadata;

use Metadata\MethodMetadata as BaseMethodMetadata;

/**
 * Extend Serializer MethodMetadata to handle extra options.
 */
class MethodMetadata extends BaseMethodMetadata
{
    use MetadataTrait;
}
