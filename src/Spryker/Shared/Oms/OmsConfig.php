<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Oms;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class OmsConfig extends AbstractSharedConfig
{
    /**
     * @api
     *
     * Predefined enumerated state flag list as defined in oms.xsd file.
     *
     * @var string
     */
    public const STATE_TYPE_FLAG_EXCLUDE_FROM_CUSTOMER = 'exclude from customer';

    /**
     * @api
     *
     * @var string
     */
    public const STATE_TYPE_FLAG_EXCLUDE_FROM_INVOICE = 'exclude from invoice';

    /**
     * @api
     *
     * @var string
     */
    public const STATE_TYPE_FLAG_READY_FOR_INVOICE = 'ready for invoice';

    /**
     * @api
     *
     * @var string
     */
    public const STATE_TYPE_FLAG_WAITING_FOR_EXPORT = 'waiting for export';

    /**
     * @api
     *
     * @var string
     */
    public const STATE_TYPE_FLAG_CANCELLABLE = 'cancellable';
}
