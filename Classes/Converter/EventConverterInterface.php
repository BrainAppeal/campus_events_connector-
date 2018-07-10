<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-10
 */

namespace BrainAppeal\BrainEventConnector\Converter;


interface EventConverterInterface
{
    /**
     * @param \BrainAppeal\BrainEventConnector\Domain\Model\AbstractConvertConfiguration $configuration
     */
    public function run($configuration);
}