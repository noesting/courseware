<?php
namespace Mooc\UI;

use Courseware\Container;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 */
class BlockFactory {

    private $container;

    private $blockClasses = null;

    // TODO
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Block $sorm_block
     *
     * @return \Mooc\UI\Block
     */
    public function makeBlock($sorm_block)
    {
        $class = 'Mooc\\UI\\'.$sorm_block->type.'\\'.$sorm_block->type;

        // there is no class describing a UI for that kind of block
        if (!class_exists($class)) {
            return null;
        }

        return new $class($this->container, $sorm_block);
    }

    // TODO
    public function getBlockClasses()
    {
        static $classes;
        if (!isset($classes)) {
            $classes = array_map("basename", glob($this->getPluginDir() . '/blocks/*'));
        }
        return $classes;
    }

    // TODO
    public function getContentBlockClasses()
    {
        $all = $this->getBlockClasses();
        return array_diff($all, \Mooc\DB\Block::getStructuralBlockClasses());
    }

    /**
     * Returns a block instance by its name.
     *
     * @param string $name The block name
     *
     * @return \Mooc\UI\Block The block instance or null if no block could be
     *                        found
     */
    public function getBlockByName($name)
    {
        $name = strtolower($name);
        $this->buildBlockClassCache();

        if (!isset($this->blockClasses[$name])) {
            return null;
        }

        $fqcn = $this->blockClasses[$name];
        $className = $fqcn;

        if (preg_match('/\\\\(\w+)$/', $className, $matches)) {
            $className = $matches[1];
        }

        $block = new \Mooc\DB\Block();
        $block->type = $className;

        return new $fqcn($this->container, $block);
    }

    // TODO
    protected function getPluginDir()
    {
        return dirname(dirname(dirname(__DIR__)));
    }

    /**
     * Builds the cache for name-to-block mappings.
     */
    private function buildBlockClassCache()
    {
        if ($this->blockClasses !== null) {
            return;
        }

        $this->blockClasses = array();

        foreach ($this->getBlockClasses() as $className) {
            $alias = strtolower($className);

            if (substr($alias, -5) === 'block') {
                $alias = substr($alias, 0, strlen($alias) - 5);
            }

            $this->blockClasses[$alias] = '\Mooc\UI\\'.$className.'\\'.$className;
        }
    }
}
