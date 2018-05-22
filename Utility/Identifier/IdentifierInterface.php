<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Utility\Identifier;

interface IdentifierInterface
{
    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @return mixed
     */
    public function getInternalRepresentation();

    /**
     * @return string
     */
    public function stringifyIdentifier(): string;

    /**
     * @param mixed $identifier
     *
     * @return bool
     */
    public function equals($identifier): bool;
}
