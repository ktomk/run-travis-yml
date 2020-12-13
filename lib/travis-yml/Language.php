<?php

/*
 * run-travis-yml
 */

namespace Ktomk\TravisYml;

/**
 * experimental
 */
class Language
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TravisYml
     */
    private $config;

    /**
     * Language constructor.
     *
     * @param string $name
     * @param TravisYml $config
     */
    public function __construct(TravisYml $config, $name)
    {
        $this->config = $config;
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }

    /**
     * matrix keys are defined by language
     *
     * @return array
     */
    public function matrixKeys()
    {
        $name = $this->name;
        $config = $this->config;
        /** @see TravisYml::$languages */
        $languages = $config::$languages;
        $keys = Node::item($languages, array($name, 'matrix'), array());
        // if the language is known but undefined for matrix keys, take a default
        // FIXME(tk): define all languages
        if (array() === $keys && in_array($name, $languages, true)) {
            $keys = array('env', $name);
        }
        return $keys;
    }
}
