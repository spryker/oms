<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Expander;

use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Oms\Communication\Builder\OmsTriggerFormCollectionBuilderInterface;

class OmsOrderDetailDataExpander implements OmsOrderDetailDataExpanderInterface
{
    protected const string KEY_OMS_TRIGGER_FORM_COLLECTION = 'omsTriggerFormCollection';

    protected const string KEY_CHANGE_STATUS_REDIRECT_URL = 'changeStatusRedirectUrl';

    protected const string KEY_EVENTS = 'events';

    public function __construct(protected readonly OmsTriggerFormCollectionBuilderInterface $omsTriggerFormCollectionBuilder)
    {
    }

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param array<string, mixed> $orderDetailData
     *
     * @return array<string, mixed>
     */
    public function expand(OrderTransfer $orderTransfer, array $orderDetailData): array
    {
        $redirectUrl = $orderDetailData[static::KEY_CHANGE_STATUS_REDIRECT_URL] ?? null;
        $events = $orderDetailData[static::KEY_EVENTS] ?? [];

        if (!$redirectUrl || !$events) {
            return $orderDetailData;
        }

        $orderDetailData[static::KEY_OMS_TRIGGER_FORM_COLLECTION] = $this->omsTriggerFormCollectionBuilder
            ->buildOrderOmsTriggerFormCollection($redirectUrl, $events, $orderTransfer->getIdSalesOrder());

        return $orderDetailData;
    }
}
