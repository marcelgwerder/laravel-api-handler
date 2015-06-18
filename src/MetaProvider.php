<?php namespace Marcelgwerder\ApiHandler;

abstract class MetaProvider
{
    /**
     * Title of the meta field
     *
     * @var string
     */
    protected $title;

    /**
     * Get the title of the meta field
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get the meta information
     *
     * @return string
     */
    abstract public function get();
}
