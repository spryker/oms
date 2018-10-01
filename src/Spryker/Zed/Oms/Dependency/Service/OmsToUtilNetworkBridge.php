<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Dependency\Service;

class OmsToUtilNetworkBridge implements OmsToUtilNetworkInterface
{
    /**
     * @var \Spryker\Service\UtilNetwork\UtilNetworkServiceInterface
     */
    protected $utilNetworkService;

    /**
     * @param \Spryker\Service\UtilNetwork\UtilNetworkServiceInterface $utilNetworkService
     */
    public function __construct($utilNetworkService)
    {
        $this->utilNetworkService = $utilNetworkService;
    }

    /**
     * @return string
     */
    public function getHostName()
    {
        return $this->utilNetworkService->getHostName();
    }
}
