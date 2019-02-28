<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Service\Oms;

use Spryker\Service\Kernel\AbstractService;

/**
 * @method \Spryker\Service\Oms\OmsServiceFactory getFactory()
 */
class OmsService extends AbstractService implements OmsServiceInterface
{
    /**
     * {@inheritdoc}
     *
     * @api
     *
     * @param float $value
     *
     * @return float
     */
    public function round(float $value): float
    {
        return $this->getFactory()
            ->createFloatRounder()
            ->round($value);
    }
}
