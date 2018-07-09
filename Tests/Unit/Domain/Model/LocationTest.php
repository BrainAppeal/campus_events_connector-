<?php
namespace BrainAppeal\BrainEventConnector\Tests\Unit\Domain\Model;

/**
 * Test case.
 */
class LocationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \BrainAppeal\BrainEventConnector\Domain\Model\Location
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \BrainAppeal\BrainEventConnector\Domain\Model\Location();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getNameReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getName()
        );
    }

    /**
     * @test
     */
    public function setNameForStringSetsName()
    {
        $this->subject->setName('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'name',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getStreetNameReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getStreetName()
        );
    }

    /**
     * @test
     */
    public function setStreetNameForStringSetsStreetName()
    {
        $this->subject->setStreetName('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'streetName',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getTownReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getTown()
        );
    }

    /**
     * @test
     */
    public function setTownForStringSetsTown()
    {
        $this->subject->setTown('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'town',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getZipCodeReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getZipCode()
        );
    }

    /**
     * @test
     */
    public function setZipCodeForStringSetsZipCode()
    {
        $this->subject->setZipCode('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'zipCode',
            $this->subject
        );
    }
}
