<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Templating;

use Liip\ImagineBundle\Templating\Helper\ImagineHelper;

class ImagineExtension extends \Twig_Extension
{
    /**
     * @var ImagineHelper
     */
    private $helper;

    /**
     * @param ImagineHelper $helper
     */
    public function __construct(ImagineHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('imagine_filter', array($this->helper, 'filter')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->helper->getName();
    }
}
