<?php

namespace Liip\ImagineBundle\Exception\Binary\Loader;

class NotChainLoadableException extends NotLoadableException
{
    /**
     * @param string                 $message
     * @param NotLoadableException[] $previousStack
     */
    public function __construct($message, array $previousStack = [])
    {
        parent::__construct(static::createPreviousAwareMessage($message, $previousStack));
    }

    /**
     * @param string                 $message
     * @param NotLoadableException[] $previousStack
     *
     * @return string
     */
    static private function createPreviousAwareMessage($message, array $previousStack = [])
    {
        if (count($previousStack) === 0) {
            return $message.' No loaders supported this path.';
        }

        $message = array_reduce($previousStack, function (&$carry, NotLoadableException $exception) {
            return sprintf('%s %s (%s),', $carry, pathinfo($exception->getFile(), PATHINFO_FILENAME), $exception->getMessage());
        }, $message.' The following loaders were tried:');

        return substr($message, 0, -1).'.';
    }
}
