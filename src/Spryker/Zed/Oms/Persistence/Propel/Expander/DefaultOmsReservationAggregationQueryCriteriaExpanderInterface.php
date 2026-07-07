<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Persistence\Propel\Expander;

use Generated\Shared\Transfer\QueryCriteriaTransfer;
use Generated\Shared\Transfer\ReservationRequestTransfer;

interface DefaultOmsReservationAggregationQueryCriteriaExpanderInterface
{
    public function expand(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        ReservationRequestTransfer $reservationRequestTransfer
    ): QueryCriteriaTransfer;
}
