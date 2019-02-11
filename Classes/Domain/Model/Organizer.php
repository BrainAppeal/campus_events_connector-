<?php
namespace BrainAppeal\CampusEventsConnector\Domain\Model;

/***
 *
 * This file is part of the "CampusEventsConnector" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018
 *
 ***/

/**
 * Organizer
 */
class Organizer extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
