<?php

/**
 * OptionKioskTextsTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class OptionKioskTextsTable extends PluginOptionKioskTextsTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object OptionKioskTextsTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('OptionKioskTexts');
    }
}