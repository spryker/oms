<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Form\DataProvider;

use Spryker\Zed\Oms\Communication\Form\OmsTriggerForm;

class OrderOmsTriggerFormDataProvider extends AbstractOmsTriggerFormDataProvider
{
    /**
     * @param string $redirectUrl
     * @param string $event
     * @param int $id Order ID
     *
     * @return array<string, mixed>
     */
    public function getOptions(string $redirectUrl, string $event, int $id): array
    {
        return [
            OmsTriggerForm::OPTION_OMS_ACTION => static::OMS_ACTION_ORDER_TRIGGER,
            OmsTriggerForm::OPTION_EVENT => $event,
            OmsTriggerForm::OPTION_SUBMIT_BUTTON_CLASS => static::SUBMIT_BUTTON_CLASS,
            OmsTriggerForm::OPTION_QUERY_PARAMS => [
                static::QUERY_PARAM_EVENT => $event,
                static::QUERY_PARAM_ID_SALES_ORDER => $id,
                static::QUERY_PARAM_REDIRECT => $redirectUrl,
            ],
        ];
    }
}
