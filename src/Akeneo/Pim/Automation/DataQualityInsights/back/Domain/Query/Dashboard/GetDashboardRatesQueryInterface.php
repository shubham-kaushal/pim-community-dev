<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2020 Akeneo SAS (http://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Akeneo\Pim\Automation\DataQualityInsights\Domain\Query\Dashboard;

use Akeneo\Pim\Automation\DataQualityInsights\Domain\Model\Read;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\CategoryCode;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\ChannelCode;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\FamilyCode;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\LocaleCode;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\TimePeriod;

interface GetDashboardRatesQueryInterface
{
    public function byCatalog(ChannelCode $channel, LocaleCode $locale, TimePeriod $timePeriod): ?Read\DashboardRates;

    public function byCategory(ChannelCode $channel, LocaleCode $locale, TimePeriod $timePeriod, CategoryCode $category): ?Read\DashboardRates;

    public function byFamily(ChannelCode $channel, LocaleCode $locale, TimePeriod $timePeriod, FamilyCode $family): ?Read\DashboardRates;
}
