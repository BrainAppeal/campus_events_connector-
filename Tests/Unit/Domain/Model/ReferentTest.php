<?php
namespace BrainAppeal\BrainEventConnector\Tests\Unit\Domain\Model;

/**
 * Test case.
 */
class ReferentTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \BrainAppeal\BrainEventConnector\Domain\Model\Referent
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \BrainAppeal\BrainEventConnector\Domain\Model\Referent();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function dummyTestToNotLeaveThisFileEmpty()
    {
        self::markTestIncomplete();
    }
}
